<?php

namespace App\Services\Payroll;

use App\Models\PayrollBatch;
use App\Models\Role;
use App\Models\User;
use App\Models\WorkflowAction;
use App\Models\WorkflowDefinition;
use App\Models\WorkflowInstance;
use App\Models\WorkflowStep;
use App\Services\Hr\HrScopeService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PayrollWorkflowService
{
    public const WORKFLOW_CODE = 'PAYROLL_APPROVAL';

    public const STEP_ROLES = [
        'hr',
        'spo_fm',
        'deputy_md',
        'fa',
        'addt_chief_eng',
        'addt_md',
        'md',
    ];

    public function workflowInstanceFor(PayrollBatch $batch): ?WorkflowInstance
    {
        return WorkflowInstance::query()
            ->where('workflowable_type', $batch->getMorphClass())
            ->where('workflowable_id', $batch->id)
            ->with([
                'currentStep',
                'actions' => fn ($query) => $query
                    ->with(['workflowStep', 'actedBy:id,name'])
                    ->latest('acted_at')
                    ->latest('id'),
            ])
            ->latest()
            ->first();
    }

    public function canSubmitFromHr(PayrollBatch $batch, ?User $user = null): bool
    {
        $user ??= Auth::user();

        if (! $user || ! $user->hasRole('hr')) {
            return false;
        }

        return ! $batch->isLocked()
            && in_array($batch->status, ['draft', 'returned'], true)
            && app(HrScopeService::class)->isHeadOfficeHr($user);
    }

    public function canHrEditBeforeSubmission(PayrollBatch $batch, ?User $user = null): bool
    {
        $user ??= Auth::user();

        if (! $user || ! $user->hasRole('hr') || $batch->isLocked()) {
            return false;
        }

        if (! in_array($batch->status, ['draft', 'returned'], true)) {
            return false;
        }

        return (int) $batch->generated_by === (int) $user->id
            || app(HrScopeService::class)->isHeadOfficeHr($user);
    }

    public function canDiscardFromHr(PayrollBatch $batch, ?User $user = null): bool
    {
        return $this->canHrEditBeforeSubmission($batch, $user);
    }

    public function canGenerateFinalPayrollDocuments(PayrollBatch $batch, ?User $user = null): bool
    {
        $user ??= Auth::user();

        return $batch->isLocked()
            && app(HrScopeService::class)->isHeadOfficeHr($user);
    }

    public function canCurrentUserAct(PayrollBatch $batch, ?User $user = null): bool
    {
        $user ??= Auth::user();

        if (! $user || $batch->isLocked() || $batch->status !== 'pending') {
            return false;
        }

        $instance = $this->workflowInstanceFor($batch);
        $role = $instance?->currentStep?->role;

        return $role !== null && $user->hasRole($role);
    }

    public function canCurrentUserEdit(PayrollBatch $batch, ?User $user = null): bool
    {
        $user ??= Auth::user();

        if (! $user || $batch->isLocked()) {
            return false;
        }

        return $this->canHrEditBeforeSubmission($batch, $user)
            || $this->canCurrentUserAct($batch, $user);
    }

    public function currentRoleLabel(PayrollBatch $batch): ?string
    {
        $role = $this->workflowInstanceFor($batch)?->currentStep?->role;

        return $role ? Role::labelFor($role) : null;
    }

    public function submitFromHr(PayrollBatch $batch, ?string $remarks = null, ?User $user = null): PayrollBatch
    {
        $user ??= Auth::user();

        abort_unless($this->canSubmitFromHr($batch, $user), 403);

        return DB::transaction(function () use ($batch, $remarks, $user) {
            $workflow = $this->ensurePayrollWorkflow();
            $hrStep = $workflow->steps->firstWhere('role', 'hr');
            $nextStep = $this->nextStep($hrStep);

            $instance = $this->workflowInstanceFor($batch);

            if (! $instance) {
                $instance = new WorkflowInstance([
                    'workflow_definition_id' => $workflow->id,
                    'status' => 'in_progress',
                    'started_by' => $user?->id,
                ]);

                $instance->workflowable()->associate($batch);
            }

            $instance->forceFill([
                'workflow_definition_id' => $workflow->id,
                'current_step_id' => $nextStep?->id,
                'status' => 'in_progress',
                'completed_at' => null,
            ])->save();

            WorkflowAction::create([
                'workflow_instance_id' => $instance->id,
                'workflow_step_id' => $hrStep?->id,
                'acted_by' => $user?->id,
                'action' => $batch->status === 'returned' ? 'resubmitted' : 'submitted',
                'remarks' => $remarks,
                'acted_at' => now(),
            ]);

            $batch->update([
                'status' => 'pending',
                'approved_by' => null,
                'approved_at' => null,
                'locked_at' => null,
            ]);

            $batch->items()->update(['status' => 'pending']);

            return $batch->refresh();
        });
    }

    public function approveCurrentStep(PayrollBatch $batch, ?string $remarks = null, ?User $user = null): PayrollBatch
    {
        $user ??= Auth::user();

        abort_unless($this->canCurrentUserAct($batch, $user), 403);

        return DB::transaction(function () use ($batch, $remarks, $user) {
            $instance = $this->workflowInstanceFor($batch);
            $currentStep = $instance?->currentStep;

            abort_unless($instance && $currentStep, 403);

            $nextStep = $this->nextStep($currentStep);

            WorkflowAction::create([
                'workflow_instance_id' => $instance->id,
                'workflow_step_id' => $currentStep->id,
                'acted_by' => $user?->id,
                'action' => $nextStep ? 'approved' : 'final_approved',
                'remarks' => $remarks,
                'acted_at' => now(),
            ]);

            if ($nextStep) {
                $instance->update([
                    'current_step_id' => $nextStep->id,
                    'status' => 'in_progress',
                ]);

                return $batch->refresh();
            }

            $instance->update([
                'current_step_id' => null,
                'status' => 'completed',
                'completed_at' => now(),
            ]);

            $batch->update([
                'status' => 'locked',
                'approved_by' => $user?->id,
                'approved_at' => now(),
                'locked_at' => now(),
            ]);

            $batch->items()->update(['status' => 'approved']);

            return $batch->refresh();
        });
    }

    public function returnToHr(PayrollBatch $batch, string $remarks, ?User $user = null): PayrollBatch
    {
        $user ??= Auth::user();

        abort_unless($this->canCurrentUserAct($batch, $user), 403);

        return DB::transaction(function () use ($batch, $remarks, $user) {
            $instance = $this->workflowInstanceFor($batch);
            $currentStep = $instance?->currentStep;
            $hrStep = $this->ensurePayrollWorkflow()->steps->firstWhere('role', 'hr');

            abort_unless($instance && $currentStep && $currentStep->role !== 'hr' && $hrStep, 403);

            WorkflowAction::create([
                'workflow_instance_id' => $instance->id,
                'workflow_step_id' => $currentStep->id,
                'acted_by' => $user?->id,
                'action' => 'returned',
                'remarks' => $remarks,
                'acted_at' => now(),
            ]);

            $instance->update([
                'current_step_id' => $hrStep->id,
                'status' => 'in_progress',
            ]);

            $batch->update(['status' => 'returned']);
            $batch->items()->update(['status' => 'draft']);

            return $batch->refresh();
        });
    }

    public function ensurePayrollWorkflow(): WorkflowDefinition
    {
        $workflow = WorkflowDefinition::query()->updateOrCreate(
            ['code' => self::WORKFLOW_CODE],
            [
                'name' => 'Payroll Approval',
                'module' => 'payroll',
                'description' => 'Payroll approval flow from HR through MD.',
                'status' => 'active',
            ],
        );

        foreach (self::STEP_ROLES as $index => $role) {
            WorkflowStep::query()->updateOrCreate(
                [
                    'workflow_definition_id' => $workflow->id,
                    'sequence' => $index + 1,
                ],
                [
                    'name' => Role::labelFor($role),
                    'role' => $role,
                    'action_type' => $role === 'hr' ? 'submit' : 'approve',
                    'sla_hours' => 48,
                    'status' => 'active',
                ],
            );
        }

        return $workflow->load(['steps' => fn ($query) => $query->orderBy('sequence')]);
    }

    private function nextStep(?WorkflowStep $step): ?WorkflowStep
    {
        if (! $step) {
            return null;
        }

        return WorkflowStep::query()
            ->where('workflow_definition_id', $step->workflow_definition_id)
            ->where('status', 'active')
            ->where('sequence', '>', $step->sequence)
            ->orderBy('sequence')
            ->first();
    }
}

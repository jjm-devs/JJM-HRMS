<?php

namespace App\Livewire\Hr\Payroll;

use App\Models\PayrollBatch;
use App\Models\PayrollItem;
use App\Models\PayrollItemAdjustment;
use App\Services\Payroll\PayrollAuditService;
use App\Services\Payroll\PayrollWorkflowService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class ItemAdjustment extends Component
{
    public PayrollBatch $batch;
    public PayrollItem  $item;

    // ── manual adjustment form ────────────────────────────────────────────────
    public string  $type      = 'addition';
    public string  $label     = '';
    public string  $amount    = '';
    public string  $note      = '';
    public ?int    $editingId = null;

    // ── disbursement override form ────────────────────────────────────────────
    public string $overridePct    = '';
    public string $overrideReason = '';

    public function mount(): void
    {
        abort_unless(app(\App\Services\Hr\HrScopeService::class)->canAccessPayrollModule(), 403);

        abort_unless($this->item->payroll_batch_id === $this->batch->id, 404);

        $this->overridePct = (string) $this->item->disbursement_pct;

        $this->item->loadMissing([
            'employee.orgUnit:id,name',
            'employee.departmentStream:id,name',
        ]);
    }

    // ── adjustment rules ──────────────────────────────────────────────────────

    protected function adjustmentRules(): array
    {
        return [
            'type'   => ['required', 'in:addition,deduction'],
            'label'  => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'note'   => ['nullable', 'string', 'max:1000'],
        ];
    }

    // ── save adjustment (create or update) ────────────────────────────────────

    public function save(): void
    {
        $this->authorizePayrollEdit();

        $data = $this->validate($this->adjustmentRules());
        $audit = app(PayrollAuditService::class);
        $context = $audit->workflowContext($this->batch);
        $oldItemNet = (float) $this->item->net_salary;
        $oldBatchNet = (float) $this->batch->net_total;
        $wasEditing = $this->editingId !== null;

        if ($this->editingId) {
            $adjustment = PayrollItemAdjustment::findOrFail($this->editingId);
            abort_unless($adjustment->payroll_item_id === $this->item->id, 403);
            $beforeValues = $this->adjustmentValues($adjustment);
            $adjustment->update([
                ...$data,
                ...$context,
                'updated_by' => Auth::id(),
            ]);
            $action = 'adjustment_updated';
        } else {
            $beforeValues = null;
            $adjustment = PayrollItemAdjustment::create([
                ...$data,
                'payroll_item_id' => $this->item->id,
                'created_by'      => Auth::id(),
                'updated_by'      => Auth::id(),
                ...$context,
            ]);
            $action = 'adjustment_created';
        }

        $adjustment->refresh();
        $this->recalculate();
        $this->item->refresh();
        $this->batch->refresh();

        $audit->recordPayrollChange(
            item: $this->item,
            action: $action,
            adjustment: $adjustment,
            beforeValues: $beforeValues,
            afterValues: $this->adjustmentValues($adjustment),
            oldItemNetSalary: $oldItemNet,
            newItemNetSalary: (float) $this->item->net_salary,
            oldBatchNetTotal: $oldBatchNet,
            newBatchNetTotal: (float) $this->batch->net_total,
            remarks: $adjustment->note,
        );

        $this->resetAdjustmentForm();
        $this->editingId = null;
        session()->flash('status', $wasEditing ? 'Adjustment updated and logged.' : 'Adjustment added and logged.');
    }

    public function edit(int $id): void
    {
        $this->authorizePayrollEdit();

        $adjustment = PayrollItemAdjustment::findOrFail($id);
        abort_unless($adjustment->payroll_item_id === $this->item->id, 403);

        $this->editingId = $id;
        $this->type      = $adjustment->type;
        $this->label     = $adjustment->label;
        $this->amount    = (string) $adjustment->amount;
        $this->note      = $adjustment->note ?? '';
    }

    public function cancelEdit(): void
    {
        $this->resetAdjustmentForm();
        $this->editingId = null;
    }

    public function delete(int $id): void
    {
        $this->authorizePayrollEdit();

        $adjustment = PayrollItemAdjustment::findOrFail($id);
        abort_unless($adjustment->payroll_item_id === $this->item->id, 403);

        $beforeValues = $this->adjustmentValues($adjustment);
        $oldItemNet = (float) $this->item->net_salary;
        $oldBatchNet = (float) $this->batch->net_total;
        $adjustment->delete();

        $this->recalculate();
        $this->item->refresh();
        $this->batch->refresh();

        app(PayrollAuditService::class)->recordPayrollChange(
            item: $this->item,
            action: 'adjustment_deleted',
            adjustment: $adjustment,
            beforeValues: $beforeValues,
            afterValues: null,
            oldItemNetSalary: $oldItemNet,
            newItemNetSalary: (float) $this->item->net_salary,
            oldBatchNetTotal: $oldBatchNet,
            newBatchNetTotal: (float) $this->batch->net_total,
            remarks: $adjustment->note,
        );

        session()->flash('status', 'Adjustment removed and logged.');
    }

    // ── disbursement override ─────────────────────────────────────────────────

    public function saveDisbursementOverride(): void
    {
        abort_unless($this->batch->isPartial(), 403, 'Disbursement override is only available on partial batches.');
        $this->authorizePayrollEdit();

        $this->validate([
            'overridePct'    => ['required', 'numeric', 'min:1', 'max:100'],
            'overrideReason' => ['required', 'string', 'max:500'],
        ]);

        $oldValues = $this->disbursementValues();
        $oldItemNet = (float) $this->item->net_salary;
        $oldBatchNet = (float) $this->batch->net_total;

        $this->item->update([
            'disbursement_pct'             => (float) $this->overridePct,
            'disbursement_override_reason' => $this->overrideReason,
        ]);

        $this->item->refresh();
        $this->item->recalculateDisbursement();

        $this->item->payrollBatch->update([
            'disbursed_total' => PayrollItem::where('payroll_batch_id', $this->batch->id)
                ->sum('disbursed_amount'),
        ]);

        $this->item->refresh();
        $this->batch->refresh();

        app(PayrollAuditService::class)->recordPayrollChange(
            item: $this->item,
            action: 'disbursement_updated',
            adjustment: null,
            beforeValues: $oldValues,
            afterValues: $this->disbursementValues(),
            oldItemNetSalary: $oldItemNet,
            newItemNetSalary: (float) $this->item->net_salary,
            oldBatchNetTotal: $oldBatchNet,
            newBatchNetTotal: (float) $this->batch->net_total,
            remarks: $this->overrideReason,
        );

        session()->flash('status', "Disbursement updated to {$this->overridePct}% for {$this->item->employee?->full_name} and logged.");
    }

    public function resetDisbursementOverride(): void
    {
        abort_unless($this->batch->isPartial(), 403);
        $this->authorizePayrollEdit();

        $defaultPct = $this->batch->default_disbursement_pct;
        $oldValues = $this->disbursementValues();
        $oldItemNet = (float) $this->item->net_salary;
        $oldBatchNet = (float) $this->batch->net_total;

        $this->item->update([
            'disbursement_pct'             => $defaultPct,
            'disbursement_override_reason' => null,
        ]);

        $this->item->refresh();
        $this->item->recalculateDisbursement();

        $this->item->payrollBatch->update([
            'disbursed_total' => PayrollItem::where('payroll_batch_id', $this->batch->id)
                ->sum('disbursed_amount'),
        ]);

        $this->overridePct    = (string) $defaultPct;
        $this->overrideReason = '';

        $this->item->refresh();
        $this->batch->refresh();

        app(PayrollAuditService::class)->recordPayrollChange(
            item: $this->item,
            action: 'disbursement_reset',
            adjustment: null,
            beforeValues: $oldValues,
            afterValues: $this->disbursementValues(),
            oldItemNetSalary: $oldItemNet,
            newItemNetSalary: (float) $this->item->net_salary,
            oldBatchNetTotal: $oldBatchNet,
            newBatchNetTotal: (float) $this->batch->net_total,
            remarks: 'Reset to batch default.',
        );

        session()->flash('status', "Disbursement reset to batch default ({$defaultPct}%) and logged.");
    }

    // ── recalculate net salary + batch totals ─────────────────────────────────

    private function recalculate(): void
    {
        $this->item->refresh();

        $adjustments = PayrollItemAdjustment::where('payroll_item_id', $this->item->id)->get();

        $additions  = $adjustments->where('type', 'addition')->sum('amount');
        $deductions = $adjustments->where('type', 'deduction')->sum('amount');

        $net = $this->item->gross_salary
            - $this->item->total_deductions
            + $additions
            - $deductions;

        $this->item->update([
            'net_salary'           => max(0, $net),
            'is_manually_adjusted' => $adjustments->isNotEmpty(),
            'adjustment_notes'     => $adjustments->isNotEmpty()
                ? $adjustments->map(fn ($a) => "[{$a->type}] {$a->label}: ₹{$a->amount}" . ($a->note ? " — {$a->note}" : ''))->implode("\n")
                : null,
        ]);

        $this->item->refresh();
        $this->item->recalculateDisbursement();

        $itemIds = PayrollItem::where('payroll_batch_id', $this->batch->id)->pluck('id');

        $adjTotals = PayrollItemAdjustment::whereIn('payroll_item_id', $itemIds)
            ->selectRaw('type, SUM(amount) as total')
            ->groupBy('type')
            ->pluck('total', 'type');

        $this->item->payrollBatch->update([
            'net_total'       => PayrollItem::where('payroll_batch_id', $this->batch->id)->sum('net_salary'),
            'disbursed_total' => PayrollItem::where('payroll_batch_id', $this->batch->id)->sum('disbursed_amount'),
            'deduction_total' => PayrollItem::where('payroll_batch_id', $this->batch->id)->sum('total_deductions')
                               + ($adjTotals['deduction'] ?? 0)
                               - ($adjTotals['addition'] ?? 0),
        ]);
    }

    private function resetAdjustmentForm(): void
    {
        $this->type   = 'addition';
        $this->label  = '';
        $this->amount = '';
        $this->note   = '';
        $this->resetValidation();
    }

    private function authorizePayrollEdit(): void
    {
        abort_unless(
            app(PayrollWorkflowService::class)->canCurrentUserEdit($this->batch),
            403,
            'Only HR before submission or the current payroll approver can edit this batch.',
        );
    }

    private function adjustmentValues(PayrollItemAdjustment $adjustment): array
    {
        return [
            'type' => $adjustment->type,
            'label' => $adjustment->label,
            'amount' => (float) $adjustment->amount,
            'note' => $adjustment->note,
        ];
    }

    private function disbursementValues(): array
    {
        return [
            'disbursement_pct' => (float) $this->item->disbursement_pct,
            'disbursed_amount' => (float) $this->item->disbursed_amount,
            'outstanding_amount' => (float) $this->item->outstanding_amount,
            'disbursement_override_reason' => $this->item->disbursement_override_reason,
        ];
    }

    // ── render ────────────────────────────────────────────────────────────────

    public function render()
    {
        $adjustments = PayrollItemAdjustment::where('payroll_item_id', $this->item->id)
            ->with(['createdBy:id,name', 'updatedBy:id,name', 'workflowStep:id,name,role'])
            ->latest()
            ->get();

        return view('livewire.hr.payroll.item-adjustment', [
            'adjustments' => $adjustments,
            'adjustmentLogs' => $this->item->adjustmentLogs()
                ->with(['actor:id,name', 'workflowStep:id,name,role'])
                ->latest()
                ->get(),
            'canEditPayroll' => app(PayrollWorkflowService::class)->canCurrentUserEdit($this->batch),
        ]);
    }
}

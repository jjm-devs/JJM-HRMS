<?php

namespace App\Livewire\Hr\Payroll;

use App\Models\PayrollBatch;
use App\Models\PayrollItem;
use App\Models\PayrollItemLeaveAdjustment;
use App\Services\Payroll\PayrollAuditService;
use App\Services\Payroll\PayrollWorkflowService;
use Livewire\Component;

class LeaveReview extends Component
{
    public PayrollBatch $batch;

    public PayrollItem $item;

    /** @var array<int, string> keyed by adjustment id */
    public array $classifications = [];

    public function mount(): void
    {
        abort_unless(app(\App\Services\Hr\HrScopeService::class)->canAccessPayrollModule(), 403);

        abort_if($this->batch->locked_at !== null, 403, 'This batch is locked.');
        abort_unless($this->item->payroll_batch_id === $this->batch->id, 404);
        abort_unless(app(PayrollWorkflowService::class)->canCurrentUserEdit($this->batch), 403);

        $this->item->load([
            'employee:id,full_name,employee_code,org_unit_id,department_stream_id',
            'employee.orgUnit:id,name',
            'employee.departmentStream:id,name',
            'leaveAdjustments.leaveApplication:id,start_date,end_date',
        ]);

        foreach ($this->item->leaveAdjustments as $adj) {
            $this->classifications[$adj->id] = $adj->hr_classification ?? $adj->auto_classification;
        }
    }

    public function save(): void
    {
        abort_unless(app(PayrollWorkflowService::class)->canCurrentUserEdit($this->batch), 403);

        $this->validate([
            'classifications' => ['required', 'array'],
            'classifications.*' => ['required', 'in:salary_deduct,leave_bank,exempt'],
        ]);

        $beforeValues = $this->leaveReviewValues();
        $oldItemNet = (float) $this->item->net_salary;
        $oldBatchNet = (float) $this->batch->net_total;

        foreach ($this->item->leaveAdjustments as $adj) {
            $adj->update([
                'hr_classification' => $this->classifications[$adj->id],
            ]);
        }

        // recalculate LWP days and net salary from the updated classifications
        $this->recalculate();

        $this->item->refresh()->load('leaveAdjustments');
        $this->batch->refresh();

        app(PayrollAuditService::class)->recordPayrollChange(
            item: $this->item,
            action: 'leave_review_saved',
            adjustment: null,
            beforeValues: $beforeValues,
            afterValues: $this->leaveReviewValues(),
            oldItemNetSalary: $oldItemNet,
            newItemNetSalary: (float) $this->item->net_salary,
            oldBatchNetTotal: $oldBatchNet,
            newBatchNetTotal: (float) $this->batch->net_total,
            remarks: 'Leave classifications reviewed.',
        );

        session()->flash('status', 'Leave review saved, recalculated, and logged.');
        $this->redirect(route('hr.payroll.batch.detail', $this->batch));
    }

    public function resetToAuto(): void
    {
        abort_unless(app(PayrollWorkflowService::class)->canCurrentUserEdit($this->batch), 403);

        $beforeValues = $this->leaveReviewValues();
        $oldItemNet = (float) $this->item->net_salary;
        $oldBatchNet = (float) $this->batch->net_total;

        foreach ($this->item->leaveAdjustments as $adj) {
            $adj->update(['hr_classification' => null]);
            $this->classifications[$adj->id] = $adj->auto_classification;
        }

        $this->recalculate();
        $this->item->refresh()->load([
            'employee:id,full_name,employee_code',
            'leaveAdjustments.leaveApplication:id,start_date,end_date',
        ]);
        $this->batch->refresh();

        app(PayrollAuditService::class)->recordPayrollChange(
            item: $this->item,
            action: 'leave_review_reset',
            adjustment: null,
            beforeValues: $beforeValues,
            afterValues: $this->leaveReviewValues(),
            oldItemNetSalary: $oldItemNet,
            newItemNetSalary: (float) $this->item->net_salary,
            oldBatchNetTotal: $oldBatchNet,
            newBatchNetTotal: (float) $this->batch->net_total,
            remarks: 'Leave classifications reset to auto.',
        );

        session()->flash('status', 'Classifications reset to auto, recalculated, and logged.');
    }

    public function render()
    {
        return view('livewire.hr.payroll.leave-review');
    }

    // ── recalculation ─────────────────────────────────────────────────────────

    private function recalculate(): void
    {
        $this->item->refresh();
        $this->item->load('leaveAdjustments');

        $lwpDays = $this->item->leaveAdjustments
            ->sum(fn (PayrollItemLeaveAdjustment $adj) => $adj->deductedDays());

        // remove old LWP component row and reinsert with new amount
        $this->item->components()->where('name', 'Leave Without Pay')->delete();

        $grossEarnings = (float) $this->item->gross_salary;
        $divisor = 30; // matches PayrollGenerationService::SALARY_DAYS_DIVISOR

        $lwpDeduction = $lwpDays > 0
            ? round($grossEarnings / $divisor * $lwpDays, 2)
            : 0;

        if ($lwpDeduction > 0) {
            $this->item->components()->create([
                'salary_component_id' => null,
                'name' => 'Leave Without Pay',
                'type' => 'deduction',
                'amount' => $lwpDeduction,
                'calculation_details' => "Gross ₹{$grossEarnings} ÷ {$divisor} days × {$lwpDays} LWP days (HR reviewed)",
                'is_manually_adjusted' => true,
            ]);
        }

        // recalculate totals from components
        $components = $this->item->components()->get();
        $totalDeductions = $components->where('type', 'deduction')->sum('amount');
        $adjustments = $this->item->adjustments()->get();
        $additions = $adjustments->where('type', 'addition')->sum('amount');
        $manualDeductions = $adjustments->where('type', 'deduction')->sum('amount');
        $netSalary = max($grossEarnings - $totalDeductions + $additions - $manualDeductions, 0);

        $this->item->update([
            'leave_without_pay_days' => $lwpDays,
            'lwp_deduction' => $lwpDeduction,
            'total_deductions' => round($totalDeductions, 2),
            'net_salary' => round($netSalary, 2),
        ]);

        $this->item->refresh();
        $this->item->recalculateDisbursement();

        // update batch totals
        $itemIds = $this->batch->items()->pluck('id');
        $adjTotals = \App\Models\PayrollItemAdjustment::whereIn('payroll_item_id', $itemIds)
            ->selectRaw('type, SUM(amount) as total')
            ->groupBy('type')
            ->pluck('total', 'type');

        $this->batch->update([
            'deduction_total' => round(
                $this->batch->items()->sum('total_deductions')
                + ($adjTotals['deduction'] ?? 0)
                - ($adjTotals['addition'] ?? 0),
                2,
            ),
            'net_total' => round($this->batch->items()->sum('net_salary'), 2),
            'disbursed_total' => round($this->batch->items()->sum('disbursed_amount'), 2),
        ]);
    }

    private function leaveReviewValues(): array
    {
        $this->item->loadMissing('leaveAdjustments');

        return $this->item->leaveAdjustments
            ->mapWithKeys(fn (PayrollItemLeaveAdjustment $adjustment) => [
                $adjustment->id => [
                    'auto_classification' => $adjustment->auto_classification,
                    'hr_classification' => $adjustment->hr_classification,
                    'effective_classification' => $adjustment->effectiveClassification(),
                    'leave_days' => (float) $adjustment->leave_days,
                    'leave_type_name' => $adjustment->leave_type_name,
                ],
            ])
            ->all();
    }
}

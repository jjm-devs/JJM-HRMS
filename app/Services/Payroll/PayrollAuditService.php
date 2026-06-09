<?php

namespace App\Services\Payroll;

use App\Models\PayrollAdjustmentLog;
use App\Models\PayrollBatch;
use App\Models\PayrollItem;
use App\Models\PayrollItemAdjustment;
use Illuminate\Support\Facades\Auth;

class PayrollAuditService
{
    public function workflowContext(PayrollBatch $batch): array
    {
        $instance = app(PayrollWorkflowService::class)->workflowInstanceFor($batch);
        $step = $instance?->currentStep;
        $user = Auth::user();
        $role = $step && $user?->hasRole($step->role)
            ? $step->role
            : $user?->primaryPayrollRole();

        return [
            'workflow_instance_id' => $instance?->id,
            'workflow_step_id' => $step?->id,
            'role' => $role,
        ];
    }

    public function recordPayrollChange(
        PayrollItem $item,
        string $action,
        ?PayrollItemAdjustment $adjustment,
        ?array $beforeValues,
        ?array $afterValues,
        float $oldItemNetSalary,
        float $newItemNetSalary,
        float $oldBatchNetTotal,
        float $newBatchNetTotal,
        ?string $remarks = null,
    ): PayrollAdjustmentLog {
        $batch = $item->payrollBatch()->firstOrFail();
        $context = $this->workflowContext($batch);

        return PayrollAdjustmentLog::create([
            'payroll_batch_id' => $batch->id,
            'payroll_item_id' => $item->id,
            'employee_id' => $item->employee_id,
            'payroll_item_adjustment_id' => $adjustment?->id,
            'workflow_instance_id' => $context['workflow_instance_id'],
            'workflow_step_id' => $context['workflow_step_id'],
            'actor_id' => Auth::id(),
            'role' => $context['role'],
            'action' => $action,
            'before_values' => $beforeValues,
            'after_values' => $afterValues,
            'old_item_net_salary' => $oldItemNetSalary,
            'new_item_net_salary' => $newItemNetSalary,
            'old_batch_net_total' => $oldBatchNetTotal,
            'new_batch_net_total' => $newBatchNetTotal,
            'remarks' => $remarks,
        ]);
    }
}

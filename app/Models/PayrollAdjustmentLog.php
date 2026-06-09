<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayrollAdjustmentLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'payroll_batch_id',
        'payroll_item_id',
        'employee_id',
        'payroll_item_adjustment_id',
        'workflow_instance_id',
        'workflow_step_id',
        'actor_id',
        'role',
        'action',
        'before_values',
        'after_values',
        'old_item_net_salary',
        'new_item_net_salary',
        'old_batch_net_total',
        'new_batch_net_total',
        'remarks',
    ];

    protected function casts(): array
    {
        return [
            'before_values' => 'array',
            'after_values' => 'array',
            'old_item_net_salary' => 'decimal:2',
            'new_item_net_salary' => 'decimal:2',
            'old_batch_net_total' => 'decimal:2',
            'new_batch_net_total' => 'decimal:2',
        ];
    }

    public function payrollBatch(): BelongsTo
    {
        return $this->belongsTo(PayrollBatch::class);
    }

    public function payrollItem(): BelongsTo
    {
        return $this->belongsTo(PayrollItem::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function adjustment(): BelongsTo
    {
        return $this->belongsTo(PayrollItemAdjustment::class, 'payroll_item_adjustment_id')
            ->withTrashed();
    }

    public function workflowInstance(): BelongsTo
    {
        return $this->belongsTo(WorkflowInstance::class);
    }

    public function workflowStep(): BelongsTo
    {
        return $this->belongsTo(WorkflowStep::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}

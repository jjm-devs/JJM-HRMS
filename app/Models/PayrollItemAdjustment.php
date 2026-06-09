<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PayrollItemAdjustment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'payroll_item_id',
        'type',
        'label',
        'amount',
        'note',
        'created_by',
        'workflow_instance_id',
        'workflow_step_id',
        'role',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
        ];
    }

    public function payrollItem(): BelongsTo
    {
        return $this->belongsTo(PayrollItem::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function workflowInstance(): BelongsTo
    {
        return $this->belongsTo(WorkflowInstance::class);
    }

    public function workflowStep(): BelongsTo
    {
        return $this->belongsTo(WorkflowStep::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(PayrollAdjustmentLog::class);
    }

    public function isAddition(): bool
    {
        return $this->type === 'addition';
    }

    public function isDeduction(): bool
    {
        return $this->type === 'deduction';
    }
}

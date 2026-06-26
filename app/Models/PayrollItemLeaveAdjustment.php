<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayrollItemLeaveAdjustment extends Model
{
    use HasFactory;

    protected $fillable = [
        'payroll_item_id',
        'leave_application_id',
        'leave_days',
        'deductible_days',
        'auto_classification',
        'hr_classification',
        'leave_type_name',
        'leave_type_is_paid',
        'had_sufficient_balance',
    ];

    protected function casts(): array
    {
        return [
            'leave_days' => 'decimal:1',
            'deductible_days' => 'decimal:2',
            'leave_type_is_paid' => 'boolean',
            'had_sufficient_balance' => 'boolean',
        ];
    }

    /**
     * The effective classification — HR override takes precedence over auto.
     */
    public function effectiveClassification(): string
    {
        return $this->hr_classification ?? $this->auto_classification;
    }

    public function isSalaryDeduct(): bool
    {
        return $this->effectiveClassification() === 'salary_deduct';
    }

    /**
     * Days actually deducted as Leave Without Pay for this leave.
     *
     * - Not salary-deduct (banked/exempt) → 0.
     * - Auto excess that HR left as-is → the computed excess (deductible_days).
     * - HR forcing salary_deduct on an otherwise-covered leave → the whole leave.
     */
    public function deductedDays(): float
    {
        if (! $this->isSalaryDeduct()) {
            return 0.0;
        }

        if ($this->hr_classification === 'salary_deduct' && $this->auto_classification !== 'salary_deduct') {
            return (float) $this->leave_days;
        }

        return (float) $this->deductible_days;
    }

    public function payrollItem(): BelongsTo
    {
        return $this->belongsTo(PayrollItem::class);
    }

    public function leaveApplication(): BelongsTo
    {
        return $this->belongsTo(LeaveApplication::class);
    }
}

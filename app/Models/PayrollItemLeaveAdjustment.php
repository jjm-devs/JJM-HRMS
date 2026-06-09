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

    public function payrollItem(): BelongsTo
    {
        return $this->belongsTo(PayrollItem::class);
    }

    public function leaveApplication(): BelongsTo
    {
        return $this->belongsTo(LeaveApplication::class);
    }
}

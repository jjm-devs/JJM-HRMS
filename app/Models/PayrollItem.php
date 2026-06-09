<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PayrollItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'payroll_batch_id',
        'employee_id',
        'basic_salary',
        'gross_salary',
        'total_deductions',
        'net_salary',
        'disbursement_pct',
        'disbursed_amount',
        'outstanding_amount',
        'disbursement_override_reason',
        'attendance_days',
        'leave_without_pay_days',
        'lwp_deduction',
        'is_manually_adjusted',
        'adjustment_notes',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'is_manually_adjusted' => 'boolean',
            'disbursement_pct'     => 'decimal:2',
            'disbursed_amount'     => 'decimal:2',
            'outstanding_amount'   => 'decimal:2',
        ];
    }

    // ── relationships ─────────────────────────────────────────────────────────

    public function payrollBatch(): BelongsTo
    {
        return $this->belongsTo(PayrollBatch::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function components(): HasMany
    {
        return $this->hasMany(PayrollItemComponent::class);
    }

    public function leaveAdjustments(): HasMany
    {
        return $this->hasMany(PayrollItemLeaveAdjustment::class);
    }

    public function adjustments(): HasMany
    {
        return $this->hasMany(PayrollItemAdjustment::class);
    }

    public function adjustmentLogs(): HasMany
    {
        return $this->hasMany(PayrollAdjustmentLog::class);
    }

    public function payslip(): HasOne
    {
        return $this->hasOne(Payslip::class);
    }

    // ── disbursement helpers ──────────────────────────────────────────────────

    public function isPartiallyDisbursed(): bool
    {
        return $this->outstanding_amount > 0;
    }

    public function hasCustomDisbursementPct(): bool
    {
        return $this->disbursement_override_reason !== null;
    }

    /**
     * Recalculate disbursed_amount and outstanding_amount from net_salary and pct.
     * Call this after net_salary changes (adjustment, leave review) or pct changes.
     */
    public function recalculateDisbursement(): void
    {
        $disbursed   = round($this->net_salary * $this->disbursement_pct / 100, 2);
        $outstanding = round($this->net_salary - $disbursed, 2);

        $this->update([
            'disbursed_amount'   => $disbursed,
            'outstanding_amount' => $outstanding,
        ]);
    }
}

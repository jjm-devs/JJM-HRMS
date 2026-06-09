<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class PayrollBatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'batch_number',
        'batch_type',
        'parent_batch_id',
        'default_disbursement_pct',
        'period_from',
        'period_to',
        'payment_date',
        'org_unit_id',
        'generated_by',
        'approved_by',
        'gross_total',
        'deduction_total',
        'net_total',
        'disbursed_total',
        'status',
        'approved_at',
        'locked_at',
    ];

    protected function casts(): array
    {
        return [
            'period_from'              => 'date',
            'period_to'                => 'date',
            'payment_date'             => 'date',
            'approved_at'              => 'datetime',
            'locked_at'                => 'datetime',
            'default_disbursement_pct' => 'decimal:2',
        ];
    }

    // ── relationships ─────────────────────────────────────────────────────────

    public function generatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function orgUnit(): BelongsTo
    {
        return $this->belongsTo(OrgUnit::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PayrollItem::class);
    }

    public function workflowInstances(): MorphMany
    {
        return $this->morphMany(WorkflowInstance::class, 'workflowable');
    }

    public function workflowInstance(): MorphOne
    {
        return $this->morphOne(WorkflowInstance::class, 'workflowable')->latestOfMany();
    }

    public function adjustmentLogs(): HasMany
    {
        return $this->hasMany(PayrollAdjustmentLog::class);
    }

    // parent batch — only set on arrear batches
    public function parentBatch(): BelongsTo
    {
        return $this->belongsTo(PayrollBatch::class, 'parent_batch_id');
    }

    // arrear batch(es) generated from this batch — only on partial batches
    public function arrearBatches(): HasMany
    {
        return $this->hasMany(PayrollBatch::class, 'parent_batch_id');
    }

    // ── type helpers ──────────────────────────────────────────────────────────

    public function isRegular(): bool
    {
        return $this->batch_type === 'regular';
    }

    public function isPartial(): bool
    {
        return $this->batch_type === 'partial';
    }

    public function isArrear(): bool
    {
        return $this->batch_type === 'arrear';
    }

    public function hasArrear(): bool
    {
        return $this->arrearBatches()->exists();
    }

    // ── status helpers ────────────────────────────────────────────────────────

    public function isLocked(): bool
    {
        return $this->locked_at !== null;
    }

    public function isDisbursed(): bool
    {
        return $this->status === 'disbursed';
    }

    // ── label helpers ─────────────────────────────────────────────────────────

    public function periodLabel(): string
    {
        return $this->period_from->format('d M Y').' – '.$this->period_to->format('d M Y');
    }

    public function typeLabel(): string
    {
        return match($this->batch_type) {
            'partial' => 'Partial',
            'arrear'  => 'Arrear',
            default   => 'Regular',
        };
    }

    public function statusLabel(): string
    {
        return match($this->status) {
            'draft' => 'Draft',
            'returned' => 'Returned to HR',
            'pending' => 'Pending Approval',
            'approved' => 'Approved',
            'locked' => 'Locked',
            'disbursed' => 'Disbursed',
            default => str($this->status)->replace('_', ' ')->title()->toString(),
        };
    }
}

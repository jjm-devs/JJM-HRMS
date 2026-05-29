<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PayrollBatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'batch_number',
        'month',
        'year',
        'org_unit_id',
        'generated_by',
        'approved_by',
        'gross_total',
        'deduction_total',
        'net_total',
        'status',
        'approved_at',
        'locked_at',
    ];

    protected function casts(): array
    {
        return [
            'approved_at' => 'datetime',
            'locked_at' => 'datetime',
        ];
    }

    public function generatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PayrollItem::class);
    }
}

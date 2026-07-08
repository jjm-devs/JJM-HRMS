<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class TransferRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'from_org_unit_id',
        'to_org_unit_id',
        'initiated_by',
        'transfer_type',
        'reason',
        'requested_date',
        'effective_date',
        'status',
        'remarks',
    ];

    protected function casts(): array
    {
        return [
            'requested_date' => 'date',
            'effective_date' => 'date',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function fromOrgUnit(): BelongsTo
    {
        return $this->belongsTo(OrgUnit::class, 'from_org_unit_id');
    }

    public function toOrgUnit(): BelongsTo
    {
        return $this->belongsTo(OrgUnit::class, 'to_org_unit_id');
    }

    public function initiatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'initiated_by');
    }

    public function order(): HasOne
    {
        return $this->hasOne(TransferOrder::class);
    }

    public function relievingRecord(): HasOne
    {
        return $this->hasOne(RelievingRecord::class);
    }

    public function joiningRecord(): HasOne
    {
        return $this->hasOne(JoiningRecord::class);
    }
}

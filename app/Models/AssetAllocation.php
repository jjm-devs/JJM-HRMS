<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetAllocation extends Model
{
    use HasFactory;

    protected $fillable = [
        'asset_id',
        'employee_id',
        'allocated_by',
        'allocated_at',
        'returned_at',
        'condition_on_issue',
        'condition_on_return',
        'remarks',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'allocated_at' => 'datetime',
            'returned_at' => 'datetime',
        ];
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function allocatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'allocated_by');
    }
}

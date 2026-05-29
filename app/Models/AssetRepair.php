<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetRepair extends Model
{
    use HasFactory;

    protected $fillable = [
        'asset_id',
        'reported_by',
        'vendor_name',
        'issue_description',
        'repair_notes',
        'estimated_cost',
        'actual_cost',
        'reported_at',
        'resolved_at',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'reported_at' => 'datetime',
            'resolved_at' => 'datetime',
        ];
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }
}

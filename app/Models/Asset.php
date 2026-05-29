<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Asset extends Model
{
    use HasFactory;

    protected $fillable = [
        'asset_category_id',
        'asset_code',
        'name',
        'serial_number',
        'description',
        'purchase_date',
        'purchase_value',
        'condition',
        'current_status',
        'location',
        'metadata',
    ];

    public function assetCategory(): BelongsTo
    {
        return $this->belongsTo(AssetCategory::class);
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(AssetAllocation::class);
    }

    public function transfers(): HasMany
    {
        return $this->hasMany(AssetTransfer::class);
    }

    public function returns(): HasMany
    {
        return $this->hasMany(AssetReturn::class);
    }

    public function repairs(): HasMany
    {
        return $this->hasMany(AssetRepair::class);
    }
}

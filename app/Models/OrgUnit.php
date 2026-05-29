<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrgUnit extends Model
{
    use HasFactory;

    public const TYPES = [
        'department' => 'Department',
        'head_office' => 'Head Office',
        'zone' => 'Zone',
        'circle' => 'Circle',
        'division' => 'Division',
        'sub_division' => 'Sub Division',
        'section' => 'Section',
        'office' => 'Office',
    ];

    protected $fillable = [
        'parent_id',
        'name',
        'code',
        'type',
        'district_id',
        'address',
        'sort_order',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'district_id' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function hrScopeAssignments(): HasMany
    {
        return $this->hasMany(HrScopeAssignment::class);
    }

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }
}

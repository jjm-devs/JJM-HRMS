<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DepartmentStream extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'description',
        'status',
    ];

    public function hrScopeAssignments(): HasMany
    {
        return $this->hasMany(HrScopeAssignment::class);
    }

    public function orgUnits(): BelongsToMany
    {
        return $this->belongsToMany(OrgUnit::class)->withTimestamps();
    }

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }
}

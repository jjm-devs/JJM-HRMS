<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Designation extends Model
{
    use HasFactory;

    protected $fillable = [
        'cadre_id',
        'department_stream_id',
        'name',
        'code',
        'level',
        'description',
        'status',
    ];

    public function cadre(): BelongsTo
    {
        return $this->belongsTo(Cadre::class);
    }

    public function departmentStream(): BelongsTo
    {
        return $this->belongsTo(DepartmentStream::class);
    }

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }
}

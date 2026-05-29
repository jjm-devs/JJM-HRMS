<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PerformanceGrade extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'min_score',
        'max_score',
        'description',
        'status',
    ];

    public function reviews(): HasMany
    {
        return $this->hasMany(PerformanceReview::class);
    }
}

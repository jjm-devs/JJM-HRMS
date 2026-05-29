<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class EmployeeGoal extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'performance_cycle_id',
        'title',
        'description',
        'weightage',
        'target_value',
        'achieved_value',
        'status',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function performanceCycle(): BelongsTo
    {
        return $this->belongsTo(PerformanceCycle::class);
    }

    public function review(): HasOne
    {
        return $this->hasOne(PerformanceReview::class);
    }
}

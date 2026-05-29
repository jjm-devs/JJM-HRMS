<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PerformanceReview extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_goal_id',
        'performance_grade_id',
        'reviewed_by',
        'self_score',
        'reviewer_score',
        'final_score',
        'self_remarks',
        'reviewer_remarks',
        'status',
    ];

    public function employeeGoal(): BelongsTo
    {
        return $this->belongsTo(EmployeeGoal::class);
    }

    public function performanceGrade(): BelongsTo
    {
        return $this->belongsTo(PerformanceGrade::class);
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}

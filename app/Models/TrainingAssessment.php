<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrainingAssessment extends Model
{
    use HasFactory;

    protected $fillable = [
        'training_enrollment_id',
        'score',
        'max_score',
        'result',
        'remarks',
        'status',
    ];

    public function trainingEnrollment(): BelongsTo
    {
        return $this->belongsTo(TrainingEnrollment::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class CandidateApplication extends Model
{
    use HasFactory;

    protected $fillable = [
        'candidate_id',
        'vacancy_id',
        'application_number',
        'reservation_category',
        'status',
        'submitted_at',
        'remarks',
    ];

    protected function casts(): array
    {
        return [
            'submitted_at' => 'datetime',
        ];
    }

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(Candidate::class);
    }

    public function vacancy(): BelongsTo
    {
        return $this->belongsTo(Vacancy::class);
    }

    public function stageResults(): HasMany
    {
        return $this->hasMany(ApplicationStageResult::class);
    }

    public function interviewSchedules(): HasMany
    {
        return $this->hasMany(InterviewSchedule::class);
    }

    public function offerLetter(): HasOne
    {
        return $this->hasOne(OfferLetter::class);
    }

    public function joiningRequest(): HasOne
    {
        return $this->hasOne(JoiningRequest::class);
    }
}

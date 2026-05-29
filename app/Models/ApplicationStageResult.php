<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApplicationStageResult extends Model
{
    use HasFactory;

    protected $fillable = [
        'candidate_application_id',
        'recruitment_stage_id',
        'score',
        'remarks',
        'status',
        'evaluated_by',
        'evaluated_at',
    ];

    public function candidateApplication(): BelongsTo
    {
        return $this->belongsTo(CandidateApplication::class);
    }

    public function recruitmentStage(): BelongsTo
    {
        return $this->belongsTo(RecruitmentStage::class);
    }
}

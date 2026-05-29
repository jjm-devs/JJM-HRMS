<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MeritListItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'merit_list_id',
        'candidate_application_id',
        'rank',
        'score',
        'category',
        'status',
        'remarks',
    ];

    public function meritList(): BelongsTo
    {
        return $this->belongsTo(MeritList::class);
    }

    public function candidateApplication(): BelongsTo
    {
        return $this->belongsTo(CandidateApplication::class);
    }
}

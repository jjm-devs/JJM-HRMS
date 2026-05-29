<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OfferLetter extends Model
{
    use HasFactory;

    protected $fillable = [
        'candidate_application_id',
        'document_id',
        'offer_number',
        'issued_at',
        'accepted_at',
        'status',
        'remarks',
    ];

    protected function casts(): array
    {
        return [
            'issued_at' => 'datetime',
            'accepted_at' => 'datetime',
        ];
    }

    public function candidateApplication(): BelongsTo
    {
        return $this->belongsTo(CandidateApplication::class);
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }
}

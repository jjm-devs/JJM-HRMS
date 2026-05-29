<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceBookEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'service_book_id',
        'document_id',
        'event_type',
        'title',
        'description',
        'effective_date',
        'created_by',
        'verified_by',
        'verified_at',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'effective_date' => 'date',
            'verified_at' => 'datetime',
        ];
    }

    public function serviceBook(): BelongsTo
    {
        return $this->belongsTo(ServiceBook::class);
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JoiningRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'transfer_request_id',
        'joined_by',
        'joined_at',
        'joining_remarks',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'joined_at' => 'datetime',
        ];
    }

    public function transferRequest(): BelongsTo
    {
        return $this->belongsTo(TransferRequest::class);
    }

    public function joinedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'joined_by');
    }
}

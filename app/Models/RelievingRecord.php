<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RelievingRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'transfer_request_id',
        'relieved_by',
        'relieved_at',
        'relieving_remarks',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'relieved_at' => 'datetime',
        ];
    }

    public function transferRequest(): BelongsTo
    {
        return $this->belongsTo(TransferRequest::class);
    }

    public function relievedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'relieved_by');
    }
}

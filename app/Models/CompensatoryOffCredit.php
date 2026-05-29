<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompensatoryOffCredit extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'attendance_log_id',
        'earned_on',
        'expires_on',
        'used_at',
        'reason',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'earned_on' => 'date',
            'expires_on' => 'date',
            'used_at' => 'datetime',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}

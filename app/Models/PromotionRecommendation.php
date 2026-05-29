<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PromotionRecommendation extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'recommended_by',
        'approved_by',
        'from_designation_id',
        'to_designation_id',
        'recommended_at',
        'approved_at',
        'status',
        'remarks',
    ];

    protected function casts(): array
    {
        return [
            'recommended_at' => 'datetime',
            'approved_at' => 'datetime',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function recommendedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recommended_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeavePolicy extends Model
{
    use HasFactory;

    protected $fillable = [
        'leave_type_id',
        'employment_type_id',
        'gender',
        'service_type',
        'annual_quota',
        'max_days_per_request',
        'carry_forward_limit',
        'encashable_limit',
        'rules',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'annual_quota' => 'decimal:2',
            'max_days_per_request' => 'decimal:2',
            'carry_forward_limit' => 'decimal:2',
            'encashable_limit' => 'decimal:2',
            'rules' => 'array',
        ];
    }

    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(LeaveType::class);
    }

    public function employmentType(): BelongsTo
    {
        return $this->belongsTo(EmploymentType::class);
    }
}

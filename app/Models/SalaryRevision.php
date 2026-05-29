<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalaryRevision extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'approved_by',
        'old_basic_salary',
        'new_basic_salary',
        'effective_from',
        'approved_at',
        'reason',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'effective_from' => 'date',
            'approved_at' => 'datetime',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}

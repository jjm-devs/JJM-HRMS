<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Arrear extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'salary_revision_id',
        'payroll_batch_id',
        'type',
        'description',
        'from_date',
        'to_date',
        'amount',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'from_date' => 'date',
            'to_date' => 'date',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PayLevel extends Model
{
    use HasFactory;

    protected $fillable = [
        'pay_matrix_id',
        'name',
        'code',
        'level_order',
        'min_basic',
        'max_basic',
        'increment_amount',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'level_order' => 'integer',
            'min_basic' => 'decimal:2',
            'max_basic' => 'decimal:2',
            'increment_amount' => 'decimal:2',
        ];
    }

    public function payMatrix(): BelongsTo
    {
        return $this->belongsTo(PayMatrix::class);
    }

    public function salaryStructures(): HasMany
    {
        return $this->hasMany(SalaryStructure::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PayMatrix extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'description',
        'effective_from',
        'effective_to',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'effective_from' => 'date',
            'effective_to' => 'date',
        ];
    }

    public function payLevels(): HasMany
    {
        return $this->hasMany(PayLevel::class);
    }
}

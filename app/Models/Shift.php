<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Shift extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'start_time',
        'end_time',
        'grace_minutes',
        'half_day_minutes',
        'full_day_minutes',
        'status',
    ];

    public function rosters(): HasMany
    {
        return $this->hasMany(Roster::class);
    }
}

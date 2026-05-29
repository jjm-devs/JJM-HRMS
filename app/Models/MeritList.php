<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MeritList extends Model
{
    use HasFactory;

    protected $fillable = [
        'vacancy_id',
        'title',
        'list_number',
        'published_at',
        'status',
        'remarks',
    ];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
        ];
    }

    public function vacancy(): BelongsTo
    {
        return $this->belongsTo(Vacancy::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(MeritListItem::class);
    }
}

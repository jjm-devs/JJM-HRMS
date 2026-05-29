<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vacancy extends Model
{
    use HasFactory;

    protected $fillable = [
        'org_unit_id',
        'designation_id',
        'vacancy_code',
        'title',
        'description',
        'number_of_posts',
        'reservation_breakup',
        'status',
        'published_at',
        'closed_at',
    ];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    public function orgUnit(): BelongsTo
    {
        return $this->belongsTo(OrgUnit::class);
    }

    public function applications(): HasMany
    {
        return $this->hasMany(CandidateApplication::class);
    }

    public function meritLists(): HasMany
    {
        return $this->hasMany(MeritList::class);
    }
}

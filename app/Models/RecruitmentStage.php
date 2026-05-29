<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RecruitmentStage extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'sequence',
        'description',
        'status',
    ];

    public function results(): HasMany
    {
        return $this->hasMany(ApplicationStageResult::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkflowDefinition extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'module',
        'description',
        'status',
    ];

    public function steps(): HasMany
    {
        return $this->hasMany(WorkflowStep::class);
    }

    public function instances(): HasMany
    {
        return $this->hasMany(WorkflowInstance::class);
    }
}

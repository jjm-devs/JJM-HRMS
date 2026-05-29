<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GrievanceCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'description',
        'sla_hours',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'sla_hours' => 'integer',
        ];
    }

    public function grievances(): HasMany
    {
        return $this->hasMany(Grievance::class);
    }
}

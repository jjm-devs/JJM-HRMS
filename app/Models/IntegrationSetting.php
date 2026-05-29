<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IntegrationSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'provider',
        'base_url',
        'credentials',
        'configuration',
        'enabled',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'credentials' => 'encrypted:array',
            'configuration' => 'array',
            'enabled' => 'boolean',
        ];
    }
}

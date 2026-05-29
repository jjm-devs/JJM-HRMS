<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NotificationTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'channel',
        'subject',
        'body',
        'variables',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'variables' => 'array',
        ];
    }

    public function logs(): HasMany
    {
        return $this->hasMany(NotificationLog::class);
    }
}

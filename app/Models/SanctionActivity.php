<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SanctionActivity extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'staff_category',
        'status',
    ];

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}

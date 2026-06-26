<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HrPolicy extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'category',
        'file_path',
        'is_active',
        'uploaded_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public const CATEGORIES = [
        'leave'         => 'Leave Policy',
        'service_rules' => 'Service Rules',
        'conduct'       => 'Conduct Rules',
        'general'       => 'General',
    ];
}
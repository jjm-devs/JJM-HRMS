<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Role extends Model
{
    use HasFactory;

    public const PAYROLL_ROLE_CODES = [
        'hr',
        'spo_fm',
        'deputy_md',
        'fa',
        'addt_chief_eng',
        'addt_md',
        'md',
    ];

    public const PAYROLL_APPROVER_ROLE_CODES = [
        'spo_fm',
        'deputy_md',
        'fa',
        'addt_chief_eng',
        'addt_md',
        'md',
    ];

    public const LABELS = [
        'hr' => 'HR',
        'spo_fm' => 'SPO FM',
        'deputy_md' => 'Deputy MD',
        'fa' => 'FA',
        'addt_chief_eng' => 'Addt Chief Eng',
        'addt_md' => 'Addt. MD',
        'md' => 'MD',
    ];

    protected $fillable = [
        'name',
        'code',
        'description',
        'status',
    ];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withTimestamps();
    }

    public static function labelFor(?string $code): string
    {
        if ($code === null || $code === '') {
            return 'Employee';
        }

        return self::LABELS[$code] ?? str($code)->replace('_', ' ')->title()->toString();
    }
}

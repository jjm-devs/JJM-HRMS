<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'mobile',
        'password',
        'is_admin',
        'is_hr',
        'status',
        'must_change_password',
        'signature_path',
        'signature_disk',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
            'is_hr' => 'boolean',
            'must_change_password' => 'boolean',
        ];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $panel->getId() === 'admin' && $this->is_admin === true;
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class)->withTimestamps();
    }

    public function hasRole(string $code): bool
    {
        if ($code === 'hr' && $this->is_hr) {
            return true;
        }

        if ($this->relationLoaded('roles')) {
            return $this->roles
                ->where('status', 'active')
                ->contains('code', $code);
        }

        return $this->roles()
            ->where('code', $code)
            ->where('status', 'active')
            ->exists();
    }

    public function hasAnyRole(array $codes): bool
    {
        foreach ($codes as $code) {
            if ($this->hasRole($code)) {
                return true;
            }
        }

        return false;
    }

    public function canAccessPayrollWorkflow(): bool
    {
        return $this->is_hr || $this->hasAnyRole(Role::PAYROLL_ROLE_CODES);
    }

    public function primaryPayrollRole(): ?string
    {
        foreach (Role::PAYROLL_ROLE_CODES as $role) {
            if ($this->hasRole($role)) {
                return $role;
            }
        }

        return null;
    }

    public function hrScopeAssignments(): HasMany
    {
        return $this->hasMany(HrScopeAssignment::class);
    }

    public function employee(): HasOne
    {
        return $this->hasOne(Employee::class);
    }

    public function employeeManagerAssignments(): HasMany
    {
        return $this->hasMany(EmployeeManagerAssignment::class);
    }

    public function loginHistories(): HasMany
    {
        return $this->hasMany(LoginHistory::class);
    }

    public function deviceSessions(): HasMany
    {
        return $this->hasMany(DeviceSession::class);
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(UserNotification::class);
    }
}

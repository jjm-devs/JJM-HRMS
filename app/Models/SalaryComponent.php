<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SalaryComponent extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'type',
        'calculation_type',
        'default_amount',
        'formula',
        'is_taxable',
        'is_deduction',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'default_amount' => 'decimal:2',
            'is_taxable' => 'boolean',
            'is_deduction' => 'boolean',
        ];
    }

    public function employeeSalaryComponents(): HasMany
    {
        return $this->hasMany(EmployeeSalaryComponent::class);
    }

    public function payrollItemComponents(): HasMany
    {
        return $this->hasMany(PayrollItemComponent::class);
    }
}

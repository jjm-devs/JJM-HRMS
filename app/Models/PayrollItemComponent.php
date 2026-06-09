<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayrollItemComponent extends Model
{
    use HasFactory;

    protected $fillable = [
        'payroll_item_id',
        'salary_component_id',
        'name',
        'type',
        'amount',
        'calculation_details',
        'is_manually_adjusted',
    ];

    protected function casts(): array
    {
        return [
            'is_manually_adjusted' => 'boolean',
        ];
    }

    public function payrollItem(): BelongsTo
    {
        return $this->belongsTo(PayrollItem::class);
    }

    public function salaryComponent(): BelongsTo
    {
        return $this->belongsTo(SalaryComponent::class);
    }
}

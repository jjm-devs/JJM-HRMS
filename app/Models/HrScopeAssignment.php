<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HrScopeAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'org_unit_id',
        'department_stream_id',
        'employment_type_id',
        'include_child_units',
        'can_view',
        'can_create',
        'can_update',
        'can_delete',
        'can_approve',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'include_child_units' => 'boolean',
            'can_view' => 'boolean',
            'can_create' => 'boolean',
            'can_update' => 'boolean',
            'can_delete' => 'boolean',
            'can_approve' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function orgUnit(): BelongsTo
    {
        return $this->belongsTo(OrgUnit::class);
    }

    public function departmentStream(): BelongsTo
    {
        return $this->belongsTo(DepartmentStream::class);
    }

    public function employmentType(): BelongsTo
    {
        return $this->belongsTo(EmploymentType::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Grievance extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'grievance_category_id',
        'ticket_number',
        'subject',
        'description',
        'priority',
        'status',
        'assigned_to',
        'closed_at',
        'resolution_summary',
    ];

    protected function casts(): array
    {
        return [
            'closed_at' => 'datetime',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function grievanceCategory(): BelongsTo
    {
        return $this->belongsTo(GrievanceCategory::class);
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function notes(): HasMany
    {
        return $this->hasMany(GrievanceNote::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(GrievanceAttachment::class);
    }

    public function escalations(): HasMany
    {
        return $this->hasMany(GrievanceEscalation::class);
    }
}

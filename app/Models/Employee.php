<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Employee extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'employee_code',
        'org_unit_id',
        'department_stream_id',
        'employment_type_id',
        'designation_id',
        'cadre_id',
        'full_name',
        'father_name',
        'mother_name',
        'date_of_birth',
        'gender',
        'blood_group',
        'aadhaar_number',
        'pan_number',
        'bank_account_number',
        'bank_ifsc_code',
        'bank_name',
        'bank_branch',
        'joining_date',
        'retirement_date',
        'service_status',
        'remarks',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'joining_date' => 'date',
            'retirement_date' => 'date',
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

    public function designation(): BelongsTo
    {
        return $this->belongsTo(Designation::class);
    }

    public function cadre(): BelongsTo
    {
        return $this->belongsTo(Cadre::class);
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(EmployeeContact::class);
    }

    public function familyMembers(): HasMany
    {
        return $this->hasMany(EmployeeFamilyMember::class);
    }

    public function qualifications(): HasMany
    {
        return $this->hasMany(EmployeeQualification::class);
    }

    public function experiences(): HasMany
    {
        return $this->hasMany(EmployeeExperience::class);
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable');
    }

    public function attendanceLogs(): HasMany
    {
        return $this->hasMany(AttendanceLog::class);
    }

    public function leaveApplications(): HasMany
    {
        return $this->hasMany(LeaveApplication::class);
    }

    public function leaveBalances(): HasMany
    {
        return $this->hasMany(LeaveBalance::class);
    }

    public function payrollItems(): HasMany
    {
        return $this->hasMany(PayrollItem::class);
    }

    public function serviceBook(): HasOne
    {
        return $this->hasOne(ServiceBook::class);
    }

    public function transferRequests(): HasMany
    {
        return $this->hasMany(TransferRequest::class);
    }

    public function postingHistories(): HasMany
    {
        return $this->hasMany(PostingHistory::class);
    }

    public function assetAllocations(): HasMany
    {
        return $this->hasMany(AssetAllocation::class);
    }

    public function grievances(): HasMany
    {
        return $this->hasMany(Grievance::class);
    }

    public function salaryStructures(): HasMany
    {
        return $this->hasMany(SalaryStructure::class);
    }

    public function loanDeductions(): HasMany
    {
        return $this->hasMany(LoanDeduction::class);
    }

    public function arrears(): HasMany
    {
        return $this->hasMany(Arrear::class);
    }

    public function salaryRevisions(): HasMany
    {
        return $this->hasMany(SalaryRevision::class);
    }

    public function trainingEnrollments(): HasMany
    {
        return $this->hasMany(TrainingEnrollment::class);
    }

    public function compensatoryOffCredits(): HasMany
    {
        return $this->hasMany(CompensatoryOffCredit::class);
    }

    public function goals(): HasMany
    {
        return $this->hasMany(EmployeeGoal::class);
    }
}

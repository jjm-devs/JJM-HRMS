<?php

namespace App\Livewire\Employee;

use App\Models\Employee;
use App\Models\LeaveApplication;
use App\Models\Payslip;
use App\Services\Leave\PaidLeaveBankService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Dashboard extends Component
{
    public Employee $employee;

    public function mount(): void
    {
        $this->employee = Auth::user()
            ->employee()
            ->with(['designation', 'orgUnit', 'departmentStream', 'employmentType'])
            ->firstOrFail();
    }

    public function render()
    {
        $now = CarbonImmutable::now();
        $bank = app(PaidLeaveBankService::class);
        $paidUsed = $bank->usedInMonth($this->employee, $now->year, $now->month);
        $latestPayslip = $this->latestPayslip();
        $recentLeaves = $this->employee
            ->leaveApplications()
            ->with('leaveType')
            ->latest('id')
            ->limit(5)
            ->get();

        return view('livewire.employee.dashboard', [
            'paidLeave' => [
                'allowance' => PaidLeaveBankService::MONTHLY_ALLOWANCE,
                'used' => $paidUsed,
                'remaining' => max(PaidLeaveBankService::MONTHLY_ALLOWANCE - $paidUsed, 0),
            ],
            'documentSummary' => [
                'total' => $this->employee->documents()->count(),
                'submitted' => $this->employee->documents()->where('status', 'submitted')->count(),
                'verified' => $this->employee->documents()->where('status', 'verified')->count(),
            ],
            'leaveSummary' => [
                'pending' => $this->employee->leaveApplications()
                    ->whereIn('status', [LeaveApplication::STATUS_SUBMITTED, LeaveApplication::STATUS_UNDER_REVIEW])
                    ->count(),
                'approved' => $this->employee->leaveApplications()
                    ->where('status', LeaveApplication::STATUS_APPROVED)
                    ->count(),
            ],
            'latestPayslip' => $latestPayslip,
            'recentLeaves' => $recentLeaves,
        ]);
    }

    private function latestPayslip(): ?Payslip
    {
        return Payslip::query()
            ->with(['payrollItem.payrollBatch'])
            ->where('status', 'issued')
            ->whereHas('payrollItem', fn ($query) => $query->where('employee_id', $this->employee->id))
            ->latest('generated_at')
            ->latest('id')
            ->first();
    }
}

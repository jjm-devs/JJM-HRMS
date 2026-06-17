<?php

namespace App\Livewire\Employee;

use App\Models\Employee;
use App\Models\LeaveApplication;
use App\Models\LeaveType;
use App\Models\Payslip;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Dashboard extends Component
{
    private const CASUAL_LEAVE_MONTHLY_ALLOWANCE = 2.0;

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
        [$monthStart, $monthEnd] = [CarbonImmutable::now()->startOfMonth(), CarbonImmutable::now()->endOfMonth()];
        $casualUsed = $this->casualLeaveUsed($monthStart, $monthEnd);
        $latestPayslip = $this->latestPayslip();
        $recentLeaves = $this->employee
            ->leaveApplications()
            ->with('leaveType')
            ->latest('id')
            ->limit(5)
            ->get();

        return view('livewire.employee.dashboard', [
            'casualLeave' => [
                'allowance' => self::CASUAL_LEAVE_MONTHLY_ALLOWANCE,
                'used' => $casualUsed,
                'remaining' => max(self::CASUAL_LEAVE_MONTHLY_ALLOWANCE - $casualUsed, 0),
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

    private function casualLeaveUsed(CarbonImmutable $monthStart, CarbonImmutable $monthEnd): float
    {
        $leaveType = $this->casualLeaveType();

        if (! $leaveType) {
            return 0.0;
        }

        return (float) $this->employee
            ->leaveApplications()
            ->where('leave_type_id', $leaveType->id)
            ->whereIn('status', [
                LeaveApplication::STATUS_SUBMITTED,
                LeaveApplication::STATUS_UNDER_REVIEW,
                LeaveApplication::STATUS_APPROVED,
            ])
            ->whereDate('start_date', '<=', $monthEnd->toDateString())
            ->whereDate('end_date', '>=', $monthStart->toDateString())
            ->get()
            ->sum(fn (LeaveApplication $leave): int => $this->overlapDays($leave, $monthStart, $monthEnd));
    }

    private function casualLeaveType(): ?LeaveType
    {
        return LeaveType::query()
            ->where('status', 'active')
            ->where(function ($query): void {
                $query
                    ->where('code', 'CL')
                    ->orWhere('code', 'like', 'CL-%')
                    ->orWhere('name', 'like', '%Casual%');
            })
            ->orderByRaw("CASE WHEN code = 'CL' THEN 0 ELSE 1 END")
            ->first();
    }

    private function overlapDays(LeaveApplication $leave, CarbonImmutable $from, CarbonImmutable $to): int
    {
        $start = CarbonImmutable::parse($leave->start_date)->max($from);
        $end = CarbonImmutable::parse($leave->end_date)->min($to);

        return $start->diffInDays($end) + 1;
    }
}

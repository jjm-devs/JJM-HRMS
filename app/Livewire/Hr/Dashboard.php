<?php

namespace App\Livewire\Hr;

use App\Models\AttendanceLog;
use App\Models\Employee;
use App\Models\Holiday;
use App\Models\LeaveApplication;
use App\Services\Hr\HrScopeService;
use Livewire\Component;

class Dashboard extends Component
{
    public function render()
    {
        $scope = app(HrScopeService::class);
        $today = now()->toDateString();

        $totalEmployees = $scope->applyToEmployeeQuery(
            Employee::query()->where('service_status', 'active')
        )->count();

        $onLeaveToday = $scope->applyToLeaveQuery(
            LeaveApplication::query()
                ->where('status', LeaveApplication::STATUS_APPROVED)
                ->whereDate('start_date', '<=', $today)
                ->whereDate('end_date', '>=', $today)
        )->distinct('employee_id')->count('employee_id');

        $pendingApprovals = $scope->applyToLeaveQuery(
            LeaveApplication::query()->whereIn('status', [
                LeaveApplication::STATUS_SUBMITTED,
                LeaveApplication::STATUS_UNDER_REVIEW,
            ])
        )->count();

        $presentToday = $scope->applyToEmployeeRelatedQuery(
            AttendanceLog::query()
                ->whereDate('attendance_date', $today)
                ->where('status', 'present')
        )->count();
        $attendancePct = $totalEmployees > 0
            ? (int) round($presentToday / $totalEmployees * 100)
            : 0;

        $recentLeaves = $scope->applyToLeaveQuery(
            LeaveApplication::query()->with([
                'employee:id,full_name,employee_code',
                'leaveType:id,name',
            ])
        )->latest('id')->limit(6)->get();

        $upcomingHolidays = Holiday::query()
            ->where('status', 'active')
            ->whereDate('holiday_date', '>=', $today)
            ->orderBy('holiday_date')
            ->limit(5)
            ->get();

        return view('livewire.hr.dashboard', [
            'stats' => [
                'total_employees' => $totalEmployees,
                'on_leave_today' => $onLeaveToday,
                'pending_approvals' => $pendingApprovals,
                'attendance_pct' => $attendancePct,
                'present_today' => $presentToday,
            ],
            'recentLeaves' => $recentLeaves,
            'upcomingHolidays' => $upcomingHolidays,
        ]);
    }
}

<?php

namespace App\Livewire\Hr\Attendance;

use App\Models\Employee;
use App\Models\Holiday;
use App\Models\LeaveApplication;
use App\Models\LeaveApplicationDay;
use App\Models\LeaveType;
use App\Services\Hr\HrScopeService;
use Carbon\CarbonImmutable;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Livewire\Component;

class Index extends Component
{
    public string $activeTab = 'calendar';

    // Calendar tab uses a month picker
    public string $month = '';

    // Leave register tab uses a date range
    public string $dateFrom = '';
    public string $dateTo = '';

    public string $search = '';
    public string $filterLeaveType = '';
    public ?int $editingLeaveId = null;

    public array $leaveForm = [
        'employee_id' => '',
        'leave_type_id' => '',
        'start_date' => '',
        'end_date' => '',
        'reason' => '',
        'contact_during_leave' => '',
        'status' => 'approved',
    ];

    private HrScopeService $scope;

    public function mount(?string $activeTab = null): void
    {
        $this->month = now()->format('Y-m');
        $this->dateFrom = now()->subMonthNoOverflow()->setDay(25)->format('Y-m-d');
        $this->dateTo = now()->setDay(25)->format('Y-m-d');

        if ($activeTab && array_key_exists($activeTab, $this->tabs())) {
            $this->activeTab = $activeTab;
        }

        $this->resetLeaveForm();
    }

    public function setActiveTab(string $tab): void
    {
        if (array_key_exists($tab, $this->tabs())) {
            $this->activeTab = $tab;
        }
    }

    public function saveLeaveRecord(): void
    {
        $data = $this->validateLeaveForm();
        $data['total_days'] = $this->totalLeaveDays($data['start_date'], $data['end_date']);

        if ($data['status'] === LeaveApplication::STATUS_APPROVED) {
            $data['approved_by'] = Auth::id();
            $data['approved_at'] = now();
        } else {
            $data['approved_by'] = null;
            $data['approved_at'] = null;
        }

        if ($this->editingLeaveId) {
            $leave = LeaveApplication::query()->whereKey($this->editingLeaveId)->firstOrFail();
            $leave->update($data);
            session()->flash('leave_status', 'Leave record updated successfully.');
        } else {
            $data = $this->manualLeaveSourceData($data);
            $leave = LeaveApplication::query()->create($data);
            session()->flash('leave_status', 'Leave record added successfully.');
        }

        $this->syncLeaveDays($leave);
        $this->resetLeaveForm();
    }

    public function editLeaveRecord(int $leaveId): void
    {
        $leave = LeaveApplication::query()->whereKey($leaveId)->firstOrFail();

        $this->activeTab = 'leave_register';
        $this->editingLeaveId = $leave->id;
        $this->leaveForm = [
            'employee_id' => (string) $leave->employee_id,
            'leave_type_id' => (string) $leave->leave_type_id,
            'start_date' => $leave->start_date?->format('Y-m-d') ?? '',
            'end_date' => $leave->end_date?->format('Y-m-d') ?? '',
            'reason' => $leave->reason ?? '',
            'contact_during_leave' => $leave->contact_during_leave ?? '',
            'status' => $leave->status,
        ];
    }

    public function cancelLeaveRecord(int $leaveId): void
    {
        $leave = LeaveApplication::query()->whereKey($leaveId)->firstOrFail();
        $leave->update([
            'status' => LeaveApplication::STATUS_CANCELLED,
            'approval_remarks' => 'Cancelled by HR.',
        ]);
        $leave->days()->update([
            'status' => LeaveApplication::STATUS_CANCELLED,
        ]);

        if ($this->editingLeaveId === $leaveId) {
            $this->resetLeaveForm();
        }

        session()->flash('leave_status', 'Leave record cancelled successfully.');
    }

    public function deleteLeaveRecord(int $leaveId): void
    {
        $this->cancelLeaveRecord($leaveId);
    }

    public function resetLeaveForm(): void
    {
        $this->editingLeaveId = null;
        $this->resetErrorBag();
        $today = now()->format('Y-m-d');

        $this->leaveForm = [
            'employee_id' => '',
            'leave_type_id' => '',
            'start_date' => $today,
            'end_date' => $today,
            'reason' => '',
            'contact_during_leave' => '',
            'status' => LeaveApplication::STATUS_APPROVED,
        ];
    }

    public function render()
    {
        $this->scope = app(HrScopeService::class);

        [$calendarStart, $calendarEnd] = $this->calendarMonthRange();
        [$registerFrom, $registerTo] = $this->registerDateRange();

        $holidaysByDate = $this->holidaysByDate($calendarStart, $calendarEnd);
        $leaveDaysByDate = $this->leaveDaysByDate($calendarStart, $calendarEnd);
        $leaveRecords = $this->leaveRecords($registerFrom, $registerTo);
        $leaveRequestSummary = $this->leaveRequestSummary();

        return view('livewire.hr.attendance.index', [
            'calendarDays' => $this->calendarDays($calendarStart, $calendarEnd, $holidaysByDate, $leaveDaysByDate),
            'employeeOptions' => $this->employeeOptions(),
            'leaveRecords' => $leaveRecords,
            'leaveRequestSummary' => $leaveRequestSummary,
            'leaveSummary' => $this->leaveSummary($leaveRecords),
            'leaveStatusOptions' => $this->leaveStatusOptions(),
            'leaveTypeOptions' => $this->leaveTypeOptions(),
            'calendarStart' => $calendarStart,
            'calendarEnd' => $calendarEnd,
            'registerFrom' => $registerFrom,
            'registerTo' => $registerTo,
            'summary' => $this->calendarSummary($calendarStart, $holidaysByDate, $leaveDaysByDate),
            'tabs' => $this->tabs(),
            'weekdays' => ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'],
        ]);
    }

    // -------------------------------------------------------------------------
    // Calendar
    // -------------------------------------------------------------------------

    private function calendarDays(
        CarbonImmutable $monthStart,
        CarbonImmutable $monthEnd,
        Collection $holidaysByDate,
        Collection $leaveDaysByDate,
    ): Collection {
        $days = collect();

        for ($blank = 0; $blank < $monthStart->dayOfWeek; $blank++) {
            $days->push([
                'date' => null,
                'holidays' => collect(),
                'leave_days' => collect(),
                'leave_employee_count' => 0,
                'is_non_working_saturday' => false,
            ]);
        }

        foreach (CarbonPeriod::create($monthStart, $monthEnd) as $date) {
            $date = CarbonImmutable::parse($date);
            $dateKey = $date->format('Y-m-d');
            $leaveDays = $leaveDaysByDate->get($dateKey, collect());

            $days->push([
                'date' => $date,
                'holidays' => $holidaysByDate->get($dateKey, collect()),
                'leave_days' => $leaveDays,
                'leave_employee_count' => $leaveDays
                    ->pluck('leaveApplication.employee_id')
                    ->filter()
                    ->unique()
                    ->count(),
                'is_non_working_saturday' => $this->isNonWorkingSaturday($date),
            ]);
        }

        while ($days->count() % 7 !== 0) {
            $days->push([
                'date' => null,
                'holidays' => collect(),
                'leave_days' => collect(),
                'leave_employee_count' => 0,
                'is_non_working_saturday' => false,
            ]);
        }

        return $days;
    }

    private function isNonWorkingSaturday(CarbonImmutable $date): bool
    {
        if ($date->dayOfWeek !== 6) {
            return false;
        }

        $saturdayCount = 0;
        $current = $date->startOfMonth();

        while ($current->lte($date)) {
            if ($current->dayOfWeek === 6) {
                $saturdayCount++;
            }
            $current = $current->addDay();
        }

        return in_array($saturdayCount, [2, 4]);
    }

    private function calendarSummary(
        CarbonImmutable $monthStart,
        Collection $holidaysByDate,
        Collection $leaveDaysByDate,
    ): array {
        $holidayCount = $holidaysByDate->flatten(1)->count();
        $leaveDays = $leaveDaysByDate->flatten(1);
        $employeesOnLeave = $leaveDays
            ->pluck('leaveApplication.employee_id')
            ->filter()
            ->unique()
            ->count();

        $activeEmployees = $this->scope
            ->applyToEmployeeQuery(Employee::query()->where('service_status', 'active'))
            ->count();

        return [
            'active_employees' => $activeEmployees,
            'holidays' => $holidayCount,
            'leave_entries' => $leaveDays->count(),
            'employees_on_leave' => $employeesOnLeave,
            'working_days' => max($monthStart->daysInMonth - $holidayCount, 0),
        ];
    }

    // -------------------------------------------------------------------------
    // Leave queries
    // -------------------------------------------------------------------------

    private function leaveRecords(CarbonImmutable $from, CarbonImmutable $to): Collection
    {
        $query = LeaveApplication::query()
            ->with(['employee.departmentStream', 'employee.designation', 'leaveType'])
            ->whereDate('start_date', '<=', $to)
            ->whereDate('end_date', '>=', $from)
            ->when($this->filterLeaveType !== '', fn ($q) => $q->where('leave_type_id', $this->filterLeaveType))
            ->when($this->search !== '', function ($query): void {
                $query->whereHas('employee', function ($query): void {
                    $query
                        ->where('full_name', 'like', '%'.$this->search.'%')
                        ->orWhere('employee_code', 'like', '%'.$this->search.'%');
                });
            })
            ->orderByDesc('start_date');

        $this->scope->applyToLeaveQuery($query);

        return $query
            ->get()
            ->map(function (LeaveApplication $leave) use ($from, $to): LeaveApplication {
                $leave->month_days = $this->overlapDays($leave, $from, $to);

                return $leave;
            });
    }

    private function leaveDaysByDate(CarbonImmutable $monthStart, CarbonImmutable $monthEnd): Collection
    {
        $query = LeaveApplicationDay::query()
            ->with(['leaveApplication.employee', 'leaveApplication.leaveType'])
            ->where('status', LeaveApplication::STATUS_APPROVED)
            ->whereBetween('leave_date', [$monthStart, $monthEnd])
            ->whereHas('leaveApplication', function ($query): void {
                $query->where('status', LeaveApplication::STATUS_APPROVED);
                $this->scope->applyToLeaveQuery($query);
            })
            ->orderBy('leave_date');

        return $query
            ->get()
            ->groupBy(fn (LeaveApplicationDay $day): string => $day->leave_date->format('Y-m-d'));
    }

    private function leaveSummary(Collection $leaveRecords): array
    {
        $activeEmployees = $this->scope
            ->applyToEmployeeQuery(Employee::query()->where('service_status', 'active'))
            ->count();

        $approvedRecords = $leaveRecords->where('status', LeaveApplication::STATUS_APPROVED);
        $employeesOnLeave = $approvedRecords->pluck('employee_id')->unique()->count();
        $leaveDays = $approvedRecords->sum('month_days');

        return [
            'active_employees' => $activeEmployees,
            'default_present_employees' => max($activeEmployees - $employeesOnLeave, 0),
            'employees_on_leave' => $employeesOnLeave,
            'leave_days' => $leaveDays,
        ];
    }

    private function leaveRequestSummary(): array
    {
        if (! $this->leaveApplicationHasColumn('source')) {
            return [
                'submitted' => 0,
                'approved' => 0,
                'rejected' => 0,
                'under_review' => 0,
            ];
        }

        $base = fn () => $this->scope->applyToLeaveQuery(
            LeaveApplication::query()->where('source', LeaveApplication::SOURCE_EMPLOYEE_REQUEST)
        );

        return [
            'submitted' => (clone $base())->where('status', LeaveApplication::STATUS_SUBMITTED)->count(),
            'approved' => (clone $base())->where('status', LeaveApplication::STATUS_APPROVED)->count(),
            'rejected' => (clone $base())->where('status', LeaveApplication::STATUS_REJECTED)->count(),
            'under_review' => (clone $base())->where('status', LeaveApplication::STATUS_UNDER_REVIEW)->count(),
        ];
    }

    // -------------------------------------------------------------------------
    // Holidays
    // -------------------------------------------------------------------------

    private function holidaysByDate(CarbonImmutable $monthStart, CarbonImmutable $monthEnd): Collection
    {
        return Holiday::query()
            ->where('status', 'active')
            ->whereBetween('holiday_date', [$monthStart, $monthEnd])
            ->orderBy('holiday_date')
            ->orderBy('name')
            ->get()
            ->groupBy(fn (Holiday $holiday): string => $holiday->holiday_date->format('Y-m-d'));
    }

    // -------------------------------------------------------------------------
    // Options
    // -------------------------------------------------------------------------

    private function employeeOptions(): array
    {
        return $this->scope
            ->applyToEmployeeQuery(Employee::query()->where('service_status', 'active')->orderBy('full_name'))
            ->get()
            ->mapWithKeys(fn (Employee $employee): array => [
                $employee->id => $employee->full_name.' ('.$employee->employee_code.')',
            ])
            ->all();
    }

    private function leaveTypeOptions(): array
    {
        return LeaveType::query()
            ->where('status', 'active')
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }

    private function leaveStatusOptions(): array
    {
        return [
            LeaveApplication::STATUS_APPROVED => 'Approved',
            LeaveApplication::STATUS_CANCELLED => 'Cancelled',
        ];
    }

    // -------------------------------------------------------------------------
    // Misc helpers
    // -------------------------------------------------------------------------

    private function manualLeaveSourceData(array $data): array
    {
        if ($this->leaveApplicationHasColumn('source')) {
            $data['source'] = LeaveApplication::SOURCE_MANUAL_HR;
        }

        if ($this->leaveApplicationHasColumn('recorded_by')) {
            $data['recorded_by'] = Auth::id();
        }

        return $data;
    }

    private function leaveApplicationHasColumn(string $column): bool
    {
        return Schema::hasTable('leave_applications')
            && Schema::hasColumn('leave_applications', $column);
    }

    private function syncLeaveDays(LeaveApplication $leave): void
    {
        $leave->days()->delete();

        foreach (CarbonPeriod::create($leave->start_date, $leave->end_date) as $date) {
            $leave->days()->create([
                'leave_date' => $date->format('Y-m-d'),
                'day_type' => 'full_day',
                'duration' => 1,
                'status' => $leave->status,
            ]);
        }
    }

    private function totalLeaveDays(string $startDate, string $endDate): int
    {
        return CarbonImmutable::parse($startDate)->diffInDays(CarbonImmutable::parse($endDate)) + 1;
    }

    private function overlapDays(LeaveApplication $leave, CarbonImmutable $from, CarbonImmutable $to): int
    {
        $start = CarbonImmutable::parse($leave->start_date)->max($from);
        $end = CarbonImmutable::parse($leave->end_date)->min($to);

        return $start->diffInDays($end) + 1;
    }

    private function calendarMonthRange(): array
    {
        $month = CarbonImmutable::createFromFormat('Y-m', $this->month) ?: CarbonImmutable::now();

        return [
            $month->startOfMonth(),
            $month->endOfMonth(),
        ];
    }

    private function registerDateRange(): array
    {
        $from = CarbonImmutable::parse($this->dateFrom)->startOfDay();
        $to = CarbonImmutable::parse($this->dateTo)->endOfDay();

        return [$from, $to];
    }

    private function tabs(): array
    {
        return [
            'calendar' => 'Attendance Calendar',
            'leave_register' => 'Leave Register',
            'leave_requests' => 'Leave Requests',
        ];
    }
}
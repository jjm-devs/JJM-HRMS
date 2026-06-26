<?php

namespace App\Livewire\Employee\Attendance;

use App\Models\AttendanceLog;
use App\Models\Document;
use App\Models\DocumentAccessLog;
use App\Models\Employee;
use App\Models\Holiday;
use App\Models\LeaveApplication;
use App\Models\LeaveApplicationDay;
use App\Models\LeaveType;
use App\Services\Leave\PaidLeaveBankService;
use Carbon\CarbonImmutable;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

class Index extends Component
{
    use WithFileUploads;

    public Employee $employee;

    public string $month = '';

    public array $leaveForm = [
        'leave_type_id' => '',
        'start_date' => '',
        'end_date' => '',
        'reason' => '',
        'contact_during_leave' => '',
    ];

    /** @var array<int, TemporaryUploadedFile> */
    public array $attachments = [];

    public bool $showLeaveModal = false;

    public ?int $selectedLeaveId = null;

    public function mount(bool $openApply = false): void
    {
        $this->employee = Auth::user()->employee()->firstOrFail();
        $this->month = now()->format('Y-m');
        $this->resetLeaveForm();
        $this->showLeaveModal = $openApply;
    }

    public function checkIn(): void
    {
        $today = now()->toDateString();

        $log = AttendanceLog::query()->firstOrCreate(
            [
                'employee_id' => $this->employee->id,
                'attendance_date' => $today,
            ],
            [
                'status' => 'present',
                'source' => 'employee_self',
                'ip_address' => request()->ip(),
            ],
        );

        if ($log->check_in !== null) {
            session()->flash('attendance_status', 'You have already checked in today.');

            return;
        }

        $log->update([
            'check_in' => now(),
            'status' => 'present',
            'source' => 'employee_self',
            'ip_address' => request()->ip(),
        ]);

        session()->flash('attendance_status', 'Check-in recorded.');
    }

    public function checkOut(): void
    {
        $log = AttendanceLog::query()
            ->where('employee_id', $this->employee->id)
            ->whereDate('attendance_date', now()->toDateString())
            ->first();

        if (! $log || $log->check_in === null) {
            session()->flash('attendance_error', 'Please check in before checking out.');

            return;
        }

        if ($log->check_out !== null) {
            session()->flash('attendance_status', 'You have already checked out today.');

            return;
        }

        $log->update([
            'check_out' => now(),
            'ip_address' => request()->ip(),
        ]);

        session()->flash('attendance_status', 'Check-out recorded.');
    }

    public function openApplyModal(): void
    {
        $this->resetLeaveForm();
        $this->dispatch('open-modal', name: 'employee-leave-request');
    }

    public function applyForDate(string $date): void
    {
        $selected = CarbonImmutable::parse($date);

        $this->leaveForm['start_date'] = $selected->toDateString();
        $this->leaveForm['end_date'] = $selected->toDateString();

        $this->dispatch('open-modal', name: 'employee-leave-request');
    }

    public function submitLeaveRequest(): void
    {
        $data = $this->validateLeaveRequest();
        $data['employee_id'] = $this->employee->id;
        $data['total_days'] = $this->totalLeaveDays($data['start_date'], $data['end_date']);
        $data['source'] = LeaveApplication::SOURCE_EMPLOYEE_REQUEST;
        $data['submitted_by'] = Auth::id();
        $data['status'] = LeaveApplication::STATUS_SUBMITTED;

        // Paid leave beyond the monthly bank is allowed — the excess is deducted in payroll.
        $leave = LeaveApplication::query()->create($data);
        $this->syncLeaveDays($leave);
        $this->storeAttachments($leave);

        $this->resetLeaveForm();
        $this->showLeaveModal = false;
        $this->dispatch('close-modal', name: 'employee-leave-request');
        session()->flash('leave_status', 'Leave request submitted successfully.');
    }

    public function openLeaveDetail(int $leaveId): void
    {
        $leave = $this->leaveForEmployee($leaveId);
        $this->selectedLeaveId = $leave->id;
        $this->dispatch('open-modal', name: 'employee-leave-detail');
    }

    public function downloadLeaveDocument(int $documentId)
    {
        $document = Document::query()
            ->whereKey($documentId)
            ->where('documentable_type', (new LeaveApplication())->getMorphClass())
            ->firstOrFail();

        $this->leaveForEmployee((int) $document->documentable_id);

        abort_unless(Storage::disk($document->disk)->exists($document->file_path), 404);

        DocumentAccessLog::query()->create([
            'document_id' => $document->id,
            'user_id' => Auth::id(),
            'action' => 'downloaded',
            'ip_address' => request()->ip(),
            'user_agent' => substr((string) request()->userAgent(), 0, 1000),
        ]);

        return Storage::disk($document->disk)->download($document->file_path, $document->file_name);
    }

    public function render()
    {
        [$monthStart, $monthEnd] = $this->calendarMonthRange();

        return view('livewire.employee.attendance.index', [
            'attendanceLogsByDate' => $this->attendanceLogsByDate($monthStart, $monthEnd),
            'calendarDays' => $this->calendarDays(
                monthStart: $monthStart,
                monthEnd: $monthEnd,
                holidaysByDate: $this->holidaysByDate($monthStart, $monthEnd),
                leaveDaysByDate: $this->leaveDaysByDate($monthStart, $monthEnd),
                attendanceLogsByDate: $this->attendanceLogsByDate($monthStart, $monthEnd),
            ),
            'paidLeaveBank' => $this->paidLeaveBank($monthStart, $monthEnd),
            'leaveHistory' => $this->leaveHistory(),
            'leaveTypeOptions' => $this->leaveTypeOptions(),
            'selectedLeaveBalance' => $this->selectedLeaveBalance(),
            'selectedLeave' => $this->selectedLeave(),
            'todayLog' => $this->todayLog(),
            'weekdays' => ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'],
        ]);
    }

    private function validateLeaveRequest(): array
    {
        $validated = $this->validate([
            'leaveForm.leave_type_id' => ['required', 'integer', 'exists:leave_types,id'],
            'leaveForm.start_date' => ['required', 'date'],
            'leaveForm.end_date' => ['required', 'date', 'after_or_equal:leaveForm.start_date'],
            'leaveForm.reason' => ['required', 'string', 'max:5000'],
            'leaveForm.contact_during_leave' => ['nullable', 'string', 'max:255'],
            'attachments' => ['array'],
            'attachments.*' => ['file', 'mimes:jpg,jpeg,png,pdf', 'max:10240'],
        ])['leaveForm'];

        $leaveType = LeaveType::query()->find($validated['leave_type_id']);

        if ($leaveType?->requires_document && empty($this->attachments)) {
            throw ValidationException::withMessages([
                'attachments' => 'This leave type requires at least one attachment.',
            ]);
        }

        return array_map(fn ($value) => $value === '' ? null : $value, $validated);
    }

    private function syncLeaveDays(LeaveApplication $leave): void
    {
        foreach (CarbonPeriod::create($leave->start_date, $leave->end_date) as $date) {
            $leave->days()->create([
                'leave_date' => $date->format('Y-m-d'),
                'day_type' => 'full_day',
                'duration' => 1,
                'status' => LeaveApplication::STATUS_SUBMITTED,
            ]);
        }
    }

    private function storeAttachments(LeaveApplication $leave): void
    {
        foreach ($this->attachments as $attachment) {
            $disk = config('filesystems.default', 'local');
            $fileName = $attachment->getClientOriginalName();
            $mimeType = $attachment->getMimeType();
            $path = $attachment->store('leave-requests/'.$leave->id, $disk);
            $fileSize = Storage::disk($disk)->size($path);

            $leave->documents()->create([
                'title' => $fileName,
                'file_name' => $fileName,
                'file_path' => $path,
                'disk' => $disk,
                'mime_type' => $mimeType,
                'file_size' => $fileSize,
                'status' => 'submitted',
                'uploaded_by' => Auth::id(),
            ]);
        }
    }

    private function resetLeaveForm(): void
    {
        $today = now()->toDateString();

        $this->resetErrorBag();
        $this->leaveForm = [
            'leave_type_id' => '',
            'start_date' => $today,
            'end_date' => $today,
            'reason' => '',
            'contact_during_leave' => '',
        ];
        $this->attachments = [];
    }

    private function calendarDays(
        CarbonImmutable $monthStart,
        CarbonImmutable $monthEnd,
        Collection $holidaysByDate,
        Collection $leaveDaysByDate,
        Collection $attendanceLogsByDate,
    ): Collection {
        $days = collect();

        for ($blank = 0; $blank < $monthStart->dayOfWeek; $blank++) {
            $days->push([
                'date' => null,
                'holidays' => collect(),
                'leave_days' => collect(),
                'attendance_log' => null,
                'is_non_working_saturday' => false,
                'is_today' => false,
            ]);
        }

        foreach (CarbonPeriod::create($monthStart, $monthEnd) as $date) {
            $date = CarbonImmutable::parse($date);
            $dateKey = $date->format('Y-m-d');

            $days->push([
                'date' => $date,
                'holidays' => $holidaysByDate->get($dateKey, collect()),
                'leave_days' => $leaveDaysByDate->get($dateKey, collect()),
                'attendance_log' => $attendanceLogsByDate->get($dateKey),
                'is_non_working_saturday' => $this->isNonWorkingSaturday($date),
                'is_today' => $date->isSameDay(now()),
            ]);
        }

        while ($days->count() % 7 !== 0) {
            $days->push([
                'date' => null,
                'holidays' => collect(),
                'leave_days' => collect(),
                'attendance_log' => null,
                'is_non_working_saturday' => false,
                'is_today' => false,
            ]);
        }

        return $days;
    }

    private function attendanceLogsByDate(CarbonImmutable $monthStart, CarbonImmutable $monthEnd): Collection
    {
        return $this->employee
            ->attendanceLogs()
            ->whereBetween('attendance_date', [$monthStart->toDateString(), $monthEnd->toDateString()])
            ->get()
            ->keyBy(fn (AttendanceLog $log): string => $log->attendance_date->format('Y-m-d'));
    }

    private function leaveDaysByDate(CarbonImmutable $monthStart, CarbonImmutable $monthEnd): Collection
    {
        return LeaveApplicationDay::query()
            ->with(['leaveApplication.leaveType'])
            ->whereBetween('leave_date', [$monthStart->toDateString(), $monthEnd->toDateString()])
            ->whereIn('status', [
                LeaveApplication::STATUS_SUBMITTED,
                LeaveApplication::STATUS_UNDER_REVIEW,
                LeaveApplication::STATUS_APPROVED,
            ])
            ->whereHas('leaveApplication', function ($query): void {
                $query
                    ->where('employee_id', $this->employee->id)
                    ->whereIn('status', [
                        LeaveApplication::STATUS_SUBMITTED,
                        LeaveApplication::STATUS_UNDER_REVIEW,
                        LeaveApplication::STATUS_APPROVED,
                    ]);
            })
            ->orderBy('leave_date')
            ->get()
            ->groupBy(fn (LeaveApplicationDay $day): string => $day->leave_date->format('Y-m-d'));
    }

    private function holidaysByDate(CarbonImmutable $monthStart, CarbonImmutable $monthEnd): Collection
    {
        return Holiday::query()
            ->where('status', 'active')
            ->whereBetween('holiday_date', [$monthStart->toDateString(), $monthEnd->toDateString()])
            ->orderBy('holiday_date')
            ->orderBy('name')
            ->get()
            ->groupBy(fn (Holiday $holiday): string => $holiday->holiday_date->format('Y-m-d'));
    }

    private function todayLog(): ?AttendanceLog
    {
        return $this->employee
            ->attendanceLogs()
            ->whereDate('attendance_date', now()->toDateString())
            ->first();
    }

    private function leaveHistory(): Collection
    {
        return $this->employee
            ->leaveApplications()
            ->with(['leaveType', 'documents'])
            ->latest('id')
            ->limit(10)
            ->get();
    }

    private function selectedLeave(): ?LeaveApplication
    {
        if (! $this->selectedLeaveId) {
            return null;
        }

        return $this->leaveForEmployee($this->selectedLeaveId);
    }

    private function leaveForEmployee(int $leaveId): LeaveApplication
    {
        return $this->employee
            ->leaveApplications()
            ->with(['leaveType', 'documents.uploadedBy:id,name', 'approvedBy:id,name'])
            ->whereKey($leaveId)
            ->firstOrFail();
    }

    private function leaveTypeOptions(): array
    {
        return LeaveType::query()
            ->where('status', 'active')
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }

    private function selectedLeaveBalance(): ?string
    {
        if (blank($this->leaveForm['leave_type_id'])) {
            return null;
        }

        $bank = app(PaidLeaveBankService::class);

        if ($bank->isPaidLeaveType((int) $this->leaveForm['leave_type_id'])) {
            [$monthStart] = $this->selectedLeaveMonthRange();
            $remaining = $bank->remainingInMonth($this->employee, $monthStart->year, $monthStart->month);

            return number_format($remaining, 2).' paid leave day(s) left in '.$monthStart->format('F Y')
                .'. Extra days are deducted from salary.';
        }

        $balance = $this->employee
            ->leaveBalances()
            ->where('leave_type_id', $this->leaveForm['leave_type_id'])
            ->where('year', now()->year)
            ->first();

        if (! $balance) {
            return null;
        }

        return number_format((float) $balance->closing_balance, 2).' day(s) left';
    }

    /**
     * @return array{allowance:float, used:float, remaining:float, leave_type:?LeaveType}
     */
    private function paidLeaveBank(CarbonImmutable $monthStart, CarbonImmutable $monthEnd): array
    {
        $bank = app(PaidLeaveBankService::class);
        $used = $bank->usedInMonth($this->employee, $monthStart->year, $monthStart->month);

        return [
            'allowance' => PaidLeaveBankService::MONTHLY_ALLOWANCE,
            'used' => $used,
            'remaining' => max(PaidLeaveBankService::MONTHLY_ALLOWANCE - $used, 0),
            'leave_type' => $bank->paidLeaveType(),
        ];
    }

    private function selectedLeaveMonthRange(): array
    {
        $date = filled($this->leaveForm['start_date'])
            ? CarbonImmutable::parse($this->leaveForm['start_date'])
            : CarbonImmutable::now();

        return [$date->startOfMonth(), $date->endOfMonth()];
    }

    private function totalLeaveDays(string $startDate, string $endDate): int
    {
        return CarbonImmutable::parse($startDate)->diffInDays(CarbonImmutable::parse($endDate)) + 1;
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

        return in_array($saturdayCount, [2, 4], true);
    }

    private function calendarMonthRange(): array
    {
        $month = CarbonImmutable::createFromFormat('Y-m', $this->month) ?: CarbonImmutable::now();

        return [$month->startOfMonth(), $month->endOfMonth()];
    }
}

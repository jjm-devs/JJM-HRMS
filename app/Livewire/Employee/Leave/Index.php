<?php

namespace App\Livewire\Employee\Leave;

use App\Models\Employee;
use App\Models\LeaveApplication;
use App\Models\LeaveType;
use Carbon\CarbonImmutable;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

class Index extends Component
{
    use WithFileUploads;

    public Employee $employee;

    public array $leaveForm = [
        'leave_type_id' => '',
        'start_date' => '',
        'end_date' => '',
        'reason' => '',
        'contact_during_leave' => '',
    ];

    /** @var array<int, TemporaryUploadedFile> */
    public array $attachments = [];

    public function mount(): void
    {
        $this->employee = Auth::user()->employee()->firstOrFail();
        $today = now()->format('Y-m-d');

        $this->leaveForm['start_date'] = $today;
        $this->leaveForm['end_date'] = $today;
    }

    public function submitLeaveRequest(): void
    {
        $data = $this->validateLeaveRequest();
        $data['employee_id'] = $this->employee->id;
        $data['total_days'] = $this->totalLeaveDays($data['start_date'], $data['end_date']);
        $data['source'] = LeaveApplication::SOURCE_EMPLOYEE_REQUEST;
        $data['submitted_by'] = Auth::id();
        $data['status'] = LeaveApplication::STATUS_SUBMITTED;

        $leave = LeaveApplication::query()->create($data);
        $this->syncLeaveDays($leave);
        $this->storeAttachments($leave);

        $this->resetLeaveForm();
        session()->flash('leave_status', 'Leave request submitted successfully.');
    }

    public function appendMessageToken(string $token): void
    {
        $this->leaveForm['reason'] = trim(($this->leaveForm['reason'] ?? '')."\n".$token);
    }

    public function render()
    {
        return view('livewire.employee.leave.index', [
            'leaveHistory' => $this->leaveHistory(),
            'leaveStats' => $this->leaveStats(),
            'leaveTypeOptions' => $this->leaveTypeOptions(),
            'selectedLeaveBalance' => $this->selectedLeaveBalance(),
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
            $path = $attachment->store('leave-requests/'.$leave->id);

            $leave->documents()->create([
                'title' => $attachment->getClientOriginalName(),
                'file_name' => $attachment->getClientOriginalName(),
                'file_path' => $path,
                'disk' => config('filesystems.default', 'local'),
                'mime_type' => $attachment->getMimeType(),
                'file_size' => $attachment->getSize(),
                'status' => 'submitted',
                'uploaded_by' => Auth::id(),
            ]);
        }
    }

    private function resetLeaveForm(): void
    {
        $today = now()->format('Y-m-d');

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

    private function leaveStats(): array
    {
        $monthStart = CarbonImmutable::now()->startOfMonth();
        $monthEnd = CarbonImmutable::now()->endOfMonth();
        $approvedDays = $this->employee
            ->leaveApplications()
            ->where('status', LeaveApplication::STATUS_APPROVED)
            ->whereDate('start_date', '<=', $monthEnd)
            ->whereDate('end_date', '>=', $monthStart)
            ->get()
            ->sum(fn (LeaveApplication $leave): int => $this->overlapDays($leave, $monthStart, $monthEnd));

        return [
            'month_days' => $monthStart->daysInMonth,
            'approved_days' => $approvedDays,
            'remaining_month_days' => max($monthStart->daysInMonth - $approvedDays, 0),
        ];
    }

    private function selectedLeaveBalance(): ?string
    {
        if (blank($this->leaveForm['leave_type_id'])) {
            return null;
        }

        $balance = $this->employee
            ->leaveBalances()
            ->where('leave_type_id', $this->leaveForm['leave_type_id'])
            ->where('year', now()->year)
            ->first();

        if (! $balance) {
            return 'No balance configured';
        }

        return number_format((float) $balance->closing_balance, 2).' days';
    }

    private function leaveHistory()
    {
        return $this->employee
            ->leaveApplications()
            ->with(['leaveType', 'documents'])
            ->latest('id')
            ->limit(10)
            ->get();
    }

    private function leaveTypeOptions(): array
    {
        return LeaveType::query()
            ->where('status', 'active')
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }

    private function totalLeaveDays(string $startDate, string $endDate): int
    {
        return CarbonImmutable::parse($startDate)->diffInDays(CarbonImmutable::parse($endDate)) + 1;
    }

    private function overlapDays(LeaveApplication $leave, CarbonImmutable $monthStart, CarbonImmutable $monthEnd): int
    {
        $start = CarbonImmutable::parse($leave->start_date)->max($monthStart);
        $end = CarbonImmutable::parse($leave->end_date)->min($monthEnd);

        return $start->diffInDays($end) + 1;
    }
}

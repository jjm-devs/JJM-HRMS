<?php

namespace Tests\Feature\Employee;

use App\Livewire\Employee\Attendance\Index as EmployeeAttendanceIndex;
use App\Models\AttendanceLog;
use App\Models\Employee;
use App\Models\LeaveApplication;
use App\Models\LeaveApplicationDay;
use App\Models\LeaveType;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AttendanceLeaveModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_can_check_in_and_check_out(): void
    {
        Carbon::setTestNow('2026-06-15 09:30:00');

        [$user, $employee] = $this->employeeUser();

        $this->actingAs($user);

        $component = Livewire::test(EmployeeAttendanceIndex::class);

        $component
            ->call('checkIn')
            ->assertHasNoErrors();

        $log = AttendanceLog::query()->firstOrFail();

        $this->assertSame($employee->id, $log->employee_id);
        $this->assertSame('2026-06-15', $log->attendance_date->format('Y-m-d'));
        $this->assertSame('present', $log->status);
        $this->assertSame('employee_self', $log->source);
        $this->assertNotNull($log->check_in);

        Carbon::setTestNow('2026-06-15 17:45:00');

        $component
            ->call('checkOut')
            ->assertHasNoErrors();

        $this->assertNotNull(AttendanceLog::query()->firstOrFail()->check_out);

        Carbon::setTestNow();
    }

    public function test_employee_can_apply_leave_from_calendar_date(): void
    {
        [$user, $employee] = $this->employeeUser();

        $leaveType = LeaveType::query()->create([
            'name' => 'Casual Leave',
            'code' => 'CL',
            'is_paid' => true,
            'status' => 'active',
        ]);

        $this->actingAs($user);

        Livewire::test(EmployeeAttendanceIndex::class)
            ->call('applyForDate', '2026-06-12')
            ->assertSet('leaveForm.start_date', '2026-06-12')
            ->assertSet('leaveForm.end_date', '2026-06-12')
            ->set('leaveForm.leave_type_id', (string) $leaveType->id)
            ->set('leaveForm.reason', 'Personal work.')
            ->call('submitLeaveRequest')
            ->assertHasNoErrors();

        $leave = LeaveApplication::query()->firstOrFail();

        $this->assertSame($employee->id, $leave->employee_id);
        $this->assertSame($leaveType->id, $leave->leave_type_id);
        $this->assertSame('2026-06-12', $leave->start_date->format('Y-m-d'));
        $this->assertSame('2026-06-12', $leave->end_date->format('Y-m-d'));
        $this->assertSame(1.0, (float) $leave->total_days);
        $this->assertSame(LeaveApplication::SOURCE_EMPLOYEE_REQUEST, $leave->source);
        $this->assertSame($user->id, $leave->submitted_by);
        $this->assertSame(LeaveApplication::STATUS_SUBMITTED, $leave->status);

        $day = LeaveApplicationDay::query()->firstOrFail();

        $this->assertSame('2026-06-12', $day->leave_date->format('Y-m-d'));
        $this->assertSame(LeaveApplication::STATUS_SUBMITTED, $day->status);
    }

    public function test_paid_leave_beyond_monthly_bank_is_allowed(): void
    {
        [$user, $employee] = $this->employeeUser();

        $leaveType = LeaveType::query()->create([
            'name' => 'Paid Leave',
            'code' => 'PL',
            'is_paid' => true,
            'status' => 'active',
        ]);

        $this->actingAs($user);

        // Within the 2/month bank — allowed.
        Livewire::test(EmployeeAttendanceIndex::class)
            ->set('leaveForm.leave_type_id', (string) $leaveType->id)
            ->set('leaveForm.start_date', '2026-06-01')
            ->set('leaveForm.end_date', '2026-06-02')
            ->set('leaveForm.reason', 'Personal work.')
            ->call('submitLeaveRequest')
            ->assertHasNoErrors();

        // Beyond the bank — also allowed now (the excess is deducted in payroll).
        Livewire::test(EmployeeAttendanceIndex::class)
            ->set('leaveForm.leave_type_id', (string) $leaveType->id)
            ->set('leaveForm.start_date', '2026-06-03')
            ->set('leaveForm.end_date', '2026-06-03')
            ->set('leaveForm.reason', 'More personal work.')
            ->call('submitLeaveRequest')
            ->assertHasNoErrors();

        $this->assertSame(2, $employee->leaveApplications()->count());
    }

    private function employeeUser(): array
    {
        $user = User::query()->create([
            'name' => 'Employee User',
            'email' => uniqid('employee-attendance-', true).'@example.test',
            'password' => 'password',
            'status' => 'active',
        ]);

        $employee = Employee::query()->create([
            'user_id' => $user->id,
            'employee_code' => strtoupper(uniqid('EMP-ATT-')),
            'full_name' => 'Attendance Employee',
            'service_status' => 'active',
        ]);

        return [$user, $employee];
    }
}

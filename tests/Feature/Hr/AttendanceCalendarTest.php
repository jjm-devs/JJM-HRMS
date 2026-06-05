<?php

namespace Tests\Feature\Hr;

use App\Livewire\Hr\Attendance\Index;
use App\Models\Employee;
use App\Models\Holiday;
use App\Models\LeaveApplication;
use App\Models\LeaveType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AttendanceCalendarTest extends TestCase
{
    use RefreshDatabase;

    public function test_hr_can_view_holidays_and_employee_leave_on_attendance_calendar(): void
    {
        $hr = User::query()->create([
            'name' => 'HR User',
            'email' => 'hr-attendance-calendar@example.test',
            'password' => 'password',
            'is_hr' => true,
            'status' => 'active',
        ]);

        $employee = Employee::query()->create([
            'employee_code' => 'EMP-CALENDAR-00001',
            'full_name' => 'Calendar Employee',
            'service_status' => 'active',
        ]);

        $leaveType = LeaveType::query()->create([
            'name' => 'Casual Leave',
            'code' => 'CL-CALENDAR',
            'is_paid' => true,
            'allow_half_day' => true,
            'status' => 'active',
        ]);

        Holiday::query()->create([
            'name' => 'State Foundation Day',
            'holiday_date' => '2026-06-15',
            'type' => 'state',
            'status' => 'active',
        ]);

        $leave = LeaveApplication::query()->create([
            'employee_id' => $employee->id,
            'leave_type_id' => $leaveType->id,
            'start_date' => '2026-06-15',
            'end_date' => '2026-06-16',
            'total_days' => 2,
            'source' => LeaveApplication::SOURCE_MANUAL_HR,
            'recorded_by' => $hr->id,
            'status' => LeaveApplication::STATUS_APPROVED,
            'approved_by' => $hr->id,
            'approved_at' => now(),
        ]);

        $leave->days()->createMany([
            [
                'leave_date' => '2026-06-15',
                'day_type' => 'full_day',
                'duration' => 1,
                'status' => LeaveApplication::STATUS_APPROVED,
            ],
            [
                'leave_date' => '2026-06-16',
                'day_type' => 'full_day',
                'duration' => 1,
                'status' => LeaveApplication::STATUS_APPROVED,
            ],
        ]);

        $this->actingAs($hr);

        Livewire::test(Index::class)
            ->set('month', '2026-06')
            ->assertSee('Attendance Calendar')
            ->assertSee('State Foundation Day')
            ->assertSee('Calendar Employee')
            ->assertSee('Casual Leave')
            ->assertSee('1 leave')
            ->assertSee('2');
    }
}

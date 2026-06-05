<?php

namespace Tests\Feature\Hr;

use App\Livewire\Hr\Attendance\Index;
use App\Models\Employee;
use App\Models\LeaveApplication;
use App\Models\LeaveType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class LeaveRegisterTest extends TestCase
{
    use RefreshDatabase;

    public function test_hr_can_record_update_and_cancel_employee_leave(): void
    {
        $hr = User::query()->create([
            'name' => 'HR User',
            'email' => 'hr-leave-register@example.test',
            'password' => 'password',
            'is_hr' => true,
            'status' => 'active',
        ]);

        $employee = Employee::query()->create([
            'employee_code' => 'EMP-LEAVE-00001',
            'full_name' => 'Leave Employee',
            'service_status' => 'active',
        ]);

        $leaveType = LeaveType::query()->create([
            'name' => 'Casual Leave',
            'code' => 'CL-TEST',
            'is_paid' => true,
            'allow_half_day' => true,
            'status' => 'active',
        ]);

        $this->actingAs($hr);

        Livewire::test(Index::class)
            ->set('activeTab', 'leave_register')
            ->set('month', '2026-06')
            ->set('leaveForm.employee_id', (string) $employee->id)
            ->set('leaveForm.leave_type_id', (string) $leaveType->id)
            ->set('leaveForm.start_date', '2026-06-10')
            ->set('leaveForm.end_date', '2026-06-12')
            ->set('leaveForm.reason', 'Personal work')
            ->set('leaveForm.contact_during_leave', '9876543210')
            ->call('saveLeaveRecord')
            ->assertHasNoErrors();

        $leave = LeaveApplication::query()->firstOrFail();

        $this->assertDatabaseHas('leave_applications', [
            'id' => $leave->id,
            'employee_id' => $employee->id,
            'leave_type_id' => $leaveType->id,
            'start_date' => '2026-06-10 00:00:00',
            'end_date' => '2026-06-12 00:00:00',
            'total_days' => 3,
            'source' => LeaveApplication::SOURCE_MANUAL_HR,
            'recorded_by' => $hr->id,
            'status' => LeaveApplication::STATUS_APPROVED,
            'approved_by' => $hr->id,
        ]);

        $this->assertSame(3, $leave->days()->count());

        Livewire::test(Index::class)
            ->call('editLeaveRecord', $leave->id)
            ->set('leaveForm.end_date', '2026-06-13')
            ->set('leaveForm.reason', 'Personal work extended')
            ->call('saveLeaveRecord')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('leave_applications', [
            'id' => $leave->id,
            'end_date' => '2026-06-13 00:00:00',
            'total_days' => 4,
            'reason' => 'Personal work extended',
        ]);

        $this->assertSame(4, $leave->fresh()->days()->count());

        Livewire::test(Index::class)
            ->set('activeTab', 'leave_register')
            ->set('month', '2026-06')
            ->assertSee('Leave Register')
            ->assertSee('Leave Employee')
            ->assertSee('Manual HR')
            ->assertSee('Casual Leave')
            ->assertSee('4.00');

        Livewire::test(Index::class)
            ->set('activeTab', 'leave_register')
            ->call('cancelLeaveRecord', $leave->id)
            ->assertHasNoErrors();

        $this->assertDatabaseHas('leave_applications', [
            'id' => $leave->id,
            'status' => LeaveApplication::STATUS_CANCELLED,
        ]);

        $this->assertSame(4, $leave->fresh()->days()->where('status', LeaveApplication::STATUS_CANCELLED)->count());
    }

    public function test_leave_request_tab_is_scaffolded(): void
    {
        $hr = User::query()->create([
            'name' => 'HR User',
            'email' => 'hr-leave-requests@example.test',
            'password' => 'password',
            'is_hr' => true,
            'status' => 'active',
        ]);

        $this->actingAs($hr);

        Livewire::test(Index::class)
            ->set('activeTab', 'leave_requests')
            ->assertSee('Submitted')
            ->assertSee('Under Review')
            ->assertSee('Approved')
            ->assertSee('Rejected')
            ->assertSee('Leave request workflow will be added next');
    }
}

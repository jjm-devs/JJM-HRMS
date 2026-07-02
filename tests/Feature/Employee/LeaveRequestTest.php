<?php

namespace Tests\Feature\Employee;

use App\Livewire\Employee\Attendance\Index as EmployeeAttendanceIndex;
use App\Livewire\Employee\Leave\Index as EmployeeLeaveIndex;
use App\Livewire\Hr\Documents\Index as HrDocumentsIndex;
use App\Livewire\Hr\Attendance\Index as HrAttendanceIndex;
use App\Models\Document;
use App\Models\DocumentAccessLog;
use App\Models\Employee;
use App\Models\LeaveApplication;
use App\Models\LeaveType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class LeaveRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_can_submit_leave_request_with_attachments_visible_to_hr(): void
    {
        Storage::fake('local');

        $employeeUser = User::query()->create([
            'name' => 'Employee User',
            'email' => 'employee-leave@example.test',
            'password' => 'password',
            'status' => 'active',
        ]);

        $hr = User::query()->create([
            'name' => 'HR User',
            'email' => 'hr-leave-review@example.test',
            'password' => 'password',
            'is_hr' => true,
            'status' => 'active',
        ]);

        $employee = Employee::query()->create([
            'user_id' => $employeeUser->id,
            'employee_code' => 'EMP-LEAVE-REQ-00001',
            'full_name' => 'Leave Request Employee',
            'service_status' => 'active',
        ]);

        $leaveType = LeaveType::query()->create([
            'name' => 'Medical Leave',
            'code' => 'ML-REQ',
            'is_paid' => true,
            'requires_document' => true,
            'allow_half_day' => true,
            'status' => 'active',
        ]);

        $this->actingAs($employeeUser);

        Livewire::test(EmployeeLeaveIndex::class)
            ->set('leaveForm.leave_type_id', (string) $leaveType->id)
            ->set('leaveForm.start_date', '2026-06-08')
            ->set('leaveForm.end_date', '2026-06-09')
            ->set('leaveForm.reason', 'Need leave for a medical appointment.')
            ->set('leaveForm.contact_during_leave', '9876543210')
            ->set('attachments', [
                UploadedFile::fake()->image('prescription.jpg')->size(512),
                UploadedFile::fake()->create('medical-note.pdf', 256, 'application/pdf'),
            ])
            ->call('submitLeaveRequest')
            ->assertHasNoErrors();

        $leave = LeaveApplication::query()->firstOrFail();

        $this->assertDatabaseHas('leave_applications', [
            'id' => $leave->id,
            'employee_id' => $employee->id,
            'leave_type_id' => $leaveType->id,
            'total_days' => 2,
            'source' => LeaveApplication::SOURCE_EMPLOYEE_REQUEST,
            'submitted_by' => $employeeUser->id,
            'status' => LeaveApplication::STATUS_SUBMITTED,
        ]);

        $this->assertSame(2, $leave->days()->count());
        $this->assertSame(2, $leave->documents()->count());

        foreach ($leave->documents as $document) {
            Storage::disk($document->disk)->assertExists($document->file_path);
        }

        $this->actingAs($hr);

        Livewire::test(HrAttendanceIndex::class)
            ->set('activeTab', 'leave_requests')
            ->assertSee('Leave Request Employee')
            ->assertSee('Medical Leave')
            ->assertSee('Submitted')
            ->assertSee('prescription.jpg')
            ->assertSee('medical-note.pdf');

        // Printable opens in-browser (HTML → print / Save as PDF).
        $this->get(route('hr.leave.application.print', $leave->id))
            ->assertOk()
            ->assertSee('Leave Application', false)
            ->assertSee('Leave Request Employee', false);

        $printDocument = Document::query()
            ->where('documentable_type', $leave->getMorphClass())
            ->where('documentable_id', $leave->id)
            ->where('title', 'Leave Application Print')
            ->firstOrFail();

        Storage::disk($printDocument->disk)->assertExists($printDocument->file_path);
        $this->assertSame('text/html', $printDocument->mime_type);
        $this->assertStringContainsString('Leave Application', Storage::disk($printDocument->disk)->get($printDocument->file_path));

        Livewire::test(HrAttendanceIndex::class)
            ->call('openApproveLeaveRequestModal', $leave->id)
            ->set('leaveApprovalRemarks', 'Approved after physical signature.')
            ->set('signedLeaveDocumentFile', UploadedFile::fake()->image('signed-leave.png')->size(256))
            ->call('approveSelectedLeaveRequest')
            ->assertHasNoErrors();

        $leave->refresh();
        $this->assertSame(LeaveApplication::STATUS_APPROVED, $leave->status);
        $this->assertSame($hr->id, $leave->approved_by);
        $this->assertSame(2, $leave->days()->where('status', LeaveApplication::STATUS_APPROVED)->count());

        $signedDocument = Document::query()
            ->where('documentable_type', $leave->getMorphClass())
            ->where('documentable_id', $leave->id)
            ->where('title', 'Signed Leave Application')
            ->firstOrFail();

        Storage::disk($signedDocument->disk)->assertExists($signedDocument->file_path);

        Livewire::test(HrDocumentsIndex::class)
            ->assertSee('Signed Leave Application')
            ->assertSee('Leave Request '.$leave->id)
            ->call('downloadDocument', $signedDocument->id)
            ->assertFileDownloaded('signed-leave.png');

        $this->actingAs($employeeUser);

        Livewire::test(EmployeeAttendanceIndex::class)
            ->call('openLeaveDetail', $leave->id)
            ->assertSee('Signed Leave Application')
            ->call('downloadLeaveDocument', $signedDocument->id)
            ->assertFileDownloaded('signed-leave.png');

        $this->assertGreaterThanOrEqual(
            2,
            DocumentAccessLog::query()
                ->where('document_id', $signedDocument->id)
                ->where('action', 'downloaded')
                ->count(),
        );
    }
}

<?php

use App\Http\Middleware\EnsureHrUser;
use App\Http\Middleware\EnsurePasswordIsChanged;
use App\Models\DocumentAccessLog;
use App\Models\Employee;
use App\Models\LeaveApplication;
use App\Models\PayrollBatch;
use App\Models\PayrollItem;
use App\Models\Payslip;
use App\Services\Hr\HrScopeService;
use App\Services\Leave\LeaveApplicationDocumentService;
use App\Services\Payroll\PayslipViewService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

Route::middleware([EnsureHrUser::class, EnsurePasswordIsChanged::class])->group(function () {
    Route::get('/dashboard', fn () => view('app.page', [
        'livewireComponent' => 'hr.dashboard',
        'title' => 'HR Dashboard',
    ]))->name('hr.dashboard');

    Route::get('/employees', fn () => view('app.page', [
        'livewireComponent' => 'hr.employees.index',
        'title' => 'Employees',
    ]))->name('hr.employees.index');

    Route::get('/employees/create', fn () => view('app.page', [
        'livewireComponent' => 'hr.employees.create',
        'title' => 'Add Employee',
    ]))->name('hr.employees.create');

    Route::get('/employees/{employee}', fn (Employee $employee) => view('app.page', [
        'livewireComponent' => 'hr.employees.show',
        'livewireParams' => ['employee' => $employee],
        'title' => $employee->full_name,
    ]))->name('hr.employees.show');

    Route::get('/employees/{employee}/edit', fn (Employee $employee) => view('app.page', [
        'livewireComponent' => 'hr.employees.edit',
        'livewireParams' => ['employee' => $employee],
        'title' => 'Edit '.$employee->full_name,
    ]))->name('hr.employees.edit');

    Route::get('/attendance', fn () => view('app.page', [
        'livewireComponent' => 'hr.attendance.index',
        'title' => 'Attendance & Leave',
    ]))->name('hr.attendance.index');

    Route::get('/leave', fn () => view('app.page', [
        'livewireComponent' => 'hr.attendance.index',
        'livewireParams' => ['activeTab' => 'leave_register'],
        'title' => 'Attendance & Leave',
    ]))->name('hr.leave.index');

    Route::get('/leave/{leave}/application/print', function (LeaveApplication $leave) {
        abort_unless($leave->source === LeaveApplication::SOURCE_EMPLOYEE_REQUEST, 404);

        $scoped = app(HrScopeService::class)->applyToLeaveQuery(
            LeaveApplication::query()->whereKey($leave->id)
        );
        abort_unless($scoped->exists(), 403);

        $document = app(LeaveApplicationDocumentService::class)->generateApplicationPrint($leave);

        DocumentAccessLog::query()->create([
            'document_id' => $document->id,
            'user_id' => Auth::id(),
            'action' => 'generated',
            'ip_address' => request()->ip(),
            'user_agent' => substr((string) request()->userAgent(), 0, 1000),
        ]);

        abort_unless(Storage::disk($document->disk)->exists($document->file_path), 404);

        $script = '<script>window.addEventListener("load",function(){setTimeout(function(){window.print();},200);});</script>';
        $html = preg_replace('/<\/body>/i', $script.'</body>', Storage::disk($document->disk)->get($document->file_path), 1);

        return response($html, 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
            'Content-Disposition' => 'inline; filename="leave-application-'.$leave->id.'.html"',
        ]);
    })->name('hr.leave.application.print');

    Route::get('/payroll', fn () => view('app.page', [
        'livewireComponent' => 'hr.payroll.index',
        'title' => 'Payroll',
    ]))->name('hr.payroll.index');

    Route::get('/payroll/batch/{batch}', fn (PayrollBatch $batch) => view('app.page', [
        'livewireComponent' => 'hr.payroll.batch-detail',
        'livewireParams' => ['batch' => $batch],
        'title' => $batch->batch_number,
    ]))->name('hr.payroll.batch.detail');

    Route::get('/payroll/payslip/{payslip}/print', function (Payslip $payslip) {
        abort_unless(Auth::user()?->canAccessPayrollWorkflow() || Auth::user()?->is_hr, 403);
        abort_unless($payslip->status === 'issued', 404);

        $payslip->loadMissing('document');
        $payslip->document?->accessLogs()->create([
            'user_id' => Auth::id(),
            'action' => 'viewed',
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        return app(PayslipViewService::class)->inlinePrintResponse($payslip);
    })->name('hr.payroll.payslip.print');

    Route::get('/payroll/batch/{batch}/employee/{item}/leave-review', fn (
        PayrollBatch $batch,
        PayrollItem $item,
    ) => view('app.page', [
        'livewireComponent' => 'hr.payroll.leave-review',
        'livewireParams' => ['batch' => $batch, 'item' => $item],
        'title' => 'Leave Review',
    ]))->name('hr.payroll.leave.review');

    Route::get('/payroll/batch/{batch}/employee/{item}/adjustments', fn (
        PayrollBatch $batch,
        PayrollItem $item,
    ) => view('app.page', [
        'livewireComponent' => 'hr.payroll.item-adjustment',
        'livewireParams'    => ['batch' => $batch, 'item' => $item],
        'title'             => 'Manual Adjustments',
    ]))->name('hr.payroll.item.adjustments');

    Route::get('/documents', fn () => view('app.page', [
        'livewireComponent' => 'hr.documents.index',
        'title' => 'Documents',
    ]))->name('hr.documents.index');

    Route::get('/transfers', fn () => view('app.page', [
        'livewireComponent' => 'hr.transfers.index',
        'title' => 'Transfers',
    ]))->name('hr.transfers.index');

    Route::get('/reports', fn () => view('app.page', [
        'livewireComponent' => 'hr.reports.index',
        'title' => 'Reports',
    ]))->name('hr.reports.index');

    Route::get('/policy', fn () => view('app.page', [
        'livewireComponent' => 'hr.policy.index',
        'title' => 'Policy',
    ]))->name('hr.policy.index');

    Route::get('/signatures', fn () => view('app.page', [
        'livewireComponent' => 'hr.signatures.index',
        'title' => 'Manage Signature',
    ]))->name('hr.signatures.index');
});

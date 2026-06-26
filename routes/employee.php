<?php

use App\Http\Middleware\EnsureEmployeeUser;
use App\Http\Middleware\EnsurePasswordIsChanged;
use App\Models\Payslip;
use App\Services\Payroll\PayslipViewService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::prefix('employee')
    ->name('employee.')
    ->middleware([EnsureEmployeeUser::class, EnsurePasswordIsChanged::class])
    ->group(function () {
        Route::get('/dashboard', fn () => view('app.page', [
            'livewireComponent' => 'employee.dashboard',
            'title' => 'Employee Dashboard',
        ]))->name('dashboard');

        Route::get('/profile', fn () => view('app.page', [
            'livewireComponent' => 'employee.profile.index',
            'title' => 'Profile',
        ]))->name('profile.index');

        Route::get('/attendance', fn () => view('app.page', [
            'livewireComponent' => 'employee.attendance.index',
            'title' => 'Attendance & Leave',
        ]))->name('attendance.index');

        Route::get('/leave', fn () => view('app.page', [
            'livewireComponent' => 'employee.attendance.index',
            'livewireParams' => ['openApply' => true],
            'title' => 'Attendance & Leave',
        ]))->name('leave.index');

        Route::get('/documents', fn () => view('app.page', [
            'livewireComponent' => 'employee.documents.index',
            'title' => 'Documents',
        ]))->name('documents.index');

        Route::get('/payslips', fn () => view('app.page', [
            'livewireComponent' => 'employee.payslips.index',
            'title' => 'Payslips',
        ]))->name('payslips.index');

        Route::get('/payslips/{payslip}/print', function (Payslip $payslip) {
            $employee = Auth::user()->employee()->firstOrFail();

            abort_unless($payslip->status === 'issued', 404);
            abort_unless(
                $payslip->payrollItem()->where('employee_id', $employee->id)->exists(),
                403,
            );

            $payslip->loadMissing('document');
            $payslip->document?->accessLogs()->create([
                'user_id' => Auth::id(),
                'action' => 'download',
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
            $payslip->increment('download_count');

            return app(PayslipViewService::class)->inlinePrintResponse($payslip);
        })->name('payslips.print');

        Route::get('/policy', fn () => view('app.page', [
            'livewireComponent' => 'employee.policy.index',
            'title' => 'Policy',
        ]))->name('policy.index');
    });

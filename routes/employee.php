<?php

use App\Http\Middleware\EnsureEmployeeUser;
use App\Http\Middleware\EnsurePasswordIsChanged;
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
            'title' => 'Attendance',
        ]))->name('attendance.index');

        Route::get('/leave', fn () => view('app.page', [
            'livewireComponent' => 'employee.leave.index',
            'title' => 'Leave',
        ]))->name('leave.index');

        Route::get('/documents', fn () => view('app.page', [
            'livewireComponent' => 'employee.documents.index',
            'title' => 'Documents',
        ]))->name('documents.index');

        Route::get('/payslips', fn () => view('app.page', [
            'livewireComponent' => 'employee.payslips.index',
            'title' => 'Payslips',
        ]))->name('payslips.index');
    });

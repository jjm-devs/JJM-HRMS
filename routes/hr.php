<?php

use App\Http\Middleware\EnsureHrUser;
use App\Http\Middleware\EnsurePasswordIsChanged;
use App\Models\Employee;
use Illuminate\Support\Facades\Route;

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
        'title' => 'Attendance',
    ]))->name('hr.attendance.index');

    Route::get('/leave', fn () => view('app.page', [
        'livewireComponent' => 'hr.leave.index',
        'title' => 'Leave',
    ]))->name('hr.leave.index');

    Route::get('/payroll', fn () => view('app.page', [
        'livewireComponent' => 'hr.payroll.index',
        'title' => 'Payroll',
    ]))->name('hr.payroll.index');

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
});

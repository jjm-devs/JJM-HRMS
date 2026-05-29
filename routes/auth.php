<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::view('/', 'auth.login')->name('login');

Route::middleware('auth')->get('/change-password', fn () => view('app.page', [
    'livewireComponent' => 'auth.change-password',
    'title' => 'Change Password',
]))->name('password.change');

Route::post('/logout', function () {
    Auth::logout();

    request()->session()->invalidate();
    request()->session()->regenerateToken();

    return redirect()->route('login');
})->name('logout');

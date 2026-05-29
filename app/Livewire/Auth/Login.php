<?php

namespace App\Livewire\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class Login extends Component
{
    public string $email = '';

    public string $password = '';

    public bool $remember = false;

    public function mount(): void
    {
        if (! Auth::check()) {
            return;
        }

        if (! Auth::user()->is_admin && Auth::user()->must_change_password) {
            $this->redirectRoute('password.change', navigate: true);

            return;
        }

        if (Auth::user()->is_hr) {
            $this->redirectRoute('hr.dashboard', navigate: true);

            return;
        }

        if (! Auth::user()->is_admin) {
            $this->redirectRoute('employee.dashboard', navigate: true);
        }
    }

    public function authenticate(): void
    {
        $this->validate([
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string'],
        ]);

        $user = User::query()
            ->where('email', trim($this->email))
            ->first();

        if (! $user || ! Hash::check($this->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => 'The provided credentials do not match our records.',
            ]);
        }

        if (($user->status ?? 'active') !== 'active') {
            throw ValidationException::withMessages([
                'email' => 'This account is not active.',
            ]);
        }

        Auth::login($user, $this->remember);

        session()->regenerate();

        if (Auth::user()->is_admin) {
            Auth::logout();

            session()->invalidate();
            session()->regenerateToken();

            throw ValidationException::withMessages([
                'email' => 'Super Admin users should sign in from the admin panel.',
            ]);
        }

        if (Auth::user()->must_change_password) {
            $this->redirectRoute('password.change', navigate: true);

            return;
        }

        if (Auth::user()->is_hr) {
            $this->redirectRoute('hr.dashboard', navigate: true);

            return;
        }

        $this->redirectRoute('employee.dashboard', navigate: true);
    }

    public function render()
    {
        return view('livewire.auth.login');
    }
}

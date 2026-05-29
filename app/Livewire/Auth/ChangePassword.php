<?php

namespace App\Livewire\Auth;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class ChangePassword extends Component
{
    public string $current_password = '';

    public string $password = '';

    public string $password_confirmation = '';

    public function save()
    {
        $data = $this->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = Auth::user();

        if (! Hash::check($data['current_password'], $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => 'The current password is incorrect.',
            ]);
        }

        $user->update([
            'password' => $data['password'],
            'must_change_password' => false,
        ]);

        session()->regenerate();
        session()->flash('status', 'Password changed successfully.');

        if ($user->is_hr) {
            return redirect()->route('hr.dashboard');
        }

        if ($user->is_admin) {
            return redirect('/admin');
        }

        return redirect()->route('employee.dashboard');
    }

    public function render()
    {
        return view('livewire.auth.change-password');
    }
}

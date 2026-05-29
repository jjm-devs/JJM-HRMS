<?php

namespace Tests\Feature\Auth;

use App\Livewire\Auth\ChangePassword;
use App\Livewire\Auth\Login;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class ChangePasswordTest extends TestCase
{
    use RefreshDatabase;

    public function test_temporary_password_user_is_sent_to_change_password_after_login(): void
    {
        $user = User::query()->create([
            'name' => 'Employee User',
            'email' => 'employee@example.test',
            'password' => 'password',
            'is_hr' => false,
            'is_admin' => false,
            'status' => 'active',
            'must_change_password' => true,
        ]);

        Employee::query()->create([
            'user_id' => $user->id,
            'employee_code' => 'EMP-TEMP-00001',
            'full_name' => 'Temporary Employee',
            'service_status' => 'active',
        ]);

        Livewire::test(Login::class)
            ->set('email', 'employee@example.test')
            ->set('password', 'password')
            ->call('authenticate')
            ->assertRedirect(route('password.change'));
    }

    public function test_temporary_password_user_cannot_access_employee_pages_before_changing_password(): void
    {
        $user = User::query()->create([
            'name' => 'Employee User',
            'email' => 'employee@example.test',
            'password' => 'password',
            'is_hr' => false,
            'is_admin' => false,
            'status' => 'active',
            'must_change_password' => true,
        ]);

        Employee::query()->create([
            'user_id' => $user->id,
            'employee_code' => 'EMP-TEMP-00002',
            'full_name' => 'Temporary Employee',
            'service_status' => 'active',
        ]);

        $this->actingAs($user)
            ->get(route('employee.dashboard'))
            ->assertRedirect(route('password.change'));
    }

    public function test_user_can_change_temporary_password(): void
    {
        $user = User::query()->create([
            'name' => 'Employee User',
            'email' => 'employee@example.test',
            'password' => 'password',
            'is_hr' => false,
            'is_admin' => false,
            'status' => 'active',
            'must_change_password' => true,
        ]);

        Employee::query()->create([
            'user_id' => $user->id,
            'employee_code' => 'EMP-TEMP-00003',
            'full_name' => 'Temporary Employee',
            'service_status' => 'active',
        ]);

        $this->actingAs($user);

        Livewire::test(ChangePassword::class)
            ->set('current_password', 'password')
            ->set('password', 'new-password-123')
            ->set('password_confirmation', 'new-password-123')
            ->call('save')
            ->assertRedirect(route('employee.dashboard'));

        $user->refresh();

        $this->assertFalse($user->must_change_password);
        $this->assertTrue(Hash::check('new-password-123', $user->password));
    }
}

<?php

namespace Tests\Feature\Hr;

use App\Livewire\Auth\Login;
use App\Livewire\Hr\Employees\Show;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class EmployeeLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_hr_can_create_employee_login(): void
    {
        $hr = User::query()->create([
            'name' => 'HR User',
            'email' => 'hr@example.test',
            'password' => 'password',
            'is_hr' => true,
            'status' => 'active',
        ]);

        $employee = Employee::query()->create([
            'employee_code' => 'EMP-LOGIN-00001',
            'full_name' => 'Login Employee',
            'service_status' => 'active',
        ]);

        $this->actingAs($hr);

        Livewire::test(Show::class, ['employee' => $employee])
            ->call('createEmployeeLogin')
            ->assertSet('generatedLogin.email', 'emp-login-00001@employee.jjmbrain.local');

        $employee->refresh();

        $this->assertNotNull($employee->user_id);
        $this->assertTrue($employee->user->must_change_password);
        $this->assertFalse($employee->user->is_hr);
        $this->assertFalse($employee->user->is_admin);
    }

    public function test_employee_can_login_with_email(): void
    {
        $user = User::query()->create([
            'name' => 'Employee User',
            'email' => 'employee@example.test',
            'password' => 'password',
            'is_hr' => false,
            'is_admin' => false,
            'status' => 'active',
        ]);

        Employee::query()->create([
            'user_id' => $user->id,
            'employee_code' => 'EMP-CODE-00001',
            'full_name' => 'Code Login Employee',
            'service_status' => 'active',
        ]);

        Livewire::test(Login::class)
            ->set('email', 'employee@example.test')
            ->set('password', 'password')
            ->call('authenticate')
            ->assertRedirect(route('employee.dashboard'));
    }
}

<?php

namespace Tests\Feature\Hr;

use App\Livewire\Hr\Employees\Show;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class EmployeeContactTest extends TestCase
{
    use RefreshDatabase;

    public function test_hr_can_add_update_and_delete_employee_contact(): void
    {
        $hr = User::query()->create([
            'name' => 'HR User',
            'email' => 'hr@example.test',
            'password' => 'password',
            'is_hr' => true,
            'status' => 'active',
        ]);

        $employee = Employee::query()->create([
            'employee_code' => 'EMP-TEST-00001',
            'full_name' => 'Test Employee',
            'service_status' => 'active',
        ]);

        $this->actingAs($hr);

        Livewire::test(Show::class, ['employee' => $employee])
            ->set('activeTab', 'contacts')
            ->set('contactForm.type', 'mobile')
            ->set('contactForm.label', 'Personal')
            ->set('contactForm.value', '9876543210')
            ->set('contactForm.is_primary', true)
            ->call('saveContact')
            ->assertHasNoErrors();

        $contact = $employee->contacts()->firstOrFail();

        $this->assertDatabaseHas('employee_contacts', [
            'id' => $contact->id,
            'employee_id' => $employee->id,
            'type' => 'mobile',
            'label' => 'Personal',
            'value' => '9876543210',
            'is_primary' => true,
        ]);

        Livewire::test(Show::class, ['employee' => $employee->fresh()])
            ->call('editContact', $contact->id)
            ->set('contactForm.value', '9123456780')
            ->call('saveContact')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('employee_contacts', [
            'id' => $contact->id,
            'value' => '9123456780',
        ]);

        Livewire::test(Show::class, ['employee' => $employee->fresh()])
            ->call('deleteContact', $contact->id)
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('employee_contacts', [
            'id' => $contact->id,
        ]);
    }
}

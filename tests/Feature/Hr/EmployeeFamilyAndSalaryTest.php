<?php

namespace Tests\Feature\Hr;

use App\Livewire\Hr\Employees\Show;
use App\Models\Employee;
use App\Models\PayLevel;
use App\Models\PayMatrix;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class EmployeeFamilyAndSalaryTest extends TestCase
{
    use RefreshDatabase;

    public function test_hr_can_manage_employee_family_members(): void
    {
        $hr = User::query()->create([
            'name' => 'HR User',
            'email' => 'hr-family@example.test',
            'password' => 'password',
            'is_hr' => true,
            'status' => 'active',
        ]);

        $employee = Employee::query()->create([
            'employee_code' => 'EMP-FAMILY-00001',
            'full_name' => 'Family Employee',
            'service_status' => 'active',
        ]);

        $this->actingAs($hr);

        Livewire::test(Show::class, ['employee' => $employee])
            ->set('activeTab', 'family')
            ->set('familyForm.name', 'Test Spouse')
            ->set('familyForm.relationship', 'spouse')
            ->set('familyForm.gender', 'female')
            ->set('familyForm.mobile', '9876543210')
            ->set('familyForm.is_dependent', true)
            ->set('familyForm.is_nominee', true)
            ->set('familyForm.nominee_share', '100')
            ->call('saveFamilyMember')
            ->assertHasNoErrors();

        $familyMember = $employee->familyMembers()->firstOrFail();

        $this->assertDatabaseHas('employee_family_members', [
            'id' => $familyMember->id,
            'employee_id' => $employee->id,
            'name' => 'Test Spouse',
            'relationship' => 'spouse',
            'is_dependent' => true,
            'is_nominee' => true,
        ]);

        Livewire::test(Show::class, ['employee' => $employee->fresh()])
            ->call('editFamilyMember', $familyMember->id)
            ->set('familyForm.mobile', '9123456780')
            ->call('saveFamilyMember')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('employee_family_members', [
            'id' => $familyMember->id,
            'mobile' => '9123456780',
        ]);

        Livewire::test(Show::class, ['employee' => $employee->fresh()])
            ->call('deleteFamilyMember', $familyMember->id)
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('employee_family_members', [
            'id' => $familyMember->id,
        ]);
    }

    public function test_hr_can_update_employee_salary_details(): void
    {
        $hr = User::query()->create([
            'name' => 'HR User',
            'email' => 'hr-salary@example.test',
            'password' => 'password',
            'is_hr' => true,
            'status' => 'active',
        ]);

        $employee = Employee::query()->create([
            'employee_code' => 'EMP-SALARY-00001',
            'full_name' => 'Salary Employee',
            'service_status' => 'active',
        ]);

        $payMatrix = PayMatrix::query()->create([
            'name' => 'Test Pay Matrix',
            'code' => 'TPM',
            'status' => 'active',
        ]);

        $payLevel = PayLevel::query()->create([
            'pay_matrix_id' => $payMatrix->id,
            'name' => 'Level 1',
            'code' => 'L1',
            'level_order' => 1,
            'min_basic' => 20000,
            'max_basic' => 60000,
            'status' => 'active',
        ]);

        $this->actingAs($hr);

        Livewire::test(Show::class, ['employee' => $employee])
            ->set('activeTab', 'salary')
            ->set('salaryForm.pay_level_id', (string) $payLevel->id)
            ->set('salaryForm.basic_salary', '35000')
            ->set('salaryForm.grade_pay', '4200')
            ->set('salaryForm.effective_from', '2026-05-01')
            ->set('salaryForm.status', 'active')
            ->call('saveSalary')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('salary_structures', [
            'employee_id' => $employee->id,
            'pay_level_id' => $payLevel->id,
            'basic_salary' => 35000,
            'grade_pay' => 4200,
            'status' => 'active',
        ]);

        $this->assertSame('2026-05-01', $employee->salaryStructures()->firstOrFail()->effective_from->toDateString());
    }
}

<?php

namespace Tests\Feature\Hr;

use App\Livewire\Hr\Employees\Show;
use App\Models\Employee;
use App\Models\PayLevel;
use App\Models\PayMatrix;
use App\Models\SalaryComponent;
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

        $basicSalary = SalaryComponent::query()->create([
            'name' => 'Basic Salary',
            'code' => 'BASIC',
            'type' => 'earning',
            'calculation_type' => 'fixed',
            'default_amount' => 0,
            'status' => 'active',
        ]);

        $this->actingAs($hr);

        Livewire::test(Show::class, ['employee' => $employee])
            ->set('activeTab', 'salary')
            ->set('salaryComponentForm.pay_level_id', (string) $payLevel->id)
            ->set('salaryComponentForm.salary_component_id', (string) $basicSalary->id)
            ->set('salaryComponentForm.amount', '35000')
            ->set('salaryComponentForm.grade_pay', '4200')
            ->set('salaryComponentForm.calculation_type', 'fixed')
            ->set('salaryComponentForm.effective_from', '2026-05-01')
            ->set('salaryComponentForm.salary_structure_status', 'active')
            ->set('salaryComponentForm.status', 'active')
            ->call('saveSalaryComponent')
            ->assertHasNoErrors();

        $salaryStructure = $employee->salaryStructures()->firstOrFail();

        $this->assertDatabaseHas('salary_structures', [
            'id' => $salaryStructure->id,
            'employee_id' => $employee->id,
            'pay_level_id' => $payLevel->id,
            'basic_salary' => 35000,
            'grade_pay' => 4200,
            'status' => 'active',
        ]);

        $this->assertDatabaseHas('employee_salary_components', [
            'salary_structure_id' => $salaryStructure->id,
            'salary_component_id' => $basicSalary->id,
            'amount' => 35000,
            'calculation_type' => 'fixed',
            'status' => 'active',
        ]);

        $this->assertSame('2026-05-01', $salaryStructure->effective_from->toDateString());
    }

    public function test_hr_can_manage_employee_salary_components(): void
    {
        $hr = User::query()->create([
            'name' => 'HR User',
            'email' => 'hr-salary-components@example.test',
            'password' => 'password',
            'is_hr' => true,
            'status' => 'active',
        ]);

        $employee = Employee::query()->create([
            'employee_code' => 'EMP-SALARY-COMP-00001',
            'full_name' => 'Salary Component Employee',
            'service_status' => 'active',
        ]);

        $salaryStructure = $employee->salaryStructures()->create([
            'basic_salary' => 35000,
            'status' => 'active',
        ]);

        $salaryComponent = SalaryComponent::query()->create([
            'name' => 'House Rent Allowance',
            'code' => 'HRA-TEST',
            'type' => 'earning',
            'calculation_type' => 'percentage',
            'default_amount' => 0,
            'status' => 'active',
        ]);

        $this->actingAs($hr);

        Livewire::test(Show::class, ['employee' => $employee])
            ->set('activeTab', 'salary')
            ->set('salaryComponentForm.salary_component_id', (string) $salaryComponent->id)
            ->set('salaryComponentForm.calculation_type', 'percentage')
            ->set('salaryComponentForm.percentage_rate', '12')
            ->set('salaryComponentForm.calculation_base', 'basic_salary')
            ->set('salaryComponentForm.formula', '12% of basic')
            ->set('salaryComponentForm.status', 'active')
            ->call('saveSalaryComponent')
            ->assertHasNoErrors();

        $component = $salaryStructure->employeeSalaryComponents()->firstOrFail();

        $this->assertDatabaseHas('employee_salary_components', [
            'id' => $component->id,
            'salary_structure_id' => $salaryStructure->id,
            'salary_component_id' => $salaryComponent->id,
            'amount' => 4200,
            'percentage_rate' => 12,
            'calculation_type' => 'percentage',
            'calculation_base' => 'basic_salary',
            'formula' => '12% of basic',
            'status' => 'active',
        ]);

        Livewire::test(Show::class, ['employee' => $employee->fresh()])
            ->call('editSalaryComponent', $component->id)
            ->set('salaryComponentForm.percentage_rate', '10')
            ->call('saveSalaryComponent')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('employee_salary_components', [
            'id' => $component->id,
            'amount' => 3500,
            'percentage_rate' => 10,
        ]);

        $taxComponent = SalaryComponent::query()->create([
            'name' => 'Income Tax',
            'code' => 'TAX-TEST',
            'type' => 'deduction',
            'calculation_type' => 'percentage',
            'default_amount' => 0,
            'is_deduction' => true,
            'status' => 'active',
        ]);

        Livewire::test(Show::class, ['employee' => $employee->fresh()])
            ->set('salaryComponentForm.salary_component_id', (string) $taxComponent->id)
            ->set('salaryComponentForm.calculation_type', 'percentage')
            ->set('salaryComponentForm.percentage_rate', '10')
            ->set('salaryComponentForm.calculation_base', 'gross_earnings')
            ->call('saveSalaryComponent')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('employee_salary_components', [
            'salary_structure_id' => $salaryStructure->id,
            'salary_component_id' => $taxComponent->id,
            'amount' => 3850,
            'percentage_rate' => 10,
            'calculation_type' => 'percentage',
            'calculation_base' => 'gross_earnings',
        ]);

        Livewire::test(Show::class, ['employee' => $employee->fresh()])
            ->call('deleteSalaryComponent', $component->id)
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('employee_salary_components', [
            'id' => $component->id,
        ]);
    }
}

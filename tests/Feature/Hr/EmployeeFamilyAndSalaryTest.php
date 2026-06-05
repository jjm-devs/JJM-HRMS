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

    public function test_hr_can_manage_employee_qualifications(): void
    {
        $hr = User::query()->create([
            'name' => 'HR User',
            'email' => 'hr-qualifications@example.test',
            'password' => 'password',
            'is_hr' => true,
            'status' => 'active',
        ]);

        $employee = Employee::query()->create([
            'employee_code' => 'EMP-QUAL-00001',
            'full_name' => 'Qualification Employee',
            'service_status' => 'active',
        ]);

        $this->actingAs($hr);

        Livewire::test(Show::class, ['employee' => $employee])
            ->set('activeTab', 'qualifications')
            ->set('qualificationForm.qualification', 'B.Tech')
            ->set('qualificationForm.specialization', 'Civil Engineering')
            ->set('qualificationForm.institution', 'Assam Engineering College')
            ->set('qualificationForm.board_or_university', 'Gauhati University')
            ->set('qualificationForm.year_of_passing', '2020')
            ->set('qualificationForm.percentage_or_cgpa', '8.1 CGPA')
            ->set('qualificationForm.status', 'active')
            ->call('saveQualification')
            ->assertHasNoErrors();

        $qualification = $employee->qualifications()->firstOrFail();

        $this->assertDatabaseHas('employee_qualifications', [
            'id' => $qualification->id,
            'employee_id' => $employee->id,
            'qualification' => 'B.Tech',
            'specialization' => 'Civil Engineering',
            'status' => 'active',
        ]);

        Livewire::test(Show::class, ['employee' => $employee->fresh()])
            ->call('editQualification', $qualification->id)
            ->set('qualificationForm.percentage_or_cgpa', '8.4 CGPA')
            ->call('saveQualification')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('employee_qualifications', [
            'id' => $qualification->id,
            'percentage_or_cgpa' => '8.4 CGPA',
        ]);

        Livewire::test(Show::class, ['employee' => $employee->fresh()])
            ->call('removeQualification', $qualification->id)
            ->assertHasNoErrors();

        $this->assertDatabaseHas('employee_qualifications', [
            'id' => $qualification->id,
            'status' => 'inactive',
        ]);

        Livewire::test(Show::class, ['employee' => $employee->fresh()])
            ->call('editQualification', $qualification->id)
            ->set('qualificationForm.status', 'active')
            ->call('saveQualification')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('employee_qualifications', [
            'id' => $qualification->id,
            'status' => 'active',
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
            ->set('activeTab', 'salary')
            ->assertSee('Payroll Preview')
            ->assertSee('38,500.00')
            ->assertSee('3,850.00')
            ->assertSee('34,650.00')
            ->assertSee('10.00% on Gross Earnings');

        Livewire::test(Show::class, ['employee' => $employee->fresh()])
            ->call('removeSalaryComponent', $component->id)
            ->assertHasNoErrors();

        $this->assertDatabaseHas('employee_salary_components', [
            'id' => $component->id,
            'status' => 'inactive',
        ]);

        $this->assertDatabaseHas('employee_salary_components', [
            'salary_structure_id' => $salaryStructure->id,
            'salary_component_id' => $taxComponent->id,
            'amount' => 3500,
        ]);

        Livewire::test(Show::class, ['employee' => $employee->fresh()])
            ->set('salaryComponentForm.salary_component_id', (string) $salaryComponent->id)
            ->set('salaryComponentForm.calculation_type', 'percentage')
            ->set('salaryComponentForm.percentage_rate', '8')
            ->set('salaryComponentForm.calculation_base', 'basic_salary')
            ->set('salaryComponentForm.status', 'active')
            ->call('saveSalaryComponent')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('employee_salary_components', [
            'id' => $component->id,
            'amount' => 2800,
            'percentage_rate' => 8,
            'status' => 'active',
        ]);

        $this->assertSame(2, $salaryStructure->employeeSalaryComponents()->count());
    }

    public function test_salary_component_edit_updates_current_salary_structure(): void
    {
        $hr = User::query()->create([
            'name' => 'HR User',
            'email' => 'hr-current-salary@example.test',
            'password' => 'password',
            'is_hr' => true,
            'status' => 'active',
        ]);

        $employee = Employee::query()->create([
            'employee_code' => 'EMP-CURRENT-SALARY-00001',
            'full_name' => 'Current Salary Employee',
            'service_status' => 'active',
        ]);

        $basicSalary = SalaryComponent::query()->create([
            'name' => 'Basic Salary',
            'code' => 'BASIC',
            'type' => 'earning',
            'calculation_type' => 'fixed',
            'default_amount' => 0,
            'status' => 'active',
        ]);

        $hra = SalaryComponent::query()->create([
            'name' => 'House Rent Allowance',
            'code' => 'HRA-REVISION',
            'type' => 'earning',
            'calculation_type' => 'percentage',
            'default_amount' => 0,
            'status' => 'active',
        ]);

        $oldStructure = $employee->salaryStructures()->create([
            'basic_salary' => 35000,
            'status' => 'active',
        ]);

        $oldBasicComponent = $oldStructure->employeeSalaryComponents()->create([
            'salary_component_id' => $basicSalary->id,
            'amount' => 35000,
            'calculation_type' => 'fixed',
            'status' => 'active',
        ]);

        $oldStructure->employeeSalaryComponents()->create([
            'salary_component_id' => $hra->id,
            'amount' => 3500,
            'percentage_rate' => 10,
            'calculation_type' => 'percentage',
            'calculation_base' => 'basic_salary',
            'status' => 'active',
        ]);

        $this->actingAs($hr);

        Livewire::test(Show::class, ['employee' => $employee])
            ->call('editSalaryComponent', $oldBasicComponent->id)
            ->set('salaryComponentForm.amount', '40000')
            ->call('saveSalaryComponent')
            ->assertHasNoErrors();

        $this->assertSame(1, $employee->salaryStructures()->count());

        $oldStructure->refresh();

        $this->assertSame('40000.00', $oldStructure->basic_salary);

        $this->assertDatabaseHas('employee_salary_components', [
            'salary_structure_id' => $oldStructure->id,
            'salary_component_id' => $basicSalary->id,
            'amount' => 40000,
        ]);

        $this->assertDatabaseHas('employee_salary_components', [
            'salary_structure_id' => $oldStructure->id,
            'salary_component_id' => $hra->id,
            'amount' => 4000,
        ]);
    }

    public function test_salary_components_render_in_business_order(): void
    {
        $hr = User::query()->create([
            'name' => 'HR User',
            'email' => 'hr-order@example.test',
            'password' => 'password',
            'is_hr' => true,
            'status' => 'active',
        ]);

        $employee = Employee::query()->create([
            'employee_code' => 'EMP-ORDER-00001',
            'full_name' => 'Ordered Salary Employee',
            'service_status' => 'active',
        ]);

        $basicSalary = SalaryComponent::query()->create([
            'name' => 'Basic Salary',
            'code' => 'BASIC',
            'type' => 'earning',
            'calculation_type' => 'fixed',
            'default_amount' => 0,
            'status' => 'active',
        ]);

        $hra = SalaryComponent::query()->create([
            'name' => 'House Rent Allowance',
            'code' => 'HRA',
            'type' => 'earning',
            'calculation_type' => 'fixed',
            'default_amount' => 0,
            'status' => 'active',
        ]);

        $tax = SalaryComponent::query()->create([
            'name' => 'Income Tax',
            'code' => 'TAX',
            'type' => 'deduction',
            'calculation_type' => 'fixed',
            'default_amount' => 0,
            'is_deduction' => true,
            'status' => 'active',
        ]);

        $salaryStructure = $employee->salaryStructures()->create([
            'basic_salary' => 35000,
            'status' => 'active',
        ]);

        $salaryStructure->employeeSalaryComponents()->create([
            'salary_component_id' => $tax->id,
            'amount' => 2500,
            'calculation_type' => 'fixed',
            'status' => 'active',
        ]);

        $salaryStructure->employeeSalaryComponents()->create([
            'salary_component_id' => $hra->id,
            'amount' => 4200,
            'calculation_type' => 'fixed',
            'status' => 'active',
        ]);

        $salaryStructure->employeeSalaryComponents()->create([
            'salary_component_id' => $basicSalary->id,
            'amount' => 35000,
            'calculation_type' => 'fixed',
            'status' => 'active',
        ]);

        $this->actingAs($hr);

        Livewire::test(Show::class, ['employee' => $employee])
            ->set('activeTab', 'salary')
            ->assertSeeInOrder([
                'Basic Salary',
                'House Rent Allowance',
                'Income Tax',
            ]);
    }

    public function test_percentage_component_requires_non_zero_calculation_base(): void
    {
        $hr = User::query()->create([
            'name' => 'HR User',
            'email' => 'hr-zero-base@example.test',
            'password' => 'password',
            'is_hr' => true,
            'status' => 'active',
        ]);

        $employee = Employee::query()->create([
            'employee_code' => 'EMP-ZERO-BASE-00001',
            'full_name' => 'Zero Base Employee',
            'service_status' => 'active',
        ]);

        $employee->salaryStructures()->create([
            'basic_salary' => 0,
            'status' => 'active',
        ]);

        $tax = SalaryComponent::query()->create([
            'name' => 'Income Tax',
            'code' => 'TAX-ZERO-BASE',
            'type' => 'deduction',
            'calculation_type' => 'percentage',
            'default_amount' => 0,
            'is_deduction' => true,
            'status' => 'active',
        ]);

        $this->actingAs($hr);

        Livewire::test(Show::class, ['employee' => $employee])
            ->set('activeTab', 'salary')
            ->set('salaryComponentForm.salary_component_id', (string) $tax->id)
            ->set('salaryComponentForm.calculation_type', 'percentage')
            ->set('salaryComponentForm.percentage_rate', '10')
            ->set('salaryComponentForm.calculation_base', 'gross_earnings')
            ->call('saveSalaryComponent')
            ->assertHasErrors(['salaryComponentForm.calculation_base']);

        $this->assertDatabaseMissing('employee_salary_components', [
            'salary_component_id' => $tax->id,
        ]);
    }
}

<?php

namespace Tests\Feature\Hr;

use App\Livewire\Hr\Payroll\Index as PayrollIndex;
use App\Models\Employee;
use App\Models\HrScopeAssignment;
use App\Models\OrgUnit;
use App\Models\SalaryComponent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PayrollMultiOfficeTest extends TestCase
{
    use RefreshDatabase;

    public function test_head_office_hr_generates_across_selected_offices_and_selection_is_remembered(): void
    {
        [$hr, $officeA, $officeB, $empA, $empB] = $this->fixture();

        $this->actingAs($hr);

        // At least one office must be selected.
        Livewire::test(PayrollIndex::class)
            ->set('orgUnitIds', [])
            ->set('periodFrom', '2026-06-01')
            ->set('periodTo', '2026-06-30')
            ->call('generate')
            ->assertHasErrors(['orgUnitIds']);

        // Generate for office A only (of the two in scope).
        Livewire::test(PayrollIndex::class)
            ->set('orgUnitIds', [(string) $officeA->id])
            ->set('periodFrom', '2026-06-01')
            ->set('periodTo', '2026-06-30')
            ->set('paymentDate', '2026-06-30')
            ->call('generate')
            ->assertHasNoErrors();

        $batch = \App\Models\PayrollBatch::query()->latest('id')->firstOrFail();

        // Only office A's employee is included.
        $this->assertSame(1, $batch->items()->count());
        $this->assertTrue($batch->items()->where('employee_id', $empA->id)->exists());
        $this->assertFalse($batch->items()->where('employee_id', $empB->id)->exists());

        // Selection was remembered on the user.
        $this->assertSame(
            [$officeA->id],
            $hr->fresh()->payroll_generation_defaults['org_unit_ids'] ?? null,
        );

        // Re-opening the modal pre-selects the remembered office.
        Livewire::test(PayrollIndex::class)
            ->call('openGenerateModal')
            ->assertSet('orgUnitIds', [(string) $officeA->id]);
    }

    /**
     * @return array{0: User, 1: OrgUnit, 2: OrgUnit, 3: Employee, 4: Employee}
     */
    private function fixture(): array
    {
        $head = OrgUnit::query()->create([
            'name' => 'Chief Head Office', 'code' => 'HEAD-'.uniqid(), 'type' => 'head_office', 'status' => 'active',
        ]);
        $officeA = OrgUnit::query()->create([
            'name' => 'Guwahati Division', 'code' => 'A-'.uniqid(), 'type' => 'division', 'parent_id' => $head->id, 'status' => 'active',
        ]);
        $officeB = OrgUnit::query()->create([
            'name' => 'Guwahati Circle', 'code' => 'B-'.uniqid(), 'type' => 'circle', 'parent_id' => $head->id, 'status' => 'active',
        ]);

        $hr = User::query()->create([
            'name' => 'Head HR', 'email' => 'head-hr@example.test', 'password' => 'password', 'is_hr' => true, 'status' => 'active',
        ]);
        HrScopeAssignment::query()->create([
            'user_id' => $hr->id, 'org_unit_id' => $head->id, 'is_ho' => true, 'include_child_units' => true,
            'can_view' => true, 'can_create' => true, 'can_update' => true, 'status' => 'active',
        ]);

        $basic = SalaryComponent::query()->create([
            'name' => 'Basic Salary', 'code' => 'BASIC', 'type' => 'earning', 'calculation_type' => 'fixed',
            'default_amount' => 0, 'status' => 'active',
        ]);

        $empA = $this->employeeIn($officeA, 'EMP-A-0001', $basic);
        $empB = $this->employeeIn($officeB, 'EMP-B-0001', $basic);

        return [$hr, $officeA, $officeB, $empA, $empB];
    }

    private function employeeIn(OrgUnit $office, string $code, SalaryComponent $basic): Employee
    {
        $employee = Employee::query()->create([
            'employee_code' => $code, 'full_name' => $code, 'org_unit_id' => $office->id, 'service_status' => 'active',
        ]);
        $structure = $employee->salaryStructures()->create([
            'basic_salary' => 30000, 'grade_pay' => 0, 'status' => 'active',
        ]);
        $structure->employeeSalaryComponents()->create([
            'salary_component_id' => $basic->id, 'amount' => 30000, 'calculation_type' => 'fixed', 'status' => 'active',
        ]);

        return $employee;
    }
}

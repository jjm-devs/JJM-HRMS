<?php

namespace Tests\Feature\Hr;

use App\Models\Employee;
use App\Models\OrgUnit;
use App\Models\SalaryComponent;
use App\Models\StaffCategory;
use App\Models\User;
use App\Services\Payroll\PayrollGenerationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PayrollStaffCategoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_generation_can_filter_by_staff_category(): void
    {
        $hr = User::query()->create([
            'name' => 'HR', 'email' => 'cat-hr@example.test', 'password' => 'password', 'is_hr' => true, 'status' => 'active',
        ]);
        $office = OrgUnit::query()->create(['name' => 'Div', 'code' => 'DIV-'.uniqid(), 'type' => 'division', 'status' => 'active']);

        $support = StaffCategory::query()->create(['name' => 'Support', 'code' => 'SUPPORT', 'status' => 'active']);
        $wq = StaffCategory::query()->create(['name' => 'WQ', 'code' => 'WQ', 'status' => 'active']);

        $basic = SalaryComponent::query()->create([
            'name' => 'Basic Salary', 'code' => 'BASIC', 'type' => 'earning', 'calculation_type' => 'fixed', 'default_amount' => 0, 'status' => 'active',
        ]);

        $supportEmp = $this->employee('EMP-SUP-1', $office, $support, $basic);
        $wqEmp = $this->employee('EMP-WQ-1', $office, $wq, $basic);

        $this->actingAs($hr);

        // Generate for WQ only.
        $batch = app(PayrollGenerationService::class)->generate(
            periodFrom: '2026-06-01',
            periodTo: '2026-06-30',
            orgUnitIds: [$office->id],
            staffCategoryIds: [$wq->id],
        );

        $this->assertSame(1, $batch->items()->count());
        $this->assertTrue($batch->items()->where('employee_id', $wqEmp->id)->exists());
        $this->assertFalse($batch->items()->where('employee_id', $supportEmp->id)->exists());
    }

    private function employee(string $code, OrgUnit $office, StaffCategory $cat, SalaryComponent $basic): Employee
    {
        $employee = Employee::query()->create([
            'employee_code' => $code, 'full_name' => $code, 'org_unit_id' => $office->id,
            'staff_category_id' => $cat->id, 'service_status' => 'active',
        ]);
        $structure = $employee->salaryStructures()->create(['basic_salary' => 20000, 'grade_pay' => 0, 'status' => 'active']);
        $structure->employeeSalaryComponents()->create([
            'salary_component_id' => $basic->id, 'amount' => 20000, 'calculation_type' => 'fixed', 'status' => 'active',
        ]);

        return $employee;
    }
}

<?php

namespace Tests\Feature\Hr;

use App\Livewire\Hr\Payroll\Index as PayrollIndex;
use App\Models\Employee;
use App\Models\LeaveApplication;
use App\Models\LeaveType;
use App\Models\PayrollBatch;
use App\Models\PayrollItem;
use App\Models\PayrollItemLeaveAdjustment;
use App\Models\SalaryComponent;
use App\Models\User;
use App\Services\Payroll\PayrollGenerationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PayrollGenerationTest extends TestCase
{
    use RefreshDatabase;

    public function test_payroll_generation_uses_saved_components_and_classifies_leave(): void
    {
        $hr = User::query()->create([
            'name' => 'Payroll HR',
            'email' => 'hr-payroll-generation@example.test',
            'password' => 'password',
            'is_hr' => true,
            'status' => 'active',
        ]);

        $employee = Employee::query()->create([
            'employee_code' => 'EMP-PAYROLL-00001',
            'full_name' => 'Payroll Employee',
            'service_status' => 'active',
        ]);

        $structure = $employee->salaryStructures()->create([
            'basic_salary' => 35000,
            'grade_pay' => 4200,
            'status' => 'active',
        ]);

        $basic = SalaryComponent::query()->create([
            'name' => 'Basic Salary',
            'code' => 'BASIC',
            'type' => 'earning',
            'calculation_type' => 'fixed',
            'default_amount' => 0,
            'status' => 'active',
        ]);

        $hra = SalaryComponent::query()->create([
            'name' => 'House Rent Allowance',
            'code' => 'HRA-PAY',
            'type' => 'earning',
            'calculation_type' => 'percentage',
            'default_amount' => 0,
            'status' => 'active',
        ]);

        $formulaNote = SalaryComponent::query()->create([
            'name' => 'Special Allowance',
            'code' => 'SP-ALLOW',
            'type' => 'earning',
            'calculation_type' => 'formula',
            'default_amount' => 0,
            'formula' => 'basic_salary * 0.50',
            'status' => 'active',
        ]);

        $tax = SalaryComponent::query()->create([
            'name' => 'Professional Tax',
            'code' => 'PTAX',
            'type' => 'deduction',
            'calculation_type' => 'fixed',
            'default_amount' => 0,
            'is_deduction' => true,
            'status' => 'active',
        ]);

        $structure->employeeSalaryComponents()->createMany([
            [
                'salary_component_id' => $basic->id,
                'amount' => 35000,
                'calculation_type' => 'fixed',
                'status' => 'active',
            ],
            [
                'salary_component_id' => $hra->id,
                'amount' => 3500,
                'percentage_rate' => 10,
                'calculation_type' => 'percentage',
                'calculation_base' => 'basic_salary',
                'status' => 'active',
            ],
            [
                'salary_component_id' => $formulaNote->id,
                'amount' => 500,
                'calculation_type' => 'formula',
                'formula' => 'basic_salary * 0.50',
                'status' => 'active',
            ],
            [
                'salary_component_id' => $tax->id,
                'amount' => 1000,
                'calculation_type' => 'fixed',
                'status' => 'active',
            ],
        ]);

        // The Paid Leave bank type — 2 free days per calendar month, rest deducts.
        $paidLeave = LeaveType::query()->create([
            'name' => 'Paid Leave',
            'code' => 'PL',
            'is_paid' => true,
            'status' => 'active',
        ]);

        // 3 paid-leave days in June → first 2 banked, 3rd is excess (1 LWP day).
        $firstLeave = $this->approvedLeave($employee, $paidLeave, '2026-06-02');
        $secondLeave = $this->approvedLeave($employee, $paidLeave, '2026-06-03');
        $thirdLeave = $this->approvedLeave($employee, $paidLeave, '2026-06-04');

        $this->actingAs($hr);

        $batch = app(PayrollGenerationService::class)->generate(
            periodFrom: '2026-06-01',
            periodTo: '2026-06-30',
            paymentDate: '2026-06-30',
        );

        $item = $batch->items()->with(['components', 'leaveAdjustments'])->firstOrFail();

        $this->assertSame(43200.0, (float) $item->gross_salary);
        $this->assertSame(1.0, (float) $item->leave_without_pay_days);
        $this->assertSame(1440.0, (float) $item->lwp_deduction);
        $this->assertSame(2440.0, (float) $item->total_deductions);
        $this->assertSame(40760.0, (float) $item->net_salary);

        $this->assertSame(1, $item->components->where('name', 'Basic Salary')->count());
        $this->assertDatabaseHas('payroll_item_components', [
            'payroll_item_id' => $item->id,
            'salary_component_id' => $basic->id,
            'name' => 'Basic Salary',
            'amount' => 35000,
        ]);
        $this->assertDatabaseHas('payroll_item_components', [
            'payroll_item_id' => $item->id,
            'salary_component_id' => $formulaNote->id,
            'name' => 'Special Allowance',
            'amount' => 500,
            'calculation_details' => 'Formula note: basic_salary * 0.50',
        ]);

        $this->assertDatabaseHas('payroll_item_leave_adjustments', [
            'payroll_item_id' => $item->id,
            'leave_application_id' => $firstLeave->id,
            'auto_classification' => 'leave_bank',
            'deductible_days' => 0,
            'had_sufficient_balance' => true,
        ]);
        $this->assertDatabaseHas('payroll_item_leave_adjustments', [
            'payroll_item_id' => $item->id,
            'leave_application_id' => $secondLeave->id,
            'auto_classification' => 'leave_bank',
            'deductible_days' => 0,
        ]);
        $this->assertDatabaseHas('payroll_item_leave_adjustments', [
            'payroll_item_id' => $item->id,
            'leave_application_id' => $thirdLeave->id,
            'auto_classification' => 'salary_deduct',
            'deductible_days' => 1,
            'had_sufficient_balance' => false,
        ]);
    }

    public function test_overlapping_paid_leave_on_same_date_is_not_double_counted(): void
    {
        $hr = User::query()->create([
            'name' => 'Payroll HR',
            'email' => 'hr-overlap@example.test',
            'password' => 'password',
            'is_hr' => true,
            'status' => 'active',
        ]);

        $employee = Employee::query()->create([
            'employee_code' => 'EMP-OVERLAP-00001',
            'full_name' => 'Overlap Employee',
            'service_status' => 'active',
        ]);

        $structure = $employee->salaryStructures()->create([
            'basic_salary' => 30000,
            'grade_pay' => 0,
            'status' => 'active',
        ]);

        $basic = SalaryComponent::query()->create([
            'name' => 'Basic Salary',
            'code' => 'BASIC',
            'type' => 'earning',
            'calculation_type' => 'fixed',
            'default_amount' => 0,
            'status' => 'active',
        ]);
        $structure->employeeSalaryComponents()->create([
            'salary_component_id' => $basic->id,
            'amount' => 30000,
            'calculation_type' => 'fixed',
            'status' => 'active',
        ]);

        $paidLeave = LeaveType::query()->create([
            'name' => 'Paid Leave',
            'code' => 'PL',
            'is_paid' => true,
            'status' => 'active',
        ]);

        // Employee requests 25th–26th; HR manually records the 26th (overlaps).
        $this->approvedRange($employee, $paidLeave, '2026-06-25', '2026-06-26');
        $this->approvedLeave($employee, $paidLeave, '2026-06-26');

        $this->actingAs($hr);

        $batch = app(PayrollGenerationService::class)->generate(
            periodFrom: '2026-06-01',
            periodTo: '2026-06-30',
            paymentDate: '2026-06-30',
        );

        $item = $batch->items()->firstOrFail();

        // Only 2 distinct days (25, 26), both within the 2/month bank → no deduction.
        $this->assertSame(0.0, (float) $item->leave_without_pay_days);
        $this->assertSame(0.0, (float) $item->lwp_deduction);
        $this->assertSame(30000.0, (float) $item->net_salary);
    }

    public function test_payroll_pages_render_primary_actions_and_links(): void
    {
        $hr = User::query()->create([
            'name' => 'Payroll HR',
            'email' => 'hr-payroll-pages@example.test',
            'password' => 'password',
            'is_hr' => true,
            'status' => 'active',
        ]);

        $employee = Employee::query()->create([
            'employee_code' => 'EMP-PAYROLL-00002',
            'full_name' => 'Payroll Page Employee',
            'service_status' => 'active',
        ]);

        $batch = PayrollBatch::query()->create([
            'batch_number' => 'PAY-2026-06-001',
            'period_from' => '2026-06-01',
            'period_to' => '2026-06-30',
            'payment_date' => '2026-06-30',
            'generated_by' => $hr->id,
            'gross_total' => 1000,
            'net_total' => 900,
            'deduction_total' => 100,
            'status' => 'draft',
        ]);

        $item = PayrollItem::query()->create([
            'payroll_batch_id' => $batch->id,
            'employee_id' => $employee->id,
            'basic_salary' => 1000,
            'gross_salary' => 1000,
            'total_deductions' => 100,
            'net_salary' => 900,
            'status' => 'draft',
        ]);

        $leaveType = LeaveType::query()->create([
            'name' => 'Casual Leave',
            'code' => 'CL-PAGE',
            'is_paid' => true,
            'status' => 'active',
        ]);
        $leave = $this->approvedLeave($employee, $leaveType, '2026-06-04');

        PayrollItemLeaveAdjustment::query()->create([
            'payroll_item_id' => $item->id,
            'leave_application_id' => $leave->id,
            'leave_days' => 1,
            'auto_classification' => 'salary_deduct',
            'leave_type_name' => 'Casual Leave',
            'leave_type_is_paid' => true,
            'had_sufficient_balance' => false,
        ]);

        $this->actingAs($hr);

        Livewire::test(PayrollIndex::class)
            ->assertSee('Generate New Batch')
            ->call('openGenerateModal')
            ->assertDispatched('open-modal');

        $this->get(route('hr.payroll.index'))
            ->assertOk()
            ->assertSee(route('hr.payroll.batch.detail', $batch), false);

        $this->get(route('hr.payroll.batch.detail', $batch))
            ->assertOk()
            ->assertSee(route('hr.payroll.leave.review', [$batch, $item]), false);

        $this->get(route('hr.payroll.leave.review', [$batch, $item]))
            ->assertOk()
            ->assertSee(route('hr.payroll.batch.detail', $batch), false);
    }

    private function approvedLeave(Employee $employee, LeaveType $leaveType, string $date): LeaveApplication
    {
        return $this->approvedRange($employee, $leaveType, $date, $date);
    }

    private function approvedRange(Employee $employee, LeaveType $leaveType, string $startDate, string $endDate): LeaveApplication
    {
        $period = \Carbon\CarbonPeriod::create($startDate, $endDate);

        $leave = LeaveApplication::query()->create([
            'employee_id' => $employee->id,
            'leave_type_id' => $leaveType->id,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'total_days' => iterator_count($period),
            'reason' => 'Payroll test leave',
            'status' => LeaveApplication::STATUS_APPROVED,
            'approved_at' => now(),
        ]);

        foreach (\Carbon\CarbonPeriod::create($startDate, $endDate) as $date) {
            $leave->days()->create([
                'leave_date' => $date->format('Y-m-d'),
                'duration' => 1,
                'status' => LeaveApplication::STATUS_APPROVED,
            ]);
        }

        return $leave;
    }
}

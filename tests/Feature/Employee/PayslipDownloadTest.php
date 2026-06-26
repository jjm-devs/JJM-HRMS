<?php

namespace Tests\Feature\Employee;

use App\Livewire\Hr\Payroll\BatchDetail;
use App\Models\DocumentAccessLog;
use App\Models\Employee;
use App\Models\PayrollBatch;
use App\Models\PayrollItem;
use App\Models\Payslip;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class PayslipDownloadTest extends TestCase
{
    use RefreshDatabase;

    public function test_hr_can_generate_and_employee_can_download_payslip(): void
    {
        Storage::fake('local');

        $hr = User::query()->create([
            'name' => 'Payroll HR',
            'email' => 'payroll-hr@example.test',
            'password' => 'password',
            'is_hr' => true,
            'status' => 'active',
        ]);

        // Payslip generation is restricted to the Deputy MD workflow role.
        $deputyMd = Role::query()->firstOrCreate(
            ['code' => 'deputy_md'],
            ['name' => 'Deputy MD', 'status' => 'active'],
        );
        $hr->roles()->attach($deputyMd);

        $employeeUser = User::query()->create([
            'name' => 'Payslip Employee',
            'email' => 'payslip-employee@example.test',
            'password' => 'password',
            'status' => 'active',
        ]);

        $employee = Employee::query()->create([
            'user_id' => $employeeUser->id,
            'employee_code' => 'EMP-PAYSLIP-00001',
            'full_name' => 'Payslip Employee',
            'service_status' => 'active',
        ]);

        $batch = PayrollBatch::query()->create([
            'batch_number' => 'PAY-2026-06-001',
            'period_from' => '2026-06-01',
            'period_to' => '2026-06-30',
            'payment_date' => '2026-06-30',
            'generated_by' => $hr->id,
            'gross_total' => 1000,
            'deduction_total' => 100,
            'net_total' => 900,
            'status' => 'locked',
            'locked_at' => now(),
        ]);

        $item = PayrollItem::query()->create([
            'payroll_batch_id' => $batch->id,
            'employee_id' => $employee->id,
            'basic_salary' => 1000,
            'gross_salary' => 1000,
            'total_deductions' => 100,
            'net_salary' => 900,
            'disbursed_amount' => 900,
            'status' => 'approved',
        ]);

        $item->components()->create([
            'name' => 'Basic Salary',
            'type' => 'earning',
            'amount' => 1000,
            'calculation_details' => 'Fixed amount',
        ]);

        $this->actingAs($hr);

        Livewire::test(BatchDetail::class, ['batch' => $batch])
            ->call('generatePayslips')
            ->assertHasNoErrors();

        $payslip = Payslip::query()->with('document')->firstOrFail();

        $this->assertSame('issued', $payslip->status);
        $this->assertNotNull($payslip->document);
        Storage::disk('local')->assertExists($payslip->document->file_path);

        $this->actingAs($employeeUser);

        // Employees open the payslip inline (new tab → browser print/Save as PDF).
        $this->get(route('employee.payslips.print', $payslip))
            ->assertOk()
            ->assertSee($payslip->payslip_number, false);

        $this->assertSame(1, $payslip->fresh()->download_count);
        $this->assertSame(1, DocumentAccessLog::query()->where('document_id', $payslip->document_id)->count());
    }
}

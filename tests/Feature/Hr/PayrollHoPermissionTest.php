<?php

namespace Tests\Feature\Hr;

use App\Livewire\Hr\Documents\Index as HrDocumentsIndex;
use App\Livewire\Hr\Payroll\BatchDetail;
use App\Livewire\Hr\Payroll\ItemAdjustment;
use App\Models\Designation;
use App\Models\Document;
use App\Models\DocumentAccessLog;
use App\Models\Employee;
use App\Models\HrScopeAssignment;
use App\Models\OrgUnit;
use App\Models\PayrollBatch;
use App\Services\Hr\HrScopeService;
use App\Models\PayrollItem;
use App\Models\User;
use App\Services\Payroll\PayrollBatchDocumentService;
use App\Services\Payroll\PayrollWorkflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;
use ZipArchive;

class PayrollHoPermissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_plain_hr_without_ho_or_workflow_role_cannot_access_payroll(): void
    {
        [$creator, , $batch, $item] = $this->payrollFixture();

        $this->actingAs($creator);

        // Plain HR (no Head Office scope, no payroll workflow role) is excluded
        // from the payroll module entirely.
        $this->assertFalse(app(HrScopeService::class)->canAccessPayrollModule($creator));

        // The payroll batch page 403s for a plain HR.
        $this->get(route('hr.payroll.batch.detail', $batch))->assertForbidden();
    }

    public function test_ho_hr_can_submit_batch_to_workflow(): void
    {
        [, $ho, $batch] = $this->payrollFixture();

        $this->actingAs($ho);

        Livewire::test(BatchDetail::class, ['batch' => $batch])
            ->call('submitBatch')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('payroll_batches', [
            'id' => $batch->id,
            'status' => 'pending',
        ]);

        $this->assertDatabaseHas('workflow_actions', [
            'action' => 'submitted',
            'acted_by' => $ho->id,
        ]);
    }

    public function test_only_ho_hr_can_generate_and_manage_locked_batch_documents(): void
    {
        Storage::fake('local');

        [$creator, $ho, $batch] = $this->payrollFixture([
            'status' => 'locked',
            'locked_at' => now(),
            'approved_by' => null,
            'approved_at' => now(),
        ]);

        $this->actingAs($creator);

        $this->expectForbidden(function () use ($batch) {
            app(PayrollBatchDocumentService::class)->generate($batch, 'salary_statement');
        });

        $this->actingAs($ho);

        Livewire::test(BatchDetail::class, ['batch' => $batch])
            ->call('downloadFinalPayrollDocument', 'salary_statement')
            ->assertFileDownloaded("salary-statement-{$batch->batch_number}.xlsx");

        $statement = Document::query()
            ->where('documentable_type', $batch->getMorphClass())
            ->where('documentable_id', $batch->id)
            ->where('title', 'Salary Statement')
            ->firstOrFail();

        Storage::disk('local')->assertExists($statement->file_path);
        $this->assertSame(
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            $statement->mime_type,
        );
        $this->assertStringStartsWith('PK', Storage::disk('local')->get($statement->file_path));
        $this->assertSalaryStatementContains($statement, [
            'HO Permission Employee',
            'Executive Engineer',
            '123456789012',
            'SBIN0001234',
            'Earning: Dearness Allowance',
            'Deduction: Provident Fund',
        ]);

        Livewire::test(BatchDetail::class, ['batch' => $batch])
            ->set('batchDocumentTitle', 'Signed Sanction Letter')
            ->set('batchDocumentRemarks', 'Physically signed copy')
            ->set('batchDocumentFile', UploadedFile::fake()->create('signed-sanction.pdf', 100, 'application/pdf'))
            ->call('uploadBatchDocument')
            ->assertHasNoErrors();

        $uploaded = Document::query()
            ->where('documentable_type', $batch->getMorphClass())
            ->where('documentable_id', $batch->id)
            ->where('title', 'Signed Sanction Letter')
            ->firstOrFail();

        Storage::disk('local')->assertExists($uploaded->file_path);

        Livewire::test(HrDocumentsIndex::class)
            ->assertSee('Signed Sanction Letter')
            ->call('downloadDocument', $uploaded->id)
            ->assertFileDownloaded('signed-sanction.pdf');

        $this->assertGreaterThanOrEqual(
            2,
            DocumentAccessLog::query()
                ->whereIn('document_id', [$statement->id, $uploaded->id])
                ->count(),
        );
    }

    /**
     * @param  array<string, mixed>  $batchOverrides
     * @return array{0: User, 1: User, 2: PayrollBatch, 3: PayrollItem}
     */
    private function payrollFixture(array $batchOverrides = []): array
    {
        $orgUnit = OrgUnit::query()->create([
            'name' => 'Head Office',
            'code' => 'HO-TEST-'.uniqid(),
            'type' => 'head_office',
            'status' => 'active',
        ]);
        $designation = Designation::query()->create([
            'name' => 'Executive Engineer',
            'code' => 'EE-TEST-'.uniqid(),
            'status' => 'active',
        ]);

        $creator = User::query()->create([
            'name' => 'Creator HR',
            'email' => 'creator-hr@example.test',
            'password' => 'password',
            'is_hr' => true,
            'status' => 'active',
        ]);

        $ho = User::query()->create([
            'name' => 'HO HR',
            'email' => 'ho-hr@example.test',
            'password' => 'password',
            'is_hr' => true,
            'status' => 'active',
        ]);

        HrScopeAssignment::query()->create([
            'user_id' => $creator->id,
            'org_unit_id' => $orgUnit->id,
            'is_ho' => false,
            'include_child_units' => true,
            'can_view' => true,
            'can_create' => true,
            'can_update' => true,
            'status' => 'active',
        ]);

        HrScopeAssignment::query()->create([
            'user_id' => $ho->id,
            'org_unit_id' => $orgUnit->id,
            'is_ho' => true,
            'include_child_units' => true,
            'can_view' => true,
            'can_create' => true,
            'can_update' => true,
            'can_approve' => true,
            'status' => 'active',
        ]);

        $employee = Employee::query()->create([
            'employee_code' => 'EMP-HO-00001',
            'full_name' => 'HO Permission Employee',
            'org_unit_id' => $orgUnit->id,
            'designation_id' => $designation->id,
            'bank_account_number' => '123456789012',
            'bank_ifsc_code' => 'SBIN0001234',
            'bank_name' => 'State Bank of India',
            'bank_branch' => 'Dispur Branch',
            'service_status' => 'active',
        ]);

        $batch = PayrollBatch::query()->create(array_merge([
            'batch_number' => 'PAY-HO-001',
            'period_from' => '2026-06-01',
            'period_to' => '2026-06-30',
            'payment_date' => '2026-06-30',
            'org_unit_id' => $orgUnit->id,
            'generated_by' => $creator->id,
            'gross_total' => 1000,
            'deduction_total' => 100,
            'net_total' => 900,
            'disbursed_total' => 900,
            'status' => 'draft',
        ], $batchOverrides));

        $item = PayrollItem::query()->create([
            'payroll_batch_id' => $batch->id,
            'employee_id' => $employee->id,
            'basic_salary' => 1000,
            'gross_salary' => 1000,
            'total_deductions' => 100,
            'net_salary' => 900,
            'disbursed_amount' => 900,
            'status' => 'draft',
        ]);

        $item->components()->create([
            'name' => 'Dearness Allowance',
            'type' => 'earning',
            'amount' => 1000,
            'calculation_details' => 'Test earning',
        ]);

        $item->components()->create([
            'name' => 'Provident Fund',
            'type' => 'deduction',
            'amount' => 100,
            'calculation_details' => 'Test deduction',
        ]);

        return [$creator, $ho, $batch, $item];
    }

    /**
     * @param  array<int, string>  $expectedValues
     */
    private function assertSalaryStatementContains(Document $document, array $expectedValues): void
    {
        $zip = new ZipArchive;
        $this->assertTrue($zip->open(Storage::disk('local')->path($document->file_path)));

        $worksheet = $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();

        $this->assertIsString($worksheet);

        foreach ($expectedValues as $expectedValue) {
            $this->assertStringContainsString($expectedValue, $worksheet);
        }
    }

    private function expectForbidden(callable $callback): void
    {
        try {
            $result = $callback();
        } catch (HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());

            return;
        }

        if (is_object($result) && method_exists($result, 'assertForbidden')) {
            $result->assertForbidden();

            return;
        }

        $this->fail('Expected a 403 response.');
    }
}

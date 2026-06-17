<?php

namespace Tests\Feature\Hr;

use App\Livewire\Hr\Documents\Index as HrDocumentsIndex;
use App\Livewire\Hr\Payroll\BatchDetail;
use App\Livewire\Hr\Payroll\ItemAdjustment;
use App\Models\Document;
use App\Models\DocumentAccessLog;
use App\Models\Employee;
use App\Models\HrScopeAssignment;
use App\Models\OrgUnit;
use App\Models\PayrollBatch;
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

class PayrollHoPermissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_creator_hr_can_edit_adjustments_but_cannot_submit_without_ho_scope(): void
    {
        [$creator, , $batch, $item] = $this->payrollFixture();

        $this->actingAs($creator);

        $this->assertTrue(app(PayrollWorkflowService::class)->canHrEditBeforeSubmission($batch, $creator));
        $this->assertFalse(app(PayrollWorkflowService::class)->canSubmitFromHr($batch, $creator));

        Livewire::test(ItemAdjustment::class, ['batch' => $batch, 'item' => $item])
            ->set('type', 'deduction')
            ->set('label', 'Recovery')
            ->set('amount', '500')
            ->set('note', 'Small deduction')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('payroll_item_adjustments', [
            'payroll_item_id' => $item->id,
            'type' => 'deduction',
            'label' => 'Recovery',
            'amount' => 500,
        ]);

        $this->expectForbidden(function () use ($batch) {
            app(PayrollWorkflowService::class)->submitFromHr($batch);
        });
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
            app(PayrollBatchDocumentService::class)->generate($batch, 'sanction_letter');
        });

        $this->actingAs($ho);

        Livewire::test(BatchDetail::class, ['batch' => $batch])
            ->call('downloadFinalPayrollDocument', 'sanction_letter')
            ->assertFileDownloaded("sanction-letter-{$batch->batch_number}.pdf");

        $generated = Document::query()
            ->where('documentable_type', $batch->getMorphClass())
            ->where('documentable_id', $batch->id)
            ->where('title', 'Sanction Letter')
            ->firstOrFail();

        Storage::disk('local')->assertExists($generated->file_path);
        $this->assertStringStartsWith('%PDF-', Storage::disk('local')->get($generated->file_path));

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
                ->whereIn('document_id', [$generated->id, $uploaded->id])
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

        return [$creator, $ho, $batch, $item];
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

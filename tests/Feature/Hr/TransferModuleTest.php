<?php

namespace Tests\Feature\Hr;

use App\Livewire\Hr\Transfers\Index;
use App\Models\Employee;
use App\Models\HrScopeAssignment;
use App\Models\OrgUnit;
use App\Models\PostingHistory;
use App\Models\TransferRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TransferModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_recording_a_transfer_moves_employee_and_writes_posting_history(): void
    {
        $this->actingAs($this->hrUser());

        [$from, $to] = $this->twoOffices();
        $employee = $this->employeeAt($from, 'Movable Staff', 'TRN-0001');

        // A prior open posting that should be closed by the transfer.
        PostingHistory::query()->create([
            'employee_id' => $employee->id,
            'org_unit_id' => $from->id,
            'from_date' => '2026-01-01',
            'status' => 'active',
        ]);

        Livewire::test(Index::class)
            ->call('openModal')
            ->set('form.employee_id', $employee->id)
            ->set('form.to_org_unit_id', $to->id)
            ->set('form.transfer_type', 'administrative')
            ->set('form.effective_date', '2026-07-01')
            ->set('form.reason', 'Administrative need')
            ->call('saveTransfer')
            ->assertHasNoErrors();

        $this->assertSame($to->id, $employee->fresh()->org_unit_id);

        $this->assertDatabaseHas('transfer_requests', [
            'employee_id' => $employee->id,
            'from_org_unit_id' => $from->id,
            'to_org_unit_id' => $to->id,
            'status' => 'completed',
        ]);

        // Old posting closed at the source on the effective date.
        $closed = PostingHistory::query()
            ->where('employee_id', $employee->id)
            ->where('status', 'closed')
            ->first();
        $this->assertNotNull($closed);
        $this->assertSame($from->id, $closed->org_unit_id);
        $this->assertSame('2026-07-01', $closed->to_date->format('Y-m-d'));

        // New posting opened at the destination on the effective date.
        $active = PostingHistory::query()
            ->where('employee_id', $employee->id)
            ->where('status', 'active')
            ->first();
        $this->assertNotNull($active);
        $this->assertSame($to->id, $active->org_unit_id);
        $this->assertSame('2026-07-01', $active->from_date->format('Y-m-d'));
    }

    public function test_transfer_to_the_same_office_is_rejected(): void
    {
        $this->actingAs($this->hrUser());

        [$from] = $this->twoOffices();
        $employee = $this->employeeAt($from, 'Same Office Staff', 'TRN-0002');

        Livewire::test(Index::class)
            ->set('form.employee_id', $employee->id)
            ->set('form.to_org_unit_id', $from->id)
            ->set('form.effective_date', '2026-07-01')
            ->call('saveTransfer')
            ->assertHasErrors('form.to_org_unit_id');

        $this->assertSame($from->id, $employee->fresh()->org_unit_id);
    }

    public function test_scoped_hr_cannot_transfer_an_out_of_scope_employee(): void
    {
        [$scopedOffice, $otherOffice] = $this->twoOffices();

        $hr = $this->hrUser('scoped-hr@example.test');
        HrScopeAssignment::query()->create([
            'user_id' => $hr->id,
            'org_unit_id' => $scopedOffice->id,
            'is_ho' => false,
            'include_child_units' => false,
            'can_view' => true,
            'status' => 'active',
        ]);
        $this->actingAs($hr);

        $outsider = $this->employeeAt($otherOffice, 'Outside Staff', 'TRN-0003');

        Livewire::test(Index::class)
            ->set('form.employee_id', $outsider->id)
            ->set('form.to_org_unit_id', $scopedOffice->id)
            ->set('form.effective_date', '2026-07-01')
            ->call('saveTransfer')
            ->assertHasErrors('form.employee_id');

        $this->assertSame($otherOffice->id, $outsider->fresh()->org_unit_id);
        $this->assertDatabaseCount('transfer_requests', 0);
    }

    private function hrUser(string $email = 'transfer-hr@example.test'): User
    {
        return User::query()->create([
            'name' => 'HR User',
            'email' => $email,
            'password' => 'password',
            'is_hr' => true,
            'status' => 'active',
        ]);
    }

    /** @return array{0: OrgUnit, 1: OrgUnit} */
    private function twoOffices(): array
    {
        return [
            OrgUnit::query()->create(['name' => 'Office A', 'code' => 'OFF-A', 'type' => 'division', 'status' => 'active']),
            OrgUnit::query()->create(['name' => 'Office B', 'code' => 'OFF-B', 'type' => 'division', 'status' => 'active']),
        ];
    }

    private function employeeAt(OrgUnit $orgUnit, string $name, string $code): Employee
    {
        return Employee::query()->create([
            'employee_code' => $code,
            'full_name' => $name,
            'org_unit_id' => $orgUnit->id,
            'service_status' => 'active',
        ]);
    }
}

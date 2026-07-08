<?php

namespace Tests\Feature\Hr;

use App\Livewire\Hr\Reports\Index;
use App\Models\Employee;
use App\Models\HrScopeAssignment;
use App\Models\OrgUnit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ReportsModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_roster_csv_only_contains_in_scope_employees(): void
    {
        $inside = OrgUnit::query()->create(['name' => 'Inside Office', 'code' => 'IN-OFF', 'type' => 'division', 'status' => 'active']);
        $outside = OrgUnit::query()->create(['name' => 'Outside Office', 'code' => 'OUT-OFF', 'type' => 'division', 'status' => 'active']);

        $hr = $this->hrUser();
        HrScopeAssignment::query()->create([
            'user_id' => $hr->id,
            'org_unit_id' => $inside->id,
            'is_ho' => false,
            'include_child_units' => false,
            'can_view' => true,
            'status' => 'active',
        ]);
        $this->actingAs($hr);

        Employee::query()->create(['employee_code' => 'RPT-IN', 'full_name' => 'Inside Person', 'org_unit_id' => $inside->id, 'service_status' => 'active']);
        Employee::query()->create(['employee_code' => 'RPT-OUT', 'full_name' => 'Outside Person', 'org_unit_id' => $outside->id, 'service_status' => 'active']);

        $csv = $this->capture(fn () => (new Index)->downloadRoster());

        $this->assertStringContainsString('Employee Code', $csv);
        $this->assertStringContainsString('Inside Person', $csv);
        $this->assertStringNotContainsString('Outside Person', $csv);
    }

    public function test_transfer_register_includes_a_transfer_recorded_today_with_a_future_effective_date(): void
    {
        $this->actingAs($this->hrUser());

        $from = OrgUnit::query()->create(['name' => 'Old Office', 'code' => 'OLD', 'type' => 'division', 'status' => 'active']);
        $to = OrgUnit::query()->create(['name' => 'New Office', 'code' => 'NEW', 'type' => 'division', 'status' => 'active']);
        $employee = Employee::query()->create(['employee_code' => 'RPT-TR', 'full_name' => 'Transferred Person', 'org_unit_id' => $to->id, 'service_status' => 'active']);

        // Recorded now, but takes effect tomorrow — must still appear in the register.
        \App\Models\TransferRequest::query()->create([
            'employee_id' => $employee->id,
            'from_org_unit_id' => $from->id,
            'to_org_unit_id' => $to->id,
            'transfer_type' => 'administrative',
            'effective_date' => now()->addDay()->toDateString(),
            'status' => 'completed',
        ]);

        $component = new Index;
        $component->mount();
        $csv = $this->capture(fn () => $component->downloadTransfers());

        $this->assertStringContainsString('Transferred Person', $csv);
    }

    public function test_reports_page_downloads_a_csv_file(): void
    {
        $this->actingAs($this->hrUser());

        Livewire::test(Index::class)
            ->call('downloadRoster')
            ->assertFileDownloaded('employee-roster-'.now()->format('Y-m-d').'.csv');
    }

    private function hrUser(string $email = 'reports-hr@example.test'): User
    {
        return User::query()->create([
            'name' => 'HR User',
            'email' => $email,
            'password' => 'password',
            'is_hr' => true,
            'status' => 'active',
        ]);
    }

    /** Run a component action that returns a streamed download and capture its body. */
    private function capture(callable $action): string
    {
        $response = $action();

        ob_start();
        $response->sendContent();

        return ob_get_clean();
    }
}

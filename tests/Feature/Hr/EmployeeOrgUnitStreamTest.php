<?php

namespace Tests\Feature\Hr;

use App\Livewire\Hr\Employees\Create;
use App\Livewire\Hr\Employees\Edit;
use App\Models\DepartmentStream;
use App\Models\Employee;
use App\Models\OrgUnit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class EmployeeOrgUnitStreamTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_create_rejects_stream_not_assigned_to_selected_org_unit(): void
    {
        $this->actingAs($this->hrUser());

        [$orgUnit, $allowedStream, $blockedStream] = $this->mappedOrgUnit();

        Livewire::test(Create::class)
            ->set('form.employee_code', 'EMP-STREAM-INVALID')
            ->set('form.full_name', 'Invalid Stream Employee')
            ->set('form.org_unit_id', (string) $orgUnit->id)
            ->set('form.department_stream_id', (string) $blockedStream->id)
            ->set('form.service_status', 'active')
            ->call('save')
            ->assertHasErrors(['form.department_stream_id']);

        Livewire::test(Create::class)
            ->set('form.employee_code', 'EMP-STREAM-VALID')
            ->set('form.full_name', 'Valid Stream Employee')
            ->set('form.org_unit_id', (string) $orgUnit->id)
            ->set('form.department_stream_id', (string) $allowedStream->id)
            ->set('form.service_status', 'active')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('employees', [
            'employee_code' => 'EMP-STREAM-VALID',
            'org_unit_id' => $orgUnit->id,
            'department_stream_id' => $allowedStream->id,
        ]);
    }

    public function test_office_without_mapped_streams_shows_message_and_blocks_save(): void
    {
        $this->actingAs($this->hrUser());

        $orgUnit = OrgUnit::query()->create([
            'name' => 'Unmapped Division',
            'code' => 'UNMAPPED-DIV',
            'type' => 'division',
            'status' => 'active',
        ]);
        $stream = DepartmentStream::query()->create([
            'name' => 'Some Stream',
            'code' => 'SOME-STREAM',
            'status' => 'active',
        ]);

        Livewire::test(Create::class)
            ->set('form.org_unit_id', (string) $orgUnit->id)
            ->assertSee('No streams set for this unit')
            ->set('form.employee_code', 'EMP-UNMAPPED')
            ->set('form.full_name', 'Unmapped Employee')
            ->set('form.department_stream_id', (string) $stream->id)
            ->set('form.service_status', 'active')
            ->call('save')
            ->assertHasErrors(['form.department_stream_id']);
    }

    public function test_employee_edit_clears_stream_when_org_unit_changes_to_incompatible_mapping(): void
    {
        $this->actingAs($this->hrUser());

        [$firstOrgUnit, $firstStream] = $this->mappedOrgUnit();
        [$secondOrgUnit] = $this->mappedOrgUnit('Second Division', 'SECOND-DIV', 'Second Stream', 'SECOND-STREAM');

        $employee = Employee::query()->create([
            'employee_code' => 'EMP-STREAM-EDIT',
            'full_name' => 'Edit Stream Employee',
            'org_unit_id' => $firstOrgUnit->id,
            'department_stream_id' => $firstStream->id,
            'service_status' => 'active',
        ]);

        Livewire::test(Edit::class, ['employee' => $employee])
            ->assertSet('form.department_stream_id', $firstStream->id)
            ->set('form.org_unit_id', (string) $secondOrgUnit->id)
            ->assertSet('form.department_stream_id', '');
    }

    private function hrUser(): User
    {
        return User::query()->create([
            'name' => 'HR User',
            'email' => 'hr-stream-filter@example.test',
            'password' => 'password',
            'is_hr' => true,
            'status' => 'active',
        ]);
    }

    /**
     * @return array{0: OrgUnit, 1: DepartmentStream, 2: DepartmentStream}
     */
    private function mappedOrgUnit(
        string $orgName = 'Mapped Division',
        string $orgCode = 'MAPPED-DIV',
        string $allowedStreamName = 'Allowed Stream',
        string $allowedStreamCode = 'ALLOWED-STREAM',
    ): array {
        $orgUnit = OrgUnit::query()->create([
            'name' => $orgName,
            'code' => $orgCode,
            'type' => 'division',
            'status' => 'active',
        ]);

        $allowedStream = DepartmentStream::query()->create([
            'name' => $allowedStreamName,
            'code' => $allowedStreamCode,
            'status' => 'active',
        ]);

        $blockedStream = DepartmentStream::query()->create([
            'name' => $allowedStreamName.' Blocked',
            'code' => $allowedStreamCode.'-BLOCKED',
            'status' => 'active',
        ]);

        $orgUnit->departmentStreams()->sync([$allowedStream->id]);

        return [$orgUnit, $allowedStream, $blockedStream];
    }
}

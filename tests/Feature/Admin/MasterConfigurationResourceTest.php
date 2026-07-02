<?php

namespace Tests\Feature\Admin;

use App\Models\DepartmentStream;
use App\Models\OrgUnit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MasterConfigurationResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_open_master_configuration_resources(): void
    {
        $admin = User::query()->create([
            'name' => 'Super Admin',
            'email' => 'admin@example.test',
            'password' => 'password',
            'is_admin' => true,
            'is_hr' => false,
            'status' => 'active',
        ]);

        $this->actingAs($admin);

        foreach ($this->masterResourcePaths() as $path) {
            $this->get($path)->assertOk();
        }
    }

    public function test_org_unit_can_be_assigned_department_streams(): void
    {
        $orgUnit = OrgUnit::query()->create([
            'name' => 'Head Office',
            'code' => 'HO',
            'type' => 'head_office',
            'status' => 'active',
        ]);

        $phed = DepartmentStream::query()->create([
            'name' => 'PHED',
            'code' => 'PHED',
            'status' => 'active',
        ]);

        $jjm = DepartmentStream::query()->create([
            'name' => 'JJM HQ',
            'code' => 'JJMHQ',
            'status' => 'active',
        ]);

        $orgUnit->departmentStreams()->sync([$phed->id, $jjm->id]);

        $this->assertDatabaseHas('department_stream_org_unit', [
            'org_unit_id' => $orgUnit->id,
            'department_stream_id' => $phed->id,
        ]);

        $this->assertDatabaseHas('department_stream_org_unit', [
            'org_unit_id' => $orgUnit->id,
            'department_stream_id' => $jjm->id,
        ]);

        $this->assertSame(
            ['JJM HQ', 'PHED'],
            $orgUnit->fresh()->departmentStreams()->orderBy('name')->pluck('name')->all(),
        );

        $this->assertSame(
            ['Head Office'],
            $phed->fresh()->orgUnits()->pluck('name')->all(),
        );
    }

    private function masterResourcePaths(): array
    {
        return [
            '/admin/org-units',
            '/admin/org-units/create',
            '/admin/hr-scope-assignments',
            '/admin/hr-scope-assignments/create',
            '/admin/pay-matrices',
            '/admin/pay-matrices/create',
            '/admin/pay-levels',
            '/admin/pay-levels/create',
            '/admin/salary-components',
            '/admin/salary-components/create',
            '/admin/leave-types',
            '/admin/leave-types/create',
            '/admin/leave-policies',
            '/admin/leave-policies/create',
            '/admin/holidays',
            '/admin/holidays/create',
            '/admin/document-types',
            '/admin/document-types/create',
            '/admin/workflow-definitions',
            '/admin/workflow-definitions/create',
            '/admin/workflow-steps',
            '/admin/workflow-steps/create',
            '/admin/grievance-categories',
            '/admin/grievance-categories/create',
            '/admin/notification-templates',
            '/admin/notification-templates/create',
            '/admin/integration-settings',
            '/admin/integration-settings/create',
        ];
    }
}

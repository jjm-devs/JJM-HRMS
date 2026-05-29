<?php

namespace Tests\Feature\Admin;

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

    private function masterResourcePaths(): array
    {
        return [
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

<?php

namespace Database\Seeders;

use App\Models\Cadre;
use App\Models\DepartmentStream;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\EmploymentType;
use App\Models\HrScopeAssignment;
use App\Models\OrgUnit;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => 'admin@jjmbrain.local'],
            [
                'name' => 'Super Admin',
                'password' => 'password',
                'is_admin' => true,
                'is_hr' => false,
                'status' => 'active',
                'must_change_password' => false,
                'email_verified_at' => now(),
            ],
        );

        $hrUser = User::query()->updateOrCreate(
            ['email' => 'hr@jjmbrain.local'],
            [
                'name' => 'HR User',
                'password' => 'password',
                'is_admin' => false,
                'is_hr' => true,
                'status' => 'active',
                'must_change_password' => false,
                'email_verified_at' => now(),
            ],
        );

        $employeeUser = User::query()->updateOrCreate(
            ['email' => 'employee@jjmbrain.local'],
            [
                'name' => 'Employee User',
                'password' => 'password',
                'is_admin' => false,
                'is_hr' => false,
                'status' => 'active',
                'must_change_password' => false,
                'email_verified_at' => now(),
            ],
        );

        $phed = DepartmentStream::query()->updateOrCreate(
            ['code' => 'PHED'],
            ['name' => 'PHED', 'status' => 'active'],
        );

        DepartmentStream::query()->updateOrCreate(
            ['code' => 'JJM'],
            ['name' => 'JJM', 'status' => 'active'],
        );

        EmploymentType::query()->updateOrCreate(
            ['code' => 'REGULAR'],
            ['name' => 'Regular', 'status' => 'active'],
        );

        EmploymentType::query()->updateOrCreate(
            ['code' => 'CONTRACTUAL'],
            ['name' => 'Contractual', 'status' => 'active'],
        );

        $this->call(OrgUnitSeeder::class);

        $engineeringCadre = Cadre::query()->updateOrCreate(
            ['code' => 'ENGINEERING'],
            ['name' => 'Engineering', 'status' => 'active'],
        );

        Cadre::query()->updateOrCreate(
            ['code' => 'ADMINISTRATION'],
            ['name' => 'Administration', 'status' => 'active'],
        );

        $juniorEngineer = Designation::query()->updateOrCreate(
            ['code' => 'JE'],
            [
                'cadre_id' => $engineeringCadre->id,
                'department_stream_id' => $phed->id,
                'name' => 'Junior Engineer',
                'level' => 'Field',
                'status' => 'active',
            ],
        );

        Designation::query()->updateOrCreate(
            ['code' => 'AE'],
            [
                'cadre_id' => $engineeringCadre->id,
                'department_stream_id' => $phed->id,
                'name' => 'Assistant Engineer',
                'level' => 'Field',
                'status' => 'active',
            ],
        );

        $sampleOrgUnit = OrgUnit::query()
            ->where('type', 'sub_division')
            ->orderBy('name')
            ->first();

        $headOffice = OrgUnit::query()
            ->where('type', 'head_office')
            ->orderBy('name')
            ->first();

        if ($headOffice) {
            HrScopeAssignment::query()->updateOrCreate(
                [
                    'user_id' => $hrUser->id,
                    'org_unit_id' => $headOffice->id,
                ],
                [
                    'is_ho' => true,
                    'include_child_units' => true,
                    'can_view' => true,
                    'can_create' => true,
                    'can_update' => true,
                    'can_delete' => true,
                    'can_approve' => true,
                    'status' => 'active',
                ],
            );
        }

        Employee::query()->updateOrCreate(
            ['employee_code' => 'EMP-2026-00001'],
            [
                'user_id' => $employeeUser->id,
                'full_name' => 'Sample Employee',
                'org_unit_id' => $sampleOrgUnit?->id,
                'department_stream_id' => $phed->id,
                'employment_type_id' => EmploymentType::query()->where('code', 'REGULAR')->value('id'),
                'cadre_id' => $engineeringCadre->id,
                'designation_id' => $juniorEngineer->id,
                'joining_date' => now()->toDateString(),
                'service_status' => 'active',
            ],
        );

        $this->call(MasterConfigurationSeeder::class);
    }
}

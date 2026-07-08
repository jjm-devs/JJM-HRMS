<?php

namespace Database\Seeders;

use App\Models\Cadre;
use App\Models\DepartmentStream;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\EmploymentType;
use App\Models\HrScopeAssignment;
use App\Models\OrgUnit;
use App\Models\Role;
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
            ['email' => 'admin@jjmbrain.in'],
            [
                'name' => 'Super Admin',
                'password' => 'jjm@123',
                'is_admin' => true,
                'is_hr' => false,
                'status' => 'active',
                'must_change_password' => false,
                'email_verified_at' => now(),
            ],
        );

        $hrUser = User::query()->updateOrCreate(
            ['email' => 'hr.kangkana@jjmbrain.in'],
            [
                'name' => 'HR Kangkana',
                'password' => 'jjm@123',
                'is_admin' => false,
                'is_hr' => true,
                'status' => 'active',
                'must_change_password' => false,
                'email_verified_at' => now(),
            ],
        );

        $workflowHrUsers = [
            ['spo-fm@jjmbrain.in', 'SPO FM User', 'spo_fm'],
            ['deputy-md@jjmbrain.in', 'Deputy MD User', 'deputy_md'],
            ['fa@jjmbrain.in', 'FA User', 'fa'],
            ['addt-chief-eng@jjmbrain.in', 'Addt Chief Eng User', 'addt_chief_eng'],
            ['addt-md@jjmbrain.in', 'Addt. MD User', 'addt_md'],
            ['md@jjmbrain.in', 'MD User', 'md'],
        ];

        $workflowRoleUsers = collect($workflowHrUsers)
            ->mapWithKeys(function (array $workflowUser): array {
                [$email, $name, $roleCode] = $workflowUser;

                $user = User::query()->updateOrCreate(
                    ['email' => $email],
                    [
                        'name' => $name,
                        'password' => 'jjm@123',
                        'is_admin' => false,
                        'is_hr' => true,
                        'status' => 'active',
                        'must_change_password' => false,
                        'email_verified_at' => now(),
                    ],
                );

                return [$roleCode => $user];
            });

        $employeeUser = User::query()->updateOrCreate(
            ['email' => 'employee@jjmbrain.in'],
            [
                'name' => 'Employee User',
                'password' => 'jjm@123',
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
            ['code' => 'JJMHQ'],
            ['name' => 'JJM HQ', 'status' => 'active'],
        );
        DepartmentStream::query()->updateOrCreate(
            ['code' => 'JJMSRL'],
            ['name' => 'JJM SRL', 'status' => 'active'],
        );
        DepartmentStream::query()->updateOrCreate(
            ['code' => 'JJMKRC'],
            ['name' => 'JJM KRC', 'status' => 'active'],
        );
        DepartmentStream::query()->updateOrCreate(
            ['code' => 'JJMUNICEF'],
            ['name' => 'JJM UNICEF', 'status' => 'active'],
        );
        DepartmentStream::query()->updateOrCreate(
            ['code' => 'DMMU'],
            ['name' => 'DMMU', 'status' => 'active'],
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

        $headOffice = OrgUnit::query()
            ->where('name', 'Office Of the Chief Engineer(Water)')
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

        $rolesByCode = Role::query()
            ->whereIn('code', Role::PAYROLL_ROLE_CODES)
            ->pluck('id', 'code');

        if ($rolesByCode->has('hr')) {
            $hrUser->roles()->sync([$rolesByCode->get('hr')]);
        }

        $workflowRoleUsers->each(function (User $user, string $roleCode) use ($rolesByCode): void {
            $roleId = $rolesByCode->get($roleCode);

            $user->roles()->sync($roleId ? [$roleId] : []);
        });

        HrScopeAssignment::query()
            ->whereIn('user_id', $workflowRoleUsers->pluck('id'))
            ->delete();

        Employee::query()->updateOrCreate(
            ['employee_code' => 'EMP-2026-00001'],
            [
                'user_id' => $employeeUser->id,
                'full_name' => 'Sample Employee',
                'org_unit_id' => $headOffice?->id,
                'department_stream_id' => $phed->id,
                'employment_type_id' => EmploymentType::query()->where('code', 'REGULAR')->value('id'),
                'cadre_id' => $engineeringCadre->id,
                'designation_id' => $juniorEngineer->id,
                'joining_date' => now()->toDateString(),
                'service_status' => 'active',
            ],
        );

        $this->call(MasterConfigurationSeeder::class);
        $this->call(SanctionActivitySeeder::class);
        $this->call(StaffCategorySeeder::class);
    }
}

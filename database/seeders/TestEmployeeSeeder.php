<?php

namespace Database\Seeders;

use App\Models\Cadre;
use App\Models\DepartmentStream;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\EmployeeSalaryComponent;
use App\Models\EmploymentType;
use App\Models\OrgUnit;
use App\Models\SalaryComponent;
use App\Models\SalaryStructure;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TestEmployeeSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // ── Employees ─────────────────────────────────────────────────────────────
        $employees = [
            ['employee1@jjmbrain.local',  'Ram Kumar',       'EMP-2026-00003'],
            ['employee2@jjmbrain.local',  'Priya Sharma',    'EMP-2026-00004'],
            ['employee3@jjmbrain.local',  'Bikash Nath',     'EMP-2026-00005'],
            ['employee4@jjmbrain.local',  'Sunita Devi',     'EMP-2026-00006'],
            ['employee5@jjmbrain.local',  'Rajib Bora',      'EMP-2026-00007'],
            ['employee6@jjmbrain.local',  'Mriganka Kalita', 'EMP-2026-00008'],
            ['employee7@jjmbrain.local',  'Deepa Gogoi',     'EMP-2026-00009'],
            ['employee8@jjmbrain.local',  'Hemen Baruah',    'EMP-2026-00010'],
            ['employee9@jjmbrain.local',  'Anita Das',       'EMP-2026-00011'],
            ['employee10@jjmbrain.local', 'Prodip Hazarika', 'EMP-2026-00012'],
            ['employee11@jjmbrain.local', 'Ranjit Saikia',   'EMP-2026-00013'],
            ['employee12@jjmbrain.local', 'Munmi Chetia',    'EMP-2026-00014'],
            ['employee13@jjmbrain.local', 'Jitu Phukan',     'EMP-2026-00015'],
            ['employee14@jjmbrain.local', 'Papori Borah',    'EMP-2026-00016'],
            ['employee15@jjmbrain.local', 'Kabita Nath',     'EMP-2026-00017'],
            ['employee16@jjmbrain.local', 'Nabanita Deka',   'EMP-2026-00018'],
        ];

        $chiefEngineerOffice = OrgUnit::query()
            ->where('name', 'Office Of the Chief Engineer(Water)')
            ->firstOrFail();
        $missionDirectorOffice = OrgUnit::query()
            ->where('name', 'Office of the Mission Director')
            ->firstOrFail();

        $phedStream = DepartmentStream::query()
            ->where('code', 'PHED')
            ->firstOrFail();
        $missionStreams = DepartmentStream::query()
            ->where('code', '!=', 'PHED')
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        $employmentTypeId = EmploymentType::query()->where('code', 'REGULAR')->value('id');
        $cadreId = Cadre::query()->where('code', 'ENGINEERING')->value('id') ?? Cadre::query()->value('id');
        $designationId = Designation::query()->where('code', 'JE')->value('id') ?? Designation::query()->value('id');
        $basicSalaryComponentId = SalaryComponent::query()->where('code', 'BASIC')->value('id');

        foreach ($employees as $index => [$email, $name, $code]) {
            $user = User::query()->updateOrCreate(
                ['email' => $email],
                [
                    'email' => $email,
                    'name' => $name,
                    'password' => 'password',
                    'is_admin' => false,
                    'is_hr' => false,
                    'status' => 'active',
                    'must_change_password' => false,
                    'email_verified_at' => now(),
                ],
            );

            $isChiefEngineerEmployee = $index < count($employees) / 2;
            $orgUnit = $isChiefEngineerEmployee ? $chiefEngineerOffice : $missionDirectorOffice;
            $stream = $isChiefEngineerEmployee
                ? $phedStream
                : $missionStreams->values()->get($index % max(1, $missionStreams->count()));

            $employee = Employee::query()->updateOrCreate(
                ['employee_code' => $code],
                [
                    'user_id' => $user->id,
                    'full_name' => $name,
                    'org_unit_id' => $orgUnit->id,
                    'department_stream_id' => $stream?->id,
                    'employment_type_id' => $employmentTypeId,
                    'cadre_id' => $cadreId,
                    'designation_id' => $designationId,
                    'joining_date' => now()->toDateString(),
                    'service_status' => 'active',
                ],
            );

            $salaryStructure = SalaryStructure::query()->updateOrCreate([
                'employee_id' => $employee->id,
                'status' => 'active',
            ], [
                'pay_level_id' => 1,
                'basic_salary' => 50000.00,
                'grade_pay' => 0.00,
                'effective_from' => now()->toDateString(),
                'effective_to' => null,
            ]);

            EmployeeSalaryComponent::query()->updateOrCreate([
                'salary_structure_id' => $salaryStructure->id,
                'salary_component_id' => $basicSalaryComponentId,
            ], [
                'amount' => 50000.00,
                'percentage_rate' => null,
                'calculation_type' => 'fixed',
                'calculation_base' => null,
                'formula' => null,
                'status' => 'active',
            ]);
        }
    }
}

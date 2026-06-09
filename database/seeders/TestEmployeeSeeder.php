<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\EmployeeSalaryComponent;
use App\Models\SalaryStructure;

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
        ];

        foreach ($employees as [$email, $name, $code]) {
            $user = User::create([
                'email'                => $email,
                'name'                 => $name,
                'password'             => 'password',
                'is_admin'             => false,
                'is_hr'                => false,
                'status'               => 'active',
                'must_change_password' => false,
                'email_verified_at'    => now(),
            ]);

            $employee=Employee::create([
                'user_id'              => $user->id,
                'employee_code'        => $code,
                'full_name'            => $name,
                'org_unit_id'          => 2,
                'department_stream_id' => 1,
                'employment_type_id'   => 1,
                'cadre_id'             => 1,
                'designation_id'       => 1,
                'joining_date'         => now()->toDateString(),
                'service_status'       => 'active',
            ]);
            $salaryStructure = SalaryStructure::create([
                'employee_id'   => $employee->id,
                'pay_level_id'  => 1,
                'basic_salary'  => 50000.00,
                'grade_pay'     => 0.00,
                'effective_from' => now()->toDateString(),
                'effective_to'   => null,
                'status'        => 'active',
            ]);
            EmployeeSalaryComponent::create([
                'salary_structure_id' => $salaryStructure->id,
                'salary_component_id' => 6, // Basic Salary
                'amount'              => 50000.00,
                'percentage_rate'     => null,
                'calculation_type'    => 'fixed',
                'calculation_base'    => null,
                'formula'            => null,
                'status'             => 'active',
            ]);
        }
    }
}
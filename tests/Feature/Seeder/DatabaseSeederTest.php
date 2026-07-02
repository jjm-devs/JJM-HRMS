<?php

namespace Tests\Feature\Seeder;

use App\Models\DepartmentStream;
use App\Models\Employee;
use App\Models\HrScopeAssignment;
use App\Models\OrgUnit;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\TestEmployeeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeders_create_valid_hr_roles_org_streams_and_test_employees(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->seed(TestEmployeeSeeder::class);

        $scopedHr = User::query()->where('email', 'hr@jjmbrain.local')->firstOrFail();

        $this->assertTrue($scopedHr->is_hr);
        $this->assertSame(['hr'], $scopedHr->roles()->pluck('code')->all());
        $this->assertSame(1, HrScopeAssignment::query()->where('user_id', $scopedHr->id)->count());

        foreach ([
            'spo-fm@jjmbrain.local' => 'spo_fm',
            'deputy-md@jjmbrain.local' => 'deputy_md',
            'fa@jjmbrain.local' => 'fa',
            'addt-chief-eng@jjmbrain.local' => 'addt_chief_eng',
            'addt-md@jjmbrain.local' => 'addt_md',
            'md@jjmbrain.local' => 'md',
        ] as $email => $roleCode) {
            $user = User::query()->where('email', $email)->firstOrFail();

            $this->assertTrue($user->is_hr);
            $this->assertSame([$roleCode], $user->roles()->pluck('code')->all());
            $this->assertSame(0, HrScopeAssignment::query()->where('user_id', $user->id)->count());
        }

        $phedStream = DepartmentStream::query()->where('code', 'PHED')->firstOrFail();
        $nonPhedStreamIds = DepartmentStream::query()
            ->where('code', '!=', 'PHED')
            ->orderBy('id')
            ->pluck('id')
            ->all();

        $chiefEngineerOffice = OrgUnit::query()
            ->where('name', 'Office Of the Chief Engineer(Water)')
            ->firstOrFail();
        $missionDirectorOffice = OrgUnit::query()
            ->where('name', 'Office of the Mission Director')
            ->firstOrFail();

        $this->assertTrue($missionDirectorOffice->parent->is($chiefEngineerOffice));
        $this->assertSame([$phedStream->id], $chiefEngineerOffice->departmentStreams()->pluck('department_streams.id')->all());
        $this->assertSame($nonPhedStreamIds, $missionDirectorOffice->departmentStreams()->orderBy('department_streams.id')->pluck('department_streams.id')->all());

        OrgUnit::query()
            ->whereKeyNot($chiefEngineerOffice->id)
            ->each(function (OrgUnit $orgUnit) use ($phedStream): void {
                $this->assertFalse(
                    $orgUnit->departmentStreams()->whereKey($phedStream->id)->exists(),
                    "{$orgUnit->name} should not be mapped to PHED.",
                );
            });

        $testEmployeeCodes = collect(range(3, 18))
            ->map(fn (int $number): string => 'EMP-2026-'.str_pad((string) $number, 5, '0', STR_PAD_LEFT))
            ->all();

        $this->assertSame(8, Employee::query()
            ->whereIn('employee_code', $testEmployeeCodes)
            ->where('org_unit_id', $chiefEngineerOffice->id)
            ->count());
        $this->assertSame(8, Employee::query()
            ->whereIn('employee_code', $testEmployeeCodes)
            ->where('org_unit_id', $missionDirectorOffice->id)
            ->count());
        $this->assertSame(8, Employee::query()
            ->whereIn('employee_code', $testEmployeeCodes)
            ->where('org_unit_id', $chiefEngineerOffice->id)
            ->where('department_stream_id', $phedStream->id)
            ->count());

        $missionStreamCounts = Employee::query()
            ->whereIn('employee_code', $testEmployeeCodes)
            ->where('org_unit_id', $missionDirectorOffice->id)
            ->selectRaw('department_stream_id, count(*) as aggregate')
            ->groupBy('department_stream_id')
            ->orderBy('department_stream_id')
            ->pluck('aggregate', 'department_stream_id');

        $this->assertEqualsCanonicalizing($nonPhedStreamIds, $missionStreamCounts->keys()->all());
        $missionStreamCounts->each(fn (int $count) => $this->assertSame(2, $count));
    }
}

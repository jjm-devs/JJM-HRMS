<?php

namespace Tests\Feature\Seeder;

use App\Models\Employee;
use App\Models\User;
use Database\Seeders\EmployeeDetailsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;

class EmployeeDetailsSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_fills_joining_date_mobile_and_login_email(): void
    {
        $row = $this->rows()->first(fn ($r) => $r['doj'] && $r['phone'] && $r['email']);
        $this->assertNotNull($row, 'Expected at least one fully-populated row in employee_details.json');

        $user = User::query()->create([
            'name' => $row['name'], 'email' => 'placeholder@jjmbrain.in', 'password' => 'jjm@123', 'status' => 'active',
        ]);
        $employee = Employee::query()->create([
            'employee_code' => $row['employee_code'],
            'full_name' => $row['name'],
            'user_id' => $user->id,
            'service_status' => 'active',
        ]);

        $this->seed(EmployeeDetailsSeeder::class);
        $employee->refresh();

        $this->assertSame($row['doj'], $employee->joining_date->format('Y-m-d'));
        // phone → primary mobile contact
        $this->assertDatabaseHas('employee_contacts', [
            'employee_id' => $employee->id, 'type' => 'mobile', 'value' => $row['phone'], 'is_primary' => true,
        ]);
        // email → the login email on the linked user (NOT a contact)
        $this->assertSame($row['email'], $user->refresh()->email);
        $this->assertDatabaseMissing('employee_contacts', [
            'employee_id' => $employee->id, 'type' => 'email',
        ]);
    }

    public function test_login_email_is_skipped_when_already_used_by_another_account(): void
    {
        $row = $this->rows()->first(fn ($r) => $r['email']);

        // Another account already owns this email.
        User::query()->create(['name' => 'Other', 'email' => $row['email'], 'password' => 'x', 'status' => 'active']);

        $user = User::query()->create([
            'name' => $row['name'], 'email' => 'keep-me@jjmbrain.in', 'password' => 'jjm@123', 'status' => 'active',
        ]);
        Employee::query()->create([
            'employee_code' => $row['employee_code'], 'full_name' => $row['name'],
            'user_id' => $user->id, 'service_status' => 'active',
        ]);

        $this->seed(EmployeeDetailsSeeder::class); // must not throw on the unique clash

        $this->assertSame('keep-me@jjmbrain.in', $user->refresh()->email);
    }

    public function test_seeder_is_idempotent_and_keeps_one_mobile_contact(): void
    {
        $row = $this->rows()->first(fn ($r) => $r['phone']);
        $employee = Employee::query()->create([
            'employee_code' => $row['employee_code'],
            'full_name' => $row['name'],
            'service_status' => 'active',
        ]);

        $this->seed(EmployeeDetailsSeeder::class);
        $this->seed(EmployeeDetailsSeeder::class);

        $this->assertSame(1, $employee->contacts()->where('type', 'mobile')->count());
    }

    public function test_blank_values_do_not_overwrite_existing_joining_date(): void
    {
        $row = $this->rows()->first(fn ($r) => empty($r['doj']));
        if ($row === null) {
            $this->markTestSkipped('No row with a blank joining date to test against.');
        }

        $employee = Employee::query()->create([
            'employee_code' => $row['employee_code'],
            'full_name' => $row['name'],
            'joining_date' => '2020-01-01',
            'service_status' => 'active',
        ]);

        $this->seed(EmployeeDetailsSeeder::class);

        $this->assertSame('2020-01-01', $employee->refresh()->joining_date->format('Y-m-d'));
    }

    private function rows(): Collection
    {
        return collect(json_decode(file_get_contents(
            database_path('seeders/data/employee_details.json')
        ), true));
    }
}

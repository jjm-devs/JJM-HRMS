<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\EmployeeContact;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Fills in the employee contact details HR collected separately (phone, email,
 * date of joining) sourced from "jjm Employee Details Required.xlsx", cleaned
 * and matched to the roster by employee_code. Data lives in
 * database/seeders/data/employee_details.json.
 *
 * Run AFTER the roster is imported (employees must already exist):
 *   php artisan db:seed --class=Database\\Seeders\\EmployeeDetailsSeeder
 *
 * Idempotent: joining_date is updated in place; phone becomes a primary mobile
 * contact (and the account's mobile); email updates the linked user's LOGIN
 * email (users.email). Blank values never overwrite existing data.
 */
class EmployeeDetailsSeeder extends Seeder
{
    public function run(): void
    {
        $file = database_path('seeders/data/employee_details.json');
        abort_unless(is_file($file), 500, "Missing data file: {$file}");

        $rows = json_decode(file_get_contents($file), true);
        $this->command?->info(count($rows).' detail rows loaded.');

        $updated = 0;
        $missing = 0;
        $dojSet = 0;
        $phoneSet = 0;
        $emailSet = 0;
        $emailConflict = 0;

        foreach ($rows as $row) {
            $employee = Employee::query()
                ->where('employee_code', $row['employee_code'])
                ->first();

            if ($employee === null) {
                $missing++;

                continue;
            }

            // ── date of joining (only when provided) ─────────────────────────
            if (! empty($row['doj'])) {
                $employee->update(['joining_date' => $row['doj']]);
                $dojSet++;
            }

            // ── phone → primary mobile contact (+ account mobile) ────────────
            if (! empty($row['phone'])) {
                EmployeeContact::query()->updateOrCreate(
                    ['employee_id' => $employee->id, 'type' => 'mobile'],
                    ['label' => 'Primary', 'value' => $row['phone'], 'is_primary' => true],
                );

                if ($employee->user_id !== null) {
                    $employee->user()->update(['mobile' => $row['phone']]);
                }

                $phoneSet++;
            }

            // ── email → login email on the linked user account ───────────────
            // Skipped when the address is already used by another account (the
            // users.email column is unique) so the seeder never fails on a clash.
            if (! empty($row['email']) && $employee->user_id !== null) {
                $conflict = User::query()
                    ->where('email', $row['email'])
                    ->where('id', '!=', $employee->user_id)
                    ->exists();

                if ($conflict) {
                    $emailConflict++;
                } else {
                    User::query()->whereKey($employee->user_id)->update(['email' => $row['email']]);
                    $emailSet++;
                }
            }

            $updated++;
        }

        $this->command?->info("Employees updated: {$updated} (not found: {$missing}).");
        $this->command?->info("joining_date set: {$dojSet} | mobile: {$phoneSet} | login email: {$emailSet} (conflicts skipped: {$emailConflict}).");
    }
}

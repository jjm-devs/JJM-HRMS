<?php

namespace Database\Seeders;

use App\Models\DepartmentStream;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\EmploymentType;
use App\Models\OrgUnit;
use App\Models\SalaryComponent;
use App\Models\StaffCategory;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * One-off import of the real employee roster (sourced from the HR salary
 * statement): employees, their office/stream/category, designation and salary
 * details. Data lives in database/seeders/data/june_2026_payroll.json.
 *
 * Not wired into DatabaseSeeder — run explicitly when ready:
 *   php artisan db:seed --class=Database\\Seeders\\EmployeeRosterImportSeeder
 *
 * Idempotent: employees are matched by their stable JJMA-2026-##### code.
 */
class EmployeeRosterImportSeeder extends Seeder
{
    /**
     * Statement division label => org unit name in the tree.
     *
     * @var array<string, string>
     */
    private array $divisionMap = [
        'Bajali' => 'Bajali Division',
        'Baksha' => 'Baska Division',
        'Barpeta' => 'Barpeta Division',
        'Belsor' => 'Belsor Division',
        'Biswanath Chariali' => 'Biswanath Chariali Divn',
        'Bokakhat' => 'Bokakhat Division',
        'Bongaigaon' => 'Bongaigaon Division',
        'Dhemaji' => 'Dhemaji Division',
        'Dhing' => 'Dhing Division',
        'Dhubri' => 'Dhubri Division',
        'Dibrugarh' => 'Dibrugarh Division',
        'Diphu Rural' => 'Diphu(Rural) Division',
        'Ghilamara' => 'Ghilamara Division',
        'Goalpara' => 'Goalpara Division',
        'Golaghat' => 'Golaghat Division',
        'Gossaigaon' => 'Gossaigaon Division',
        'Guwahati I' => 'Guwahati Division No-I',
        'Haflong' => 'Haflong Division',
        'Hailakandi' => 'Hailakandi Division',
        'Hamren' => 'Hamren Division',
        'Hojai' => 'Hojai Division',
        'Howraghat' => 'Howraghat Division',
        'Jorhat' => 'Jorhat Division',
        'Kaliabor' => 'Kaliabor Division',
        'Karimganj' => 'Karimganj Division',
        'Kokrajhar I' => 'Kokrajhar Division No-I',
        'Kokrajhar II' => 'Kokrajhar Division No-II',
        'Maibang' => 'Maibang Division',
        'Majuli' => 'Majuli Division',
        'Mangaldai' => 'Mangaldoi Division',
        'Morigaon' => 'Morigaon Division',
        'Nagaon' => 'Nagaon Division',
        'Nalbari' => 'Nalbari Division',
        'Nazira' => 'Nazira Division',
        'North Lakhimpur' => 'Lakhimpur Division',
        'Rangia' => 'Rangia Division',
        'Silchar I' => 'Silchar Division No-I',
        'Silchar II' => 'Silchar Division No-II',
        'Sivasagar' => 'Sivsagar Division',
        'South Salmara Mancachar' => 'South Salmara Mankachar',
        'Tangla' => 'Tangla Division',
        'Tezpur I' => 'Tezpur Division No-I',
        'Tezpur II' => 'Tezpur Divn No-II',
        'Tinsukia' => 'Tinsukia Division',
        'Ulukunchi' => 'Ulukunchi',
        'Umrangso' => 'Umrangsoo Division',
    ];

    public function run(): void
    {
        $file = database_path('seeders/data/june_2026_payroll.json');
        abort_unless(is_file($file), 500, "Missing data file: {$file}");

        $rows = json_decode(file_get_contents($file), true);
        $this->command?->info(count($rows).' rows loaded.');

        // ── masters (create if missing) ──────────────────────────────────────
        $streamByKey = $this->resolveStreams();
        $categoryByCode = $this->resolveCategories();
        $components = $this->resolveComponents();
        $contractualId = EmploymentType::query()->where('code', 'CONTRACTUAL')->value('id');

        $directorate = OrgUnit::query()->where('name', 'Office of the Mission Director')->value('id');
        $orgIdByName = OrgUnit::query()->pluck('id', 'name');
        $designationCache = [];
        $missingDivisions = [];
        $imported = 0;

        // Drop logins from the previous (@jjmassam.local) email scheme so the
        // switch to name@jjmbrain.in doesn't leave orphaned duplicate users.
        // That domain is unique to this seeder, so it's safe to purge.
        User::query()->where('email', 'like', '%@jjmassam.local')->delete();
        $emailSeen = [];

        foreach ($rows as $row) {
            // ── resolve office ────────────────────────────────────────────────
            if ($row['stream'] === 'DMMU') {
                $orgName = $this->divisionMap[$row['division']] ?? null;
                $orgUnitId = $orgName ? ($orgIdByName[$orgName] ?? null) : null;
                if (! $orgUnitId) {
                    $missingDivisions[$row['division']] = true;
                }
            } else {
                $orgUnitId = $directorate;
            }

            // ── resolve designation (create on first sight) ──────────────────
            $designationId = null;
            if (! empty($row['designation'])) {
                $name = $row['designation'];
                $designationId = $designationCache[$name] ??= Designation::query()->firstOrCreate(
                    ['code' => $this->designationCode($name)],
                    ['name' => $name, 'status' => 'active'],
                )->id;
            }

            // ── employee ─────────────────────────────────────────────────────
            $employee = Employee::query()->updateOrCreate(
                ['employee_code' => $row['employee_code']],
                [
                    'full_name' => $row['name'],
                    'org_unit_id' => $orgUnitId,
                    'department_stream_id' => $streamByKey[$row['stream']] ?? null,
                    'staff_category_id' => $row['category'] ? ($categoryByCode[$row['category']] ?? null) : null,
                    'designation_id' => $designationId,
                    'employment_type_id' => $contractualId,
                    'bank_account_number' => $this->clip($row['account'] ?? '', 40),
                    'bank_ifsc_code' => $this->clipIfsc($row['ifsc'] ?? ''),
                    'bank_name' => $this->clip($row['bank'] ?? '', 150),
                    'bank_branch' => $this->clip($row['branch'] ?? '', 150),
                    'service_status' => 'active',
                    'remarks' => $row['remarks'] ?: null,
                ],
            );

            // ── login account ────────────────────────────────────────────────
            // Name-based email (name@jjmbrain.in); duplicate names get name1,
            // name2, … Deterministic because rows are processed in JSON order.
            // firstOrCreate so a re-run never resets a password the user changed.
            $email = $this->uniqueEmail($row['name'], $row['employee_code'], $emailSeen);
            $user = User::query()->firstOrCreate(
                ['email' => $email],
                [
                    'name' => $row['name'],
                    'password' => 'jjm@123',
                    'is_admin' => false,
                    'is_hr' => false,
                    'status' => 'active',
                    'must_change_password' => false,
                ],
            );
            if ($employee->user_id !== $user->id) {
                $employee->update(['user_id' => $user->id]);
            }

            // ── salary structure + components ────────────────────────────────
            $structure = $employee->salaryStructures()->updateOrCreate(
                ['status' => 'active'],
                ['basic_salary' => $row['base'], 'grade_pay' => 0],
            );
            $structure->employeeSalaryComponents()->delete();

            $lines = [[$components['BASIC'], 'Basic', (float) $row['base']]];
            if ((float) $row['enhancement'] > 0) {
                $lines[] = [$components['ENHANCE'], 'Enhancement @10%', (float) $row['enhancement']];
            }
            if ((float) $row['ptax'] > 0) {
                $lines[] = [$components['PTAX'], 'P.Tax', (float) $row['ptax']];
            }
            foreach ($lines as [$componentId, $label, $amount]) {
                $structure->employeeSalaryComponents()->create([
                    'salary_component_id' => $componentId,
                    'amount' => $amount,
                    'calculation_type' => 'fixed',
                    'status' => 'active',
                ]);
            }

            $imported++;
        }

        $this->command?->info("Imported/updated {$imported} employees.");
        if ($missingDivisions) {
            $this->command?->warn('Unmapped divisions (employees left without office): '.implode(', ', array_keys($missingDivisions)));
        }
    }

    /**
     * Build a login email from the employee name: name@jjmbrain.in, with
     * duplicate names disambiguated as name1@…, name2@… (first keeps no suffix).
     *
     * @param  array<string, int>  $seen  base slug => how many already used
     */
    private function uniqueEmail(string $name, string $code, array &$seen): string
    {
        $base = Str::of($name)->slug('.')->toString();
        if ($base === '') {
            $base = strtolower($code);
        }

        $count = $seen[$base] ?? 0;
        $seen[$base] = $count + 1;
        $local = $count === 0 ? $base : $base.$count;

        return $local.'@jjmbrain.in';
    }

    /**
     * @return array<string, int>  stream key => department_stream_id
     */
    private function resolveStreams(): array
    {
        $dmmu = DepartmentStream::query()->firstOrCreate(['code' => 'DMMU'], ['name' => 'DMMU', 'status' => 'active']);

        $byCode = DepartmentStream::query()->pluck('id', 'code');

        return [
            'DMMU' => $dmmu->id,
            'SMMU' => $byCode['JJMHQ'] ?? $dmmu->id,       // SMMU -> JJM HQ
            'SRL' => $byCode['JJMSLR'] ?? $dmmu->id,       // SRL  -> JJM SLR
            'KRC' => $byCode['JJMKRC'] ?? $dmmu->id,       // KRC  -> JJM KRC
            'UNICEF' => $byCode['JJMUNICEF'] ?? $dmmu->id, // UNICEF -> JJM UNICEF
            'GRADEIV' => $byCode['JJMHQ'] ?? $dmmu->id,    // Grade-IV allowance staff -> JJM HQ (directorate)
        ];
    }

    /**
     * @return array<string, int>  category code => staff_category_id
     */
    private function resolveCategories(): array
    {
        return [
            'SUPPORT' => StaffCategory::query()->firstOrCreate(['code' => 'SUPPORT'], ['name' => 'Support', 'status' => 'active'])->id,
            'WQ' => StaffCategory::query()->firstOrCreate(['code' => 'WQ'], ['name' => 'WQ', 'status' => 'active'])->id,
        ];
    }

    /**
     * @return array<string, int>  BASIC/ENHANCE/PTAX => salary_component_id
     */
    private function resolveComponents(): array
    {
        $basic = SalaryComponent::query()->firstOrCreate(
            ['code' => 'BASIC'],
            ['name' => 'Basic', 'type' => 'earning', 'calculation_type' => 'fixed', 'default_amount' => 0, 'is_taxable' => true, 'is_deduction' => false, 'status' => 'active'],
        );
        $enhance = SalaryComponent::query()->firstOrCreate(
            ['code' => 'ENHANCE'],
            ['name' => 'Enhancement @10%', 'type' => 'earning', 'calculation_type' => 'fixed', 'default_amount' => 0, 'is_taxable' => true, 'is_deduction' => false, 'status' => 'active'],
        );
        $ptax = SalaryComponent::query()->firstOrCreate(
            ['code' => 'PTAX'],
            ['name' => 'PTAX', 'type' => 'deduction', 'calculation_type' => 'fixed', 'default_amount' => 0, 'is_taxable' => false, 'is_deduction' => true, 'status' => 'active'],
        );

        return ['BASIC' => $basic->id, 'ENHANCE' => $enhance->id, 'PTAX' => $ptax->id];
    }

    /**
     * Trim a value and hard-clip it to the column length (null when empty).
     */
    private function clip(mixed $value, int $length): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : mb_substr($value, 0, $length);
    }

    /**
     * Sanitise an IFSC code: uppercase, strip stray junk (the source has values
     * like "SBIN0015287="), then clip to the 11-char column.
     */
    private function clipIfsc(mixed $value): ?string
    {
        $value = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) $value));

        return $value === '' ? null : substr($value, 0, 11);
    }

    private function designationCode(string $name): string
    {
        return Str::of($name)->upper()->replaceMatches('/[^A-Z0-9]+/', '-')->trim('-')->limit(48, '')->toString();
    }
}

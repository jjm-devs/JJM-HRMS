<?php

namespace Database\Seeders;

use App\Models\Employee;
use Illuminate\Database\Seeder;

class EmployeeBankDetailsSeeder extends Seeder
{
    /**
     * Seed dummy bank details for existing employees.
     */
    public function run(): void
    {
        $banks = [
            ['State Bank of India', 'SBIN', 'Dispur Branch'],
            ['HDFC Bank', 'HDFC', 'Guwahati Main Branch'],
            ['ICICI Bank', 'ICIC', 'Ganeshguri Branch'],
            ['Axis Bank', 'UTIB', 'Beltola Branch'],
            ['Punjab National Bank', 'PUNB', 'Panbazar Branch'],
        ];

        Employee::query()
            ->orderBy('id')
            ->chunkById(100, function ($employees) use ($banks): void {
                foreach ($employees as $employee) {
                    [$bankName, $ifscPrefix, $branch] = $banks[$employee->id % count($banks)];
                    $suffix = str_pad((string) $employee->id, 6, '0', STR_PAD_LEFT);

                    $employee->forceFill([
                        'bank_account_number' => '2026'.str_pad((string) ($employee->id * 7919), 10, '0', STR_PAD_LEFT),
                        'bank_ifsc_code' => $ifscPrefix.'0'.$suffix,
                        'bank_name' => $bankName,
                        'bank_branch' => $branch,
                    ])->save();
                }
            });
    }
}

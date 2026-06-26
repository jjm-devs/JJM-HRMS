<?php

namespace Database\Seeders;

use App\Models\SanctionActivity;
use Illuminate\Database\Seeder;

class SanctionActivitySeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            ['Support Activity (Training-HRD)', 'SMMU Specialist/staffs'],
            ['WQMSP', 'SMMU Specialist/staffs'],
        ] as [$name, $staffCategory]) {
            SanctionActivity::query()->updateOrCreate(
                ['name' => $name],
                ['staff_category' => $staffCategory, 'status' => 'active'],
            );
        }
    }
}

<?php

namespace Database\Seeders;

use App\Models\StaffCategory;
use Illuminate\Database\Seeder;

class StaffCategorySeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            ['SUPPORT', 'Support'],
            ['WQ', 'WQ'],
        ] as [$code, $name]) {
            StaffCategory::query()->updateOrCreate(
                ['code' => $code],
                ['name' => $name, 'status' => 'active'],
            );
        }
    }
}

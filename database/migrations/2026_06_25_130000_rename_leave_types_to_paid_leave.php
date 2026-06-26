<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Earned Leave is renamed to Paid Leave (the monthly 2-day bank type).
        DB::table('leave_types')->where('code', 'EL')->update([
            'code' => 'PL',
            'name' => 'Paid Leave',
            'updated_at' => now(),
        ]);

        // Retire Casual Leave. Delete it only when nothing references it; otherwise deactivate.
        $casual = DB::table('leave_types')->where('code', 'CL')->first();

        if ($casual) {
            $hasApplications = DB::table('leave_applications')->where('leave_type_id', $casual->id)->exists();

            if ($hasApplications) {
                DB::table('leave_types')->where('id', $casual->id)->update([
                    'status' => 'inactive',
                    'updated_at' => now(),
                ]);
            } else {
                DB::table('leave_policies')->where('leave_type_id', $casual->id)->delete();
                DB::table('leave_balances')->where('leave_type_id', $casual->id)->delete();
                DB::table('leave_types')->where('id', $casual->id)->delete();
            }
        }
    }

    public function down(): void
    {
        DB::table('leave_types')->where('code', 'PL')->update([
            'code' => 'EL',
            'name' => 'Earned Leave',
            'updated_at' => now(),
        ]);
        // Casual Leave is not restored automatically.
    }
};

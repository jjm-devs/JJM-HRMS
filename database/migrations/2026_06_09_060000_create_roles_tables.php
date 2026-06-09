<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->text('description')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
        });

        Schema::create('role_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['role_id', 'user_id']);
        });

        $now = now();

        DB::table('roles')->insertOrIgnore([
            [
                'name' => 'HR',
                'code' => 'hr',
                'description' => 'Payroll preparation and HR working panel access.',
                'status' => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'SPO FM',
                'code' => 'spo_fm',
                'description' => 'Payroll workflow approver: SPO FM.',
                'status' => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Deputy MD',
                'code' => 'deputy_md',
                'description' => 'Payroll workflow approver: Deputy MD.',
                'status' => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'FA',
                'code' => 'fa',
                'description' => 'Payroll workflow approver: FA.',
                'status' => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Addt Chief Eng',
                'code' => 'addt_chief_eng',
                'description' => 'Payroll workflow approver: Addt Chief Eng.',
                'status' => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Addt. MD',
                'code' => 'addt_md',
                'description' => 'Payroll workflow approver: Addt. MD.',
                'status' => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'MD',
                'code' => 'md',
                'description' => 'Final payroll approval authority.',
                'status' => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);

        $hrRoleId = DB::table('roles')->where('code', 'hr')->value('id');

        if ($hrRoleId) {
            $existingHrUserIds = DB::table('users')
                ->where('is_hr', true)
                ->pluck('id');

            foreach ($existingHrUserIds as $userId) {
                DB::table('role_user')->insertOrIgnore([
                    'role_id' => $hrRoleId,
                    'user_id' => $userId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('role_user');
        Schema::dropIfExists('roles');
    }
};

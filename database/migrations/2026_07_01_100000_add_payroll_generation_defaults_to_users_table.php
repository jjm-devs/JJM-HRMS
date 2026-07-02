<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Remembers the HR's last payroll office/stream selection so the next
            // generation is pre-filled (they can still change it).
            $table->json('payroll_generation_defaults')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('payroll_generation_defaults');
        });
    }
};

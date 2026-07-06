<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            // Operational category for district (DMMU) staff: Support / WQ.
            // Nullable because it only applies to district staff.
            $table->foreignId('staff_category_id')
                ->nullable()
                ->after('department_stream_id')
                ->constrained()
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropConstrainedForeignId('staff_category_id');
        });
    }
};

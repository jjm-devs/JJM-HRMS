<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('payroll_batches', function (Blueprint $table) {
            $table->foreignId('department_stream_id')
                ->nullable()
                ->after('org_unit_id')
                ->constrained('department_streams')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payroll_batches', function (Blueprint $table) {
            $table->dropForeignIdFor(\App\Models\DepartmentStream::class);
            $table->dropColumn('department_stream_id');
        });
    }
};

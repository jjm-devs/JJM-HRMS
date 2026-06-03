<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employee_salary_components', function (Blueprint $table) {
            $table->decimal('percentage_rate', 8, 2)->nullable()->after('amount');
            $table->string('calculation_base')->nullable()->after('calculation_type');
        });
    }

    public function down(): void
    {
        Schema::table('employee_salary_components', function (Blueprint $table) {
            $table->dropColumn(['percentage_rate', 'calculation_base']);
        });
    }
};

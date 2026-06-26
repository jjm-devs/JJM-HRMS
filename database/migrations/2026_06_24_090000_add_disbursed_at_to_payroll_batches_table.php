<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payroll_batches', function (Blueprint $table) {
            $table->timestamp('disbursed_at')->nullable()->after('locked_at');
        });
    }

    public function down(): void
    {
        Schema::table('payroll_batches', function (Blueprint $table) {
            $table->dropColumn('disbursed_at');
        });
    }
};

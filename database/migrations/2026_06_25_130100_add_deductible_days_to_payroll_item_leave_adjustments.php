<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payroll_item_leave_adjustments', function (Blueprint $table) {
            // Days from this leave that should be deducted under the auto rule
            // (paid-leave days beyond the 2/month bank, or all days for unpaid leave).
            $table->decimal('deductible_days', 8, 2)->default(0)->after('leave_days');
        });
    }

    public function down(): void
    {
        Schema::table('payroll_item_leave_adjustments', function (Blueprint $table) {
            $table->dropColumn('deductible_days');
        });
    }
};

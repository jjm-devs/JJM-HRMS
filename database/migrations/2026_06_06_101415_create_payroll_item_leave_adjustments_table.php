<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_item_leave_adjustments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('leave_application_id')->constrained()->cascadeOnDelete();

            // how many days from this leave application fall within the pay period
            $table->decimal('leave_days', 5, 1)->default(0);

            // system-computed classification at generation time
            $table->enum('auto_classification', ['salary_deduct', 'leave_bank', 'exempt']);

            // HR override — null means not yet reviewed, uses auto_classification
            $table->enum('hr_classification', ['salary_deduct', 'leave_bank', 'exempt'])->nullable();

            // snapshot of leave type name for display (avoids joins on review screen)
            $table->string('leave_type_name');
            $table->boolean('leave_type_is_paid');

            // whether employee had sufficient balance at generation time
            $table->boolean('had_sufficient_balance')->default(false);

            $table->timestamps();

            $table->index('payroll_item_id');
            $table->index('leave_application_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_item_leave_adjustments');
    }
};

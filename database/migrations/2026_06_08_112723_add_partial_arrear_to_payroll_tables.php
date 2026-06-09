<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payroll_batches', function (Blueprint $table) {
            $table->enum('batch_type', ['regular', 'partial', 'arrear'])
                ->default('regular')
                ->after('batch_number');

            $table->foreignId('parent_batch_id')
                ->nullable()
                ->after('batch_type')
                ->constrained('payroll_batches')
                ->nullOnDelete();

            $table->decimal('default_disbursement_pct', 5, 2)
                ->default(100.00)
                ->after('parent_batch_id')
                ->comment('Default % applied to all items. 100 for regular/arrear.');
        });

        Schema::table('payroll_items', function (Blueprint $table) {
            $table->decimal('disbursement_pct', 5, 2)
                ->default(100.00)
                ->after('net_salary')
                ->comment('% of net_salary to disburse. Overridable per employee for partial batches.');

            $table->decimal('disbursed_amount', 12, 2)
                ->default(0)
                ->after('disbursement_pct')
                ->comment('Actual amount paid in this batch = net_salary * disbursement_pct / 100');

            $table->decimal('outstanding_amount', 12, 2)
                ->default(0)
                ->after('disbursed_amount')
                ->comment('net_salary - disbursed_amount. Non-zero only on partial batches.');
        });
    }

    public function down(): void
    {
        Schema::table('payroll_batches', function (Blueprint $table) {
            $table->dropForeign(['parent_batch_id']);
            $table->dropColumn(['batch_type', 'parent_batch_id', 'default_disbursement_pct']);
        });

        Schema::table('payroll_items', function (Blueprint $table) {
            $table->dropColumn(['disbursement_pct', 'disbursed_amount', 'outstanding_amount']);
        });
    }
};
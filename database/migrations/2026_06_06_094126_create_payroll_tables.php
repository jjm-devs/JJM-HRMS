<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_batches', function (Blueprint $table) {
            $table->id();
            $table->string('batch_number')->unique();

            // Pay period — flexible, not locked to calendar month
            $table->date('period_from');                                        // e.g. 2026-05-25
            $table->date('period_to');                                          // e.g. 2026-06-25
            $table->date('payment_date')->nullable();                           // actual salary credit date

            $table->foreignId('org_unit_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('generated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();

            // Totals — sum of all payroll items, updated on each recalculation
            $table->decimal('gross_total', 14, 2)->default(0);
            $table->decimal('deduction_total', 14, 2)->default(0);
            $table->decimal('net_total', 14, 2)->default(0);

            // draft → pending → approved → disbursed
            $table->string('status')->default('draft');

            $table->timestamp('approved_at')->nullable();
            $table->timestamp('locked_at')->nullable();                         // set when approved, blocks further edits
            $table->timestamps();

            $table->index(['period_from', 'period_to']);
            $table->index(['org_unit_id', 'status']);
            $table->index('status');
        });

        Schema::create('payroll_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_batch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();

            // Salary figures — auto-calculated from salary_structures
            // HR can override any of these before locking
            $table->decimal('basic_salary', 12, 2)->default(0);
            $table->decimal('gross_salary', 12, 2)->default(0);
            $table->decimal('total_deductions', 12, 2)->default(0);
            $table->decimal('net_salary', 12, 2)->default(0);

            // Attendance — pulled from leave_application_days for the period
            // HR can manually correct these
            $table->decimal('attendance_days', 5, 2)->nullable();
            $table->decimal('leave_without_pay_days', 5, 2)->default(0);
            $table->decimal('lwp_deduction', 12, 2)->default(0);               // calculated: (basic / working_days) * lwp_days

            // Audit trail — was this item touched by HR after auto-generation?
            $table->boolean('is_manually_adjusted')->default(false);
            $table->text('adjustment_notes')->nullable();

            // draft → pending → approved → disbursed — mirrors batch status
            $table->string('status')->default('draft');

            $table->timestamps();

            // One entry per employee per batch
            $table->unique(['payroll_batch_id', 'employee_id']);
            $table->index(['payroll_batch_id', 'status']);
            $table->index(['employee_id', 'status']);
        });

        Schema::create('payroll_item_components', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_item_id')->constrained()->cascadeOnDelete();

            // Nullable — in case component master is deleted later
            $table->foreignId('salary_component_id')->nullable()->constrained()->nullOnDelete();

            // Snapshotted at time of generation — historical accuracy
            $table->string('name');                                             // e.g. "House Rent Allowance"
            $table->string('type');                                             // earning | deduction | employer_contribution

            $table->decimal('amount', 12, 2)->default(0);

            // How it was calculated — shown on payslip
            // e.g. "40% of Basic Salary (₹28,000)" or "Fixed amount"
            $table->text('calculation_details')->nullable();

            // Was this component amount manually overridden by HR?
            $table->boolean('is_manually_adjusted')->default(false);

            $table->timestamps();

            $table->index(['payroll_item_id', 'type']);
        });

        Schema::create('payslips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_item_id')->constrained()->cascadeOnDelete();

            // Optional link to the documents table for PDF storage
            $table->foreignId('document_id')->nullable()->constrained()->nullOnDelete();

            $table->string('payslip_number')->unique();                         // e.g. SLIP-2026-06-EMP001
            $table->timestamp('generated_at')->nullable();
            $table->unsignedInteger('download_count')->default(0);

            // draft | issued
            $table->string('status')->default('draft');

            $table->timestamps();

            $table->index('payroll_item_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payslips');
        Schema::dropIfExists('payroll_item_components');
        Schema::dropIfExists('payroll_items');
        Schema::dropIfExists('payroll_batches');
    }
};

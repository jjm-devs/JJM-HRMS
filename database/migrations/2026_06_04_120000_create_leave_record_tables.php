<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('leave_applications')) {
            Schema::create('leave_applications', function (Blueprint $table) {
                $table->id();
                $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
                $table->foreignId('leave_type_id')->constrained()->cascadeOnDelete();
                $table->date('start_date');
                $table->date('end_date');
                $table->decimal('total_days', 8, 2)->default(0);
                $table->text('reason')->nullable();
                $table->string('contact_during_leave')->nullable();
                $table->string('source')->default('manual_hr');
                $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
                $table->string('status')->default('approved');
                $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('approved_at')->nullable();
                $table->text('approval_remarks')->nullable();
                $table->timestamps();

                $table->index(['employee_id', 'start_date', 'end_date']);
                $table->index(['leave_type_id', 'status']);
                $table->index(['source', 'status']);
            });
        }

        if (! Schema::hasTable('leave_application_days')) {
            Schema::create('leave_application_days', function (Blueprint $table) {
                $table->id();
                $table->foreignId('leave_application_id')->constrained()->cascadeOnDelete();
                $table->date('leave_date');
                $table->string('day_type')->default('full_day');
                $table->decimal('duration', 4, 2)->default(1);
                $table->string('status')->default('approved');
                $table->timestamps();

                $table->unique(['leave_application_id', 'leave_date']);
                $table->index(['leave_date', 'status']);
            });
        }

        if (! Schema::hasTable('leave_balances')) {
            Schema::create('leave_balances', function (Blueprint $table) {
                $table->id();
                $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
                $table->foreignId('leave_type_id')->constrained()->cascadeOnDelete();
                $table->unsignedSmallInteger('year');
                $table->decimal('opening_balance', 8, 2)->default(0);
                $table->decimal('credited', 8, 2)->default(0);
                $table->decimal('availed', 8, 2)->default(0);
                $table->decimal('encashed', 8, 2)->default(0);
                $table->decimal('lapsed', 8, 2)->default(0);
                $table->decimal('closing_balance', 8, 2)->default(0);
                $table->timestamps();

                $table->unique(['employee_id', 'leave_type_id', 'year']);
            });
        }

        if (! Schema::hasTable('leave_cancellations')) {
            Schema::create('leave_cancellations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('leave_application_id')->constrained()->cascadeOnDelete();
                $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
                $table->text('reason')->nullable();
                $table->string('status')->default('approved');
                $table->timestamp('approved_at')->nullable();
                $table->text('approval_remarks')->nullable();
                $table->timestamps();

                $table->index(['leave_application_id', 'status']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_cancellations');
        Schema::dropIfExists('leave_balances');
        Schema::dropIfExists('leave_application_days');
        Schema::dropIfExists('leave_applications');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('attendance_logs')) {
            Schema::create('attendance_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
                $table->date('attendance_date');
                $table->timestamp('check_in')->nullable();
                $table->timestamp('check_out')->nullable();
                $table->string('status')->default('present');
                $table->string('source')->default('employee_self');
                $table->decimal('latitude', 10, 7)->nullable();
                $table->decimal('longitude', 10, 7)->nullable();
                $table->string('device_id')->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->text('remarks')->nullable();
                $table->timestamps();

                $table->unique(['employee_id', 'attendance_date']);
                $table->index(['attendance_date', 'status']);
            });
        }

        if (! Schema::hasTable('attendance_correction_requests')) {
            Schema::create('attendance_correction_requests', function (Blueprint $table) {
                $table->id();
                $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
                $table->foreignId('attendance_log_id')->nullable()->constrained()->nullOnDelete();
                $table->timestamp('requested_check_in')->nullable();
                $table->timestamp('requested_check_out')->nullable();
                $table->text('reason');
                $table->string('status')->default('submitted');
                $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('approved_at')->nullable();
                $table->text('approval_remarks')->nullable();
                $table->timestamps();

                $table->index(['employee_id', 'status']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_correction_requests');
        Schema::dropIfExists('attendance_logs');
    }
};

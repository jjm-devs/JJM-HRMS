<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transfer_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('from_org_unit_id')->nullable()->constrained('org_units')->nullOnDelete();
            $table->foreignId('to_org_unit_id')->nullable()->constrained('org_units')->nullOnDelete();
            $table->foreignId('initiated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('transfer_type')->default('administrative');
            $table->text('reason')->nullable();
            $table->date('requested_date')->nullable();
            $table->date('effective_date')->nullable();
            $table->string('status')->default('completed');
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->index(['employee_id', 'status']);
            $table->index('to_org_unit_id');
        });

        Schema::create('posting_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('org_unit_id')->nullable()->constrained('org_units')->nullOnDelete();
            $table->foreignId('designation_id')->nullable()->constrained('designations')->nullOnDelete();
            $table->date('from_date')->nullable();
            $table->date('to_date')->nullable();
            $table->string('order_number')->nullable();
            $table->text('remarks')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();

            $table->index(['employee_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('posting_histories');
        Schema::dropIfExists('transfer_requests');
    }
};

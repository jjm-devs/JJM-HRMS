<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pay_matrices', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->text('description')->nullable();
            $table->date('effective_from')->nullable();
            $table->date('effective_to')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
        });

        Schema::create('pay_levels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pay_matrix_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('code')->unique();
            $table->unsignedInteger('level_order')->default(0);
            $table->decimal('min_basic', 12, 2)->nullable();
            $table->decimal('max_basic', 12, 2)->nullable();
            $table->decimal('increment_amount', 12, 2)->nullable();
            $table->string('status')->default('active');
            $table->timestamps();

            $table->index(['pay_matrix_id', 'status']);
        });

        Schema::create('salary_components', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->string('type');
            $table->string('calculation_type')->default('fixed');
            $table->decimal('default_amount', 12, 2)->default(0);
            $table->text('formula')->nullable();
            $table->boolean('is_taxable')->default(false);
            $table->boolean('is_deduction')->default(false);
            $table->string('status')->default('active');
            $table->timestamps();

            $table->index(['type', 'status']);
        });

        Schema::create('leave_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->text('description')->nullable();
            $table->boolean('is_paid')->default(true);
            $table->boolean('requires_document')->default(false);
            $table->boolean('allow_half_day')->default(true);
            $table->string('status')->default('active');
            $table->timestamps();
        });

        Schema::create('leave_policies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('leave_type_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employment_type_id')->nullable()->constrained()->nullOnDelete();
            $table->string('gender')->nullable();
            $table->string('service_type')->nullable();
            $table->decimal('annual_quota', 8, 2)->default(0);
            $table->decimal('max_days_per_request', 8, 2)->nullable();
            $table->decimal('carry_forward_limit', 8, 2)->nullable();
            $table->decimal('encashable_limit', 8, 2)->nullable();
            $table->json('rules')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();

            $table->index(['leave_type_id', 'employment_type_id']);
            $table->index(['gender', 'service_type']);
        });

        Schema::create('holidays', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->date('holiday_date');
            $table->string('type')->default('state');
            $table->string('state')->nullable();
            $table->string('district')->nullable();
            $table->foreignId('org_unit_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status')->default('active');
            $table->timestamps();

            $table->index(['holiday_date', 'type']);
            $table->index(['org_unit_id', 'status']);
        });

        Schema::create('document_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->text('description')->nullable();
            $table->boolean('requires_verification')->default(true);
            $table->boolean('has_expiry')->default(false);
            $table->string('status')->default('active');
            $table->timestamps();
        });

        Schema::create('workflow_definitions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->string('module');
            $table->text('description')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();

            $table->index(['module', 'status']);
        });

        Schema::create('workflow_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workflow_definition_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->unsignedInteger('sequence')->default(1);
            $table->string('role');
            $table->string('action_type')->default('approve');
            $table->unsignedInteger('sla_hours')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();

            $table->index(['workflow_definition_id', 'sequence']);
        });

        Schema::create('grievance_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->text('description')->nullable();
            $table->unsignedInteger('sla_hours')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
        });

        Schema::create('notification_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->string('channel');
            $table->string('subject')->nullable();
            $table->text('body');
            $table->json('variables')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();

            $table->index(['channel', 'status']);
        });

        Schema::create('integration_settings', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->string('provider');
            $table->string('base_url')->nullable();
            $table->text('credentials')->nullable();
            $table->json('configuration')->nullable();
            $table->boolean('enabled')->default(false);
            $table->string('status')->default('inactive');
            $table->timestamps();

            $table->index(['provider', 'enabled']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('integration_settings');
        Schema::dropIfExists('notification_templates');
        Schema::dropIfExists('grievance_categories');
        Schema::dropIfExists('workflow_steps');
        Schema::dropIfExists('workflow_definitions');
        Schema::dropIfExists('document_types');
        Schema::dropIfExists('holidays');
        Schema::dropIfExists('leave_policies');
        Schema::dropIfExists('leave_types');
        Schema::dropIfExists('salary_components');
        Schema::dropIfExists('pay_levels');
        Schema::dropIfExists('pay_matrices');
    }
};

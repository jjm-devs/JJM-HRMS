<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payroll_item_adjustments', function (Blueprint $table) {
            $table->foreignId('workflow_instance_id')
                ->nullable()
                ->after('created_by')
                ->constrained()
                ->nullOnDelete();

            $table->foreignId('workflow_step_id')
                ->nullable()
                ->after('workflow_instance_id')
                ->constrained()
                ->nullOnDelete();

            $table->string('role')->nullable()->after('workflow_step_id');

            $table->foreignId('updated_by')
                ->nullable()
                ->after('role')
                ->constrained('users')
                ->nullOnDelete();

            $table->softDeletes();
        });

        Schema::create('payroll_adjustment_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_batch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('payroll_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('payroll_item_adjustment_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();
            $table->foreignId('workflow_instance_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();
            $table->foreignId('workflow_step_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('role')->nullable();
            $table->string('action');
            $table->json('before_values')->nullable();
            $table->json('after_values')->nullable();
            $table->decimal('old_item_net_salary', 12, 2)->nullable();
            $table->decimal('new_item_net_salary', 12, 2)->nullable();
            $table->decimal('old_batch_net_total', 14, 2)->nullable();
            $table->decimal('new_batch_net_total', 14, 2)->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->index(['payroll_batch_id', 'created_at']);
            $table->index(['workflow_instance_id', 'workflow_step_id'], 'pal_wf_instance_step_idx');
            $table->index(['actor_id', 'role']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_adjustment_logs');

        Schema::table('payroll_item_adjustments', function (Blueprint $table) {
            $table->dropForeign(['workflow_instance_id']);
            $table->dropForeign(['workflow_step_id']);
            $table->dropForeign(['updated_by']);
            $table->dropColumn([
                'workflow_instance_id',
                'workflow_step_id',
                'role',
                'updated_by',
            ]);
            $table->dropSoftDeletes();
        });
    }
};

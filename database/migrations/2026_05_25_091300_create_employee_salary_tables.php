<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('salary_structures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('pay_level_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('basic_salary', 12, 2)->default(0);
            $table->decimal('grade_pay', 12, 2)->nullable();
            $table->date('effective_from')->nullable();
            $table->date('effective_to')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();

            $table->index(['employee_id', 'status']);
            $table->index(['pay_level_id', 'status']);
        });

        Schema::create('employee_salary_components', function (Blueprint $table) {
            $table->id();
            $table->foreignId('salary_structure_id')->constrained()->cascadeOnDelete();
            $table->foreignId('salary_component_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('amount', 12, 2)->default(0);
            $table->string('calculation_type')->default('fixed');
            $table->text('formula')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();

            $table->index(['salary_structure_id', 'status']);
            $table->index(['salary_component_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_salary_components');
        Schema::dropIfExists('salary_structures');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('hr_policies', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('category'); // leave, service_rules, conduct, general
            $table->string('file_path');
            $table->boolean('is_active')->default(true);
            $table->foreignId('uploaded_by')->constrained('users');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hr_policies');
    }
};

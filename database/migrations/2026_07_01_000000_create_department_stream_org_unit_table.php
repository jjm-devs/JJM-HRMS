<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('department_stream_org_unit', function (Blueprint $table) {
            $table->id();
            $table->foreignId('department_stream_id')->constrained()->cascadeOnDelete();
            $table->foreignId('org_unit_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['department_stream_id', 'org_unit_id'], 'stream_org_unit_unique');
            $table->index(['org_unit_id', 'department_stream_id'], 'org_unit_stream_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('department_stream_org_unit');
    }
};

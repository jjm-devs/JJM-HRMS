<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('leave_applications')) {
            return;
        }

        Schema::table('leave_applications', function (Blueprint $table) {
            if (! Schema::hasColumn('leave_applications', 'source')) {
                $table->string('source')->default('manual_hr');
            }

            if (! Schema::hasColumn('leave_applications', 'recorded_by')) {
                $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            }

            if (! Schema::hasColumn('leave_applications', 'submitted_by')) {
                $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            }
        });

    }

    public function down(): void
    {
        //
    }
};

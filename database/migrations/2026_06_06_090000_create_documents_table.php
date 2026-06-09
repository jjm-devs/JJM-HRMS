<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('documents')) {
            return;
        }

        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->morphs('documentable');
            $table->foreignId('document_type_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->string('file_name');
            $table->string('file_path');
            $table->string('disk')->default('local');
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('file_size')->default(0);
            $table->unsignedInteger('version')->default(1);
            $table->string('status')->default('uploaded');
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->date('expires_at')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->index(['status', 'uploaded_by']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('department_streams', function (Blueprint $table) {
            $table->text('description')->nullable()->after('code');
        });

        Schema::table('employment_types', function (Blueprint $table) {
            $table->text('description')->nullable()->after('code');
        });

        Schema::create('cadres', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->text('description')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
        });

        Schema::create('designations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cadre_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('department_stream_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('code')->unique();
            $table->string('level')->nullable();
            $table->text('description')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();

            $table->index(['cadre_id', 'status']);
            $table->index(['department_stream_id', 'status']);
        });

        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->string('employee_code')->unique();
            $table->foreignId('org_unit_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('department_stream_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('employment_type_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('designation_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('cadre_id')->nullable()->constrained()->nullOnDelete();
            $table->string('full_name');
            $table->string('father_name')->nullable();
            $table->string('mother_name')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('gender')->nullable();
            $table->string('blood_group')->nullable();
            $table->string('aadhaar_number')->nullable()->unique();
            $table->string('pan_number')->nullable()->unique();
            $table->date('joining_date')->nullable();
            $table->date('retirement_date')->nullable();
            $table->string('service_status')->default('active');
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->index(['org_unit_id', 'service_status']);
            $table->index(['department_stream_id', 'employment_type_id']);
            $table->index(['designation_id', 'cadre_id']);
        });

        Schema::create('employee_contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->string('label')->nullable();
            $table->string('value')->nullable();
            $table->string('address_line_1')->nullable();
            $table->string('address_line_2')->nullable();
            $table->string('city')->nullable();
            $table->string('district')->nullable();
            $table->string('state')->nullable();
            $table->string('pincode')->nullable();
            $table->boolean('is_primary')->default(false);
            $table->timestamps();

            $table->index(['employee_id', 'type']);
        });

        Schema::create('employee_family_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('relationship');
            $table->date('date_of_birth')->nullable();
            $table->string('gender')->nullable();
            $table->string('mobile')->nullable();
            $table->string('occupation')->nullable();
            $table->boolean('is_dependent')->default(false);
            $table->boolean('is_nominee')->default(false);
            $table->decimal('nominee_share', 5, 2)->nullable();
            $table->timestamps();

            $table->index(['employee_id', 'relationship']);
        });

        Schema::create('employee_qualifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('document_id')->nullable();
            $table->string('qualification');
            $table->string('institution')->nullable();
            $table->string('board_or_university')->nullable();
            $table->unsignedSmallInteger('year_of_passing')->nullable();
            $table->string('percentage_or_cgpa')->nullable();
            $table->string('specialization')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();

            $table->index('document_id');
            $table->index(['employee_id', 'status']);
        });

        Schema::create('employee_experiences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('document_id')->nullable();
            $table->string('organization_name');
            $table->string('designation')->nullable();
            $table->date('from_date')->nullable();
            $table->date('to_date')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_government_service')->default(false);
            $table->timestamps();

            $table->index('document_id');
            $table->index(['employee_id', 'from_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_experiences');
        Schema::dropIfExists('employee_qualifications');
        Schema::dropIfExists('employee_family_members');
        Schema::dropIfExists('employee_contacts');
        Schema::dropIfExists('employees');
        Schema::dropIfExists('designations');
        Schema::dropIfExists('cadres');

        Schema::table('employment_types', function (Blueprint $table) {
            $table->dropColumn('description');
        });

        Schema::table('department_streams', function (Blueprint $table) {
            $table->dropColumn('description');
        });
    }
};

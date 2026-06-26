<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('employees', 'bank_account_number')) {
            Schema::table('employees', function (Blueprint $table) {
                $table->string('bank_account_number', 40)->nullable()->after('pan_number');
            });
        }

        if (! Schema::hasColumn('employees', 'bank_ifsc_code')) {
            Schema::table('employees', function (Blueprint $table) {
                $table->string('bank_ifsc_code', 11)->nullable()->after('bank_account_number');
            });
        }

        if (! Schema::hasColumn('employees', 'bank_name')) {
            Schema::table('employees', function (Blueprint $table) {
                $table->string('bank_name', 150)->nullable()->after('bank_ifsc_code');
            });
        }

        if (! Schema::hasColumn('employees', 'bank_branch')) {
            Schema::table('employees', function (Blueprint $table) {
                $table->string('bank_branch', 150)->nullable()->after('bank_name');
            });
        }
    }

    public function down(): void
    {
        $columns = array_values(array_filter([
            'bank_account_number',
            'bank_ifsc_code',
            'bank_name',
            'bank_branch',
        ], fn (string $column): bool => Schema::hasColumn('employees', $column)));

        if ($columns !== []) {
            Schema::table('employees', function (Blueprint $table) use ($columns) {
                $table->dropColumn($columns);
            });
        }
    }
};

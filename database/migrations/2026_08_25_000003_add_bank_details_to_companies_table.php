<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('companies')) {
            return;
        }

        Schema::table('companies', function (Blueprint $table) {
            if (! Schema::hasColumn('companies', 'bank_account_number')) {
                $table->string('bank_account_number', 50)->nullable()->after('gstin');
            }
            if (! Schema::hasColumn('companies', 'bank_name')) {
                $table->string('bank_name', 150)->nullable()->after('bank_account_number');
            }
            if (! Schema::hasColumn('companies', 'bank_branch')) {
                $table->string('bank_branch', 150)->nullable()->after('bank_name');
            }
            if (! Schema::hasColumn('companies', 'bank_address')) {
                $table->string('bank_address', 500)->nullable()->after('bank_branch');
            }
            if (! Schema::hasColumn('companies', 'bank_ifsc')) {
                $table->string('bank_ifsc', 20)->nullable()->after('bank_address');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('companies')) {
            return;
        }

        Schema::table('companies', function (Blueprint $table) {
            foreach (['bank_account_number', 'bank_name', 'bank_branch', 'bank_address', 'bank_ifsc'] as $column) {
                if (Schema::hasColumn('companies', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};

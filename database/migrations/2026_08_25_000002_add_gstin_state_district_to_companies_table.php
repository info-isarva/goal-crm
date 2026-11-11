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
            if (! Schema::hasColumn('companies', 'gstin')) {
                $table->string('gstin', 20)->nullable()->after('website');
            }
            if (! Schema::hasColumn('companies', 'state')) {
                $table->string('state', 100)->nullable()->after('city');
            }
            if (! Schema::hasColumn('companies', 'district')) {
                $table->string('district', 100)->nullable()->after('state');
            }
            if (! Schema::hasColumn('companies', 'country')) {
                $table->string('country', 100)->nullable()->after('pincode');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('companies')) {
            return;
        }

        Schema::table('companies', function (Blueprint $table) {
            foreach (['gstin', 'state', 'district'] as $column) {
                if (Schema::hasColumn('companies', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};

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
        if (Schema::hasTable('companies') && !Schema::hasColumn('companies', 'expire_date')) {
            Schema::table('companies', function (Blueprint $table) {
                $table->date('expire_date')->nullable();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('companies') && Schema::hasColumn('companies', 'expire_date')) {
            Schema::table('companies', function (Blueprint $table) {
                $table->dropColumn('expire_date');
            });
        }
    }
};

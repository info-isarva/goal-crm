<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('leads') && !Schema::hasColumn('leads', 'referral_name')) {
            Schema::table('leads', function (Blueprint $table) {
                $table->string('referral_name', 255)->nullable()->after('lead_source');
            });
        }

        if (Schema::hasTable('deals') && !Schema::hasColumn('deals', 'referral_name')) {
            Schema::table('deals', function (Blueprint $table) {
                $table->string('referral_name', 255)->nullable()->after('lead_source');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('leads') && Schema::hasColumn('leads', 'referral_name')) {
            Schema::table('leads', function (Blueprint $table) {
                $table->dropColumn('referral_name');
            });
        }

        if (Schema::hasTable('deals') && Schema::hasColumn('deals', 'referral_name')) {
            Schema::table('deals', function (Blueprint $table) {
                $table->dropColumn('referral_name');
            });
        }
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('companies')) {
            return;
        }

        Schema::table('companies', function (Blueprint $table) {
            if (!Schema::hasColumn('companies', 'web_forms_enabled')) {
                $table->boolean('web_forms_enabled')->default(true)->after('whatsapp_send_enabled');
            }
            if (!Schema::hasColumn('companies', 'social_ads_enabled')) {
                $table->boolean('social_ads_enabled')->default(true)->after('web_forms_enabled');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('companies')) {
            return;
        }

        Schema::table('companies', function (Blueprint $table) {
            if (Schema::hasColumn('companies', 'social_ads_enabled')) {
                $table->dropColumn('social_ads_enabled');
            }
            if (Schema::hasColumn('companies', 'web_forms_enabled')) {
                $table->dropColumn('web_forms_enabled');
            }
        });
    }
};

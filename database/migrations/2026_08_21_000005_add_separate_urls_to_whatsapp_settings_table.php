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
        Schema::table('whatsapp_settings', function (Blueprint $table) {
            $table->string('isarva_api_url')->nullable()->default('https://wacrm.isarva.vercel.app/')->after('other_access_token');
            $table->string('other_api_url')->nullable()->default('https://graph.facebook.com')->after('isarva_api_url');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('whatsapp_settings', function (Blueprint $table) {
            $table->dropColumn(['isarva_api_url', 'other_api_url']);
        });
    }
};

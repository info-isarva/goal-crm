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
            if (!Schema::hasColumn('whatsapp_settings', 'quotation_template_id')) {
                $table->string('quotation_template_id')->nullable()->after('whatsapp_template_id');
            }
            if (!Schema::hasColumn('whatsapp_settings', 'quotation_language_code')) {
                $table->string('quotation_language_code', 20)->nullable()->after('quotation_template_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('whatsapp_settings', function (Blueprint $table) {
            if (Schema::hasColumn('whatsapp_settings', 'quotation_language_code')) {
                $table->dropColumn('quotation_language_code');
            }
            if (Schema::hasColumn('whatsapp_settings', 'quotation_template_id')) {
                $table->dropColumn('quotation_template_id');
            }
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('whatsapp_settings', function (Blueprint $table) {
            if (!Schema::hasColumn('whatsapp_settings', 'quotation_message_format')) {
                $table->text('quotation_message_format')->nullable()->after('quotation_language_code');
            }
        });
    }

    public function down(): void
    {
        Schema::table('whatsapp_settings', function (Blueprint $table) {
            if (Schema::hasColumn('whatsapp_settings', 'quotation_message_format')) {
                $table->dropColumn('quotation_message_format');
            }
        });
    }
};

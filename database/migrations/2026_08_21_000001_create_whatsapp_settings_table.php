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
        Schema::create('whatsapp_settings', function (Blueprint $table) {
            $table->id();
            $table->text('whatsapp_access_token')->nullable();
            $table->string('whatsapp_phone_number_id')->nullable();
            $table->string('whatsapp_api_url')->default('https://graph.facebook.com');
            $table->string('whatsapp_api_version')->default('v20.0');
            $table->string('whatsapp_template_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('whatsapp_settings');
    }
};

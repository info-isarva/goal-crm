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
        // In the migration
        Schema::table('users', function (Blueprint $table) {
            $table->string('fcm_token')->nullable();
            $table->string('device_platform')->nullable()->after('fcm_token'); // e.g., 'android', 'ios'
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('fcm_token');
            $table->dropColumn('device_platform');
        });
    }
};

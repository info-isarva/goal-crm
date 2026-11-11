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
            if (!Schema::hasColumn('companies', 'email_send_enabled')) {
                $table->boolean('email_send_enabled')->default(true)->after('admin_assignable_permissions');
            }
            if (!Schema::hasColumn('companies', 'whatsapp_send_enabled')) {
                $table->boolean('whatsapp_send_enabled')->default(true)->after('email_send_enabled');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('companies')) {
            return;
        }

        Schema::table('companies', function (Blueprint $table) {
            if (Schema::hasColumn('companies', 'whatsapp_send_enabled')) {
                $table->dropColumn('whatsapp_send_enabled');
            }
            if (Schema::hasColumn('companies', 'email_send_enabled')) {
                $table->dropColumn('email_send_enabled');
            }
        });
    }
};

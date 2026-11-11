<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        if (!Schema::hasTable('tasks')) {
            return;
        }
        Schema::table('tasks', function (Blueprint $table) {
            if (! Schema::hasColumn('tasks', 'reminder_notifications_enabled')) {
                $table->boolean('reminder_notifications_enabled')->default(true)->after('created_by');
            }
        });
    }

    public function down() {
        Schema::table('tasks', function (Blueprint $table) {
            if (Schema::hasColumn('tasks', 'reminder_notifications_enabled')) {
                $table->dropColumn('reminder_notifications_enabled');
            }
        });
    }
};

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
            if (!Schema::hasColumn('companies', 'weekly_target_alert_enabled')) {
                $table->boolean('weekly_target_alert_enabled')->default(true)->after('whatsapp_send_enabled');
            }
            if (!Schema::hasColumn('companies', 'weekly_target_alert_morning')) {
                $table->string('weekly_target_alert_morning', 5)->nullable()->default('09:00')->after('weekly_target_alert_enabled');
            }
            if (!Schema::hasColumn('companies', 'weekly_target_alert_afternoon')) {
                $table->string('weekly_target_alert_afternoon', 5)->nullable()->default('14:00')->after('weekly_target_alert_morning');
            }
            if (!Schema::hasColumn('companies', 'weekly_target_alert_evening')) {
                $table->string('weekly_target_alert_evening', 5)->nullable()->default('18:00')->after('weekly_target_alert_afternoon');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('companies')) {
            return;
        }

        Schema::table('companies', function (Blueprint $table) {
            foreach ([
                'weekly_target_alert_evening',
                'weekly_target_alert_afternoon',
                'weekly_target_alert_morning',
                'weekly_target_alert_enabled',
            ] as $column) {
                if (Schema::hasColumn('companies', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};

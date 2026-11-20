<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            if (! Schema::hasColumn('companies', 'goal_case_required_fields')) {
                $table->json('goal_case_required_fields')->nullable()->after('weekly_target_alert_evening');
            }
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            if (Schema::hasColumn('companies', 'goal_case_required_fields')) {
                $table->dropColumn('goal_case_required_fields');
            }
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('goal_vrm_call_logs', function (Blueprint $table) {
            if (! Schema::hasColumn('goal_vrm_call_logs', 'interest_level')) {
                $table->string('interest_level', 20)->nullable()->after('disposition');
            }
            if (! Schema::hasColumn('goal_vrm_call_logs', 'duration_seconds')) {
                $table->unsignedInteger('duration_seconds')->nullable()->after('interest_level');
            }
        });
    }

    public function down(): void
    {
        Schema::table('goal_vrm_call_logs', function (Blueprint $table) {
            if (Schema::hasColumn('goal_vrm_call_logs', 'duration_seconds')) {
                $table->dropColumn('duration_seconds');
            }
            if (Schema::hasColumn('goal_vrm_call_logs', 'interest_level')) {
                $table->dropColumn('interest_level');
            }
        });
    }
};
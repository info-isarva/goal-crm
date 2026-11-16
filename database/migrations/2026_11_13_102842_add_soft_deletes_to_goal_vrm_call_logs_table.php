<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('goal_vrm_call_logs', function (Blueprint $table) {
            if (!Schema::hasColumn('goal_vrm_call_logs', 'deleted_at')) {
                $table->softDeletes();
            }
        });
    }

    public function down(): void
    {
        Schema::table('goal_vrm_call_logs', function (Blueprint $table) {
            if (Schema::hasColumn('goal_vrm_call_logs', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });
    }
};

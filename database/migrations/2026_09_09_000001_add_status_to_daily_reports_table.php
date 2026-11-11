<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('daily_reports', 'status')) {
            Schema::table('daily_reports', function (Blueprint $table) {
                $table->string('status')->default('submitted')->after('other_activity');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('daily_reports', 'status')) {
            Schema::table('daily_reports', function (Blueprint $table) {
                $table->dropColumn('status');
            });
        }
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('goal_pms_items', function (Blueprint $table) {
            if (! Schema::hasColumn('goal_pms_items', 'follow_up_at')) {
                $table->timestamp('follow_up_at')->nullable()->after('notes');
            }
            if (! Schema::hasColumn('goal_pms_items', 'activity_type')) {
                $table->string('activity_type', 40)->nullable()->after('follow_up_at');
            }
            if (! Schema::hasColumn('goal_pms_items', 'referral_org_unit_id')) {
                $table->foreignId('referral_org_unit_id')->nullable()->after('activity_type')
                    ->constrained('goal_org_units')->nullOnDelete();
            }
        });

        Schema::table('goal_revenue_entries', function (Blueprint $table) {
            if (! Schema::hasColumn('goal_revenue_entries', 'entry_type')) {
                $table->string('entry_type', 20)->default('recognized')->after('amount');
            }
            if (! Schema::hasColumn('goal_revenue_entries', 'created_by')) {
                $table->foreignId('created_by')->nullable()->after('note')
                    ->constrained('users')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('goal_pms_items', function (Blueprint $table) {
            if (Schema::hasColumn('goal_pms_items', 'referral_org_unit_id')) {
                $table->dropConstrainedForeignId('referral_org_unit_id');
            }
            if (Schema::hasColumn('goal_pms_items', 'activity_type')) {
                $table->dropColumn('activity_type');
            }
            if (Schema::hasColumn('goal_pms_items', 'follow_up_at')) {
                $table->dropColumn('follow_up_at');
            }
        });

        Schema::table('goal_revenue_entries', function (Blueprint $table) {
            if (Schema::hasColumn('goal_revenue_entries', 'created_by')) {
                $table->dropConstrainedForeignId('created_by');
            }
            if (Schema::hasColumn('goal_revenue_entries', 'entry_type')) {
                $table->dropColumn('entry_type');
            }
        });
    }
};

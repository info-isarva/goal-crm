<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('goal_vrm_call_logs', function (Blueprint $table) {
            $table->unsignedBigInteger('goal_org_unit_id')->nullable()->after('goal_loan_lead_id');
            $table->index('goal_org_unit_id');
        });

        // Backfill from linked lead, then from caller's assigned branch.
        DB::statement('
            UPDATE goal_vrm_call_logs AS c
            INNER JOIN goal_loan_leads AS l ON l.id = c.goal_loan_lead_id
            SET c.goal_org_unit_id = l.goal_org_unit_id
            WHERE c.goal_org_unit_id IS NULL
              AND l.goal_org_unit_id IS NOT NULL
        ');

        DB::statement('
            UPDATE goal_vrm_call_logs AS c
            INNER JOIN users AS u ON u.id = c.user_id
            SET c.goal_org_unit_id = u.goal_org_unit_id
            WHERE c.goal_org_unit_id IS NULL
              AND u.goal_org_unit_id IS NOT NULL
        ');
    }

    public function down(): void
    {
        Schema::table('goal_vrm_call_logs', function (Blueprint $table) {
            $table->dropIndex(['goal_org_unit_id']);
            $table->dropColumn('goal_org_unit_id');
        });
    }
};

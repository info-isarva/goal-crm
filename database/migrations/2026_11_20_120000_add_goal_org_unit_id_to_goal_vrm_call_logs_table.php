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
        if (Schema::hasTable('goal_loan_leads') && Schema::hasColumn('goal_loan_leads', 'goal_org_unit_id')) {
            $fromLead = DB::table('goal_vrm_call_logs as c')
                ->join('goal_loan_leads as l', 'l.id', '=', 'c.goal_loan_lead_id')
                ->whereNull('c.goal_org_unit_id')
                ->whereNotNull('l.goal_org_unit_id')
                ->select('c.id', 'l.goal_org_unit_id')
                ->get();
            foreach ($fromLead as $row) {
                DB::table('goal_vrm_call_logs')->where('id', $row->id)->update([
                    'goal_org_unit_id' => $row->goal_org_unit_id,
                ]);
            }
        }

        if (Schema::hasColumn('users', 'goal_org_unit_id')) {
            $fromUser = DB::table('goal_vrm_call_logs as c')
                ->join('users as u', 'u.id', '=', 'c.user_id')
                ->whereNull('c.goal_org_unit_id')
                ->whereNotNull('u.goal_org_unit_id')
                ->select('c.id', 'u.goal_org_unit_id')
                ->get();
            foreach ($fromUser as $row) {
                DB::table('goal_vrm_call_logs')->where('id', $row->id)->update([
                    'goal_org_unit_id' => $row->goal_org_unit_id,
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('goal_vrm_call_logs', function (Blueprint $table) {
            $table->dropIndex(['goal_org_unit_id']);
            $table->dropColumn('goal_org_unit_id');
        });
    }
};

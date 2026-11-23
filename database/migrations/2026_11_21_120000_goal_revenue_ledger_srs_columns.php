<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('goal_revenue_entries', function (Blueprint $table) {
            if (! Schema::hasColumn('goal_revenue_entries', 'entry_ref')) {
                $table->string('entry_ref', 40)->nullable()->after('id')->index();
            }
            if (! Schema::hasColumn('goal_revenue_entries', 'status')) {
                $table->string('status', 30)->default('posted')->after('entry_type')->index();
            }
            if (! Schema::hasColumn('goal_revenue_entries', 'parent_entry_id')) {
                $table->foreignId('parent_entry_id')->nullable()->after('goal_disbursement_id')
                    ->constrained('goal_revenue_entries')->nullOnDelete();
            }
            if (! Schema::hasColumn('goal_revenue_entries', 'adjustment_direction')) {
                $table->string('adjustment_direction', 20)->nullable()->after('entry_type');
            }
            if (! Schema::hasColumn('goal_revenue_entries', 'reason')) {
                $table->string('reason', 500)->nullable()->after('note');
            }
            if (! Schema::hasColumn('goal_revenue_entries', 'evidence_path')) {
                $table->string('evidence_path', 255)->nullable()->after('reason');
            }
            if (! Schema::hasColumn('goal_revenue_entries', 'posted_at')) {
                $table->timestamp('posted_at')->nullable()->after('created_by');
            }
            if (! Schema::hasColumn('goal_revenue_entries', 'approved_by')) {
                $table->foreignId('approved_by')->nullable()->after('posted_at')
                    ->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('goal_revenue_entries', 'approved_at')) {
                $table->timestamp('approved_at')->nullable()->after('approved_by');
            }
        });

        // Backfill refs + status for existing rows
        $rows = DB::table('goal_revenue_entries')->orderBy('id')->get(['id', 'created_at', 'entry_type', 'note']);
        foreach ($rows as $row) {
            $stamp = $row->created_at ? date('ym', strtotime((string) $row->created_at)) : date('ym');
            $ref = 'RV-'.$stamp.'-'.str_pad((string) $row->id, 4, '0', STR_PAD_LEFT);
            DB::table('goal_revenue_entries')->where('id', $row->id)->update([
                'entry_ref' => $ref,
                'status' => 'posted',
                'posted_at' => $row->created_at,
                'reason' => $row->note,
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('goal_revenue_entries', function (Blueprint $table) {
            foreach (['approved_at', 'approved_by', 'posted_at', 'evidence_path', 'reason', 'adjustment_direction', 'parent_entry_id', 'status', 'entry_ref'] as $col) {
                if (Schema::hasColumn('goal_revenue_entries', $col)) {
                    if (in_array($col, ['parent_entry_id', 'approved_by'], true)) {
                        try {
                            $table->dropConstrainedForeignId($col);
                        } catch (\Throwable $e) {
                            $table->dropColumn($col);
                        }
                    } else {
                        $table->dropColumn($col);
                    }
                }
            }
        });
    }
};

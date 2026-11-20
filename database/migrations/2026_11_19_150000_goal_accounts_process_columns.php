<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('goal_accounts_items', function (Blueprint $table) {
            if (! Schema::hasColumn('goal_accounts_items', 'report_date')) {
                $table->date('report_date')->nullable()->after('report_number');
            }
            if (! Schema::hasColumn('goal_accounts_items', 'report_document_path')) {
                $table->string('report_document_path', 500)->nullable()->after('report_date');
            }
            if (! Schema::hasColumn('goal_accounts_items', 'remarks')) {
                $table->text('remarks')->nullable()->after('report_document_path');
            }
            if (! Schema::hasColumn('goal_accounts_items', 'submitted_by')) {
                $table->foreignId('submitted_by')->nullable()->after('remarks')->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('goal_accounts_items', 'submitted_for_approval_at')) {
                $table->timestamp('submitted_for_approval_at')->nullable()->after('submitted_by');
            }
        });

        if (! Schema::hasTable('goal_payout_adjustments')) {
            Schema::create('goal_payout_adjustments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('goal_payout_id')->constrained('goal_payouts')->cascadeOnDelete();
                $table->decimal('amount', 15, 2);
                $table->string('reason', 255);
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->index(['goal_payout_id', 'created_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('goal_payout_adjustments');

        Schema::table('goal_accounts_items', function (Blueprint $table) {
            foreach (['submitted_for_approval_at', 'submitted_by', 'remarks', 'report_document_path', 'report_date'] as $col) {
                if (Schema::hasColumn('goal_accounts_items', $col)) {
                    if (in_array($col, ['submitted_by'], true)) {
                        $table->dropConstrainedForeignId($col);
                    } else {
                        $table->dropColumn($col);
                    }
                }
            }
        });
    }
};

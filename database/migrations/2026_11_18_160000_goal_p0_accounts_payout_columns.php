<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('goal_accounts_items', function (Blueprint $table) {
            if (! Schema::hasColumn('goal_accounts_items', 'approved_by')) {
                $table->foreignId('approved_by')->nullable()->after('updated_by')->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('goal_accounts_items', 'approved_at')) {
                $table->timestamp('approved_at')->nullable()->after('approved_by');
            }
        });

        Schema::table('goal_payouts', function (Blueprint $table) {
            if (! Schema::hasColumn('goal_payouts', 'reconciliation_status')) {
                $table->string('reconciliation_status', 40)->nullable()->after('status');
            }
            if (! Schema::hasColumn('goal_payouts', 'difference_reason')) {
                $table->string('difference_reason')->nullable()->after('exception_reason');
            }
        });
    }

    public function down(): void
    {
        Schema::table('goal_accounts_items', function (Blueprint $table) {
            if (Schema::hasColumn('goal_accounts_items', 'approved_at')) {
                $table->dropColumn('approved_at');
            }
            if (Schema::hasColumn('goal_accounts_items', 'approved_by')) {
                $table->dropConstrainedForeignId('approved_by');
            }
        });

        Schema::table('goal_payouts', function (Blueprint $table) {
            if (Schema::hasColumn('goal_payouts', 'difference_reason')) {
                $table->dropColumn('difference_reason');
            }
            if (Schema::hasColumn('goal_payouts', 'reconciliation_status')) {
                $table->dropColumn('reconciliation_status');
            }
        });
    }
};

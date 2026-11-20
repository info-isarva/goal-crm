<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('goal_pms_items', function (Blueprint $table) {
            if (! Schema::hasColumn('goal_pms_items', 'assigned_to')) {
                $table->foreignId('assigned_to')->nullable()->after('status')->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('goal_pms_items', 'verification_started_at')) {
                $table->timestamp('verification_started_at')->nullable()->after('assigned_to');
            }
            if (! Schema::hasColumn('goal_pms_items', 'verification_remarks')) {
                $table->text('verification_remarks')->nullable()->after('notes');
            }
        });

        // Idempotency: one PMS queue row per verified disbursement.
        try {
            Schema::table('goal_pms_items', function (Blueprint $table) {
                $table->unique('goal_disbursement_id', 'goal_pms_items_disbursement_unique');
            });
        } catch (\Throwable $e) {
            // Index may already exist.
        }

        // Normalize legacy Pending → Pending Verification for display consistency.
        DB::table('goal_pms_items')->where('status', 'Pending')->update(['status' => 'Pending Verification']);

        if (! Schema::hasTable('goal_pms_verifications')) {
            Schema::create('goal_pms_verifications', function (Blueprint $table) {
                $table->id();
                $table->foreignId('goal_pms_item_id')->constrained('goal_pms_items')->cascadeOnDelete();
                $table->string('verification_result', 40); // Verified | Rejected | On Hold | Draft | In Progress
                $table->text('verification_remarks')->nullable();
                $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('verified_at')->nullable();
                $table->timestamps();

                $table->index(['goal_pms_item_id', 'verification_result'], 'gpms_verif_item_result_idx');
            });
        }

        if (! Schema::hasTable('goal_pms_followups')) {
            Schema::create('goal_pms_followups', function (Blueprint $table) {
                $table->id();
                $table->foreignId('goal_pms_item_id')->constrained('goal_pms_items')->cascadeOnDelete();
                $table->foreignId('goal_loan_case_id')->constrained('goal_loan_cases')->cascadeOnDelete();
                $table->string('followup_type', 60);
                $table->string('subject');
                $table->text('description')->nullable();
                $table->date('scheduled_date');
                $table->time('scheduled_time')->nullable();
                $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
                $table->string('status', 40)->default('Scheduled'); // Scheduled|Due|In Progress|Completed|Cancelled
                $table->string('priority', 20)->default('Medium'); // Low|Medium|High
                $table->timestamp('completed_at')->nullable();
                $table->foreignId('completed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->text('completion_remarks')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['goal_pms_item_id', 'status'], 'gpms_fu_item_status_idx');
                $table->index(['scheduled_date', 'status'], 'gpms_fu_date_status_idx');
                $table->index(['assigned_to', 'status'], 'gpms_fu_assignee_status_idx');
            });
        }

        if (! Schema::hasTable('goal_pms_assignment_history')) {
            Schema::create('goal_pms_assignment_history', function (Blueprint $table) {
                $table->id();
                $table->foreignId('goal_pms_item_id')->constrained('goal_pms_items')->cascadeOnDelete();
                $table->foreignId('previous_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('new_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
                $table->string('reassignment_reason')->nullable();
                $table->timestamp('assigned_at');
                $table->timestamps();

                $table->index(['goal_pms_item_id', 'assigned_at'], 'gpms_assign_item_at_idx');
            });
        }

        if (! Schema::hasTable('goal_pms_activity_logs')) {
            Schema::create('goal_pms_activity_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('goal_pms_item_id')->constrained('goal_pms_items')->cascadeOnDelete();
                $table->foreignId('goal_loan_case_id')->nullable()->constrained('goal_loan_cases')->nullOnDelete();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('activity_type', 60);
                $table->string('description');
                $table->text('old_value')->nullable();
                $table->text('new_value')->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->timestamp('created_at')->useCurrent();

                $table->index(['goal_pms_item_id', 'created_at'], 'gpms_act_item_at_idx');
                $table->index(['activity_type', 'created_at'], 'gpms_act_type_at_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('goal_pms_activity_logs');
        Schema::dropIfExists('goal_pms_assignment_history');
        Schema::dropIfExists('goal_pms_followups');
        Schema::dropIfExists('goal_pms_verifications');

        Schema::table('goal_pms_items', function (Blueprint $table) {
            if (Schema::hasColumn('goal_pms_items', 'verification_remarks')) {
                $table->dropColumn('verification_remarks');
            }
            if (Schema::hasColumn('goal_pms_items', 'verification_started_at')) {
                $table->dropColumn('verification_started_at');
            }
            if (Schema::hasColumn('goal_pms_items', 'assigned_to')) {
                $table->dropConstrainedForeignId('assigned_to');
            }
        });

        try {
            Schema::table('goal_pms_items', function (Blueprint $table) {
                $table->dropUnique('goal_pms_items_disbursement_unique');
            });
        } catch (\Throwable $e) {
            //
        }

        DB::table('goal_pms_items')->where('status', 'Pending Verification')->update(['status' => 'Pending']);
    }
};

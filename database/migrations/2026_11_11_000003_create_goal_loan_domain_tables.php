<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('goal_loan_leads', function (Blueprint $table) {
            $table->id();
            $table->string('ref_no', 40)->unique();
            $table->string('customer_name');
            $table->string('mobile', 20)->nullable();
            $table->string('email')->nullable();
            $table->string('source', 40)->nullable(); // vrm, channel, walkin, referral
            $table->string('channel_partner')->nullable();
            $table->string('product')->nullable();
            $table->decimal('loan_amount', 15, 2)->nullable();
            $table->string('lead_type', 20)->default('immediate'); // immediate | future
            $table->string('status', 40)->default('New');
            $table->string('disposition', 40)->nullable();
            $table->timestamp('follow_up_at')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('consent_recorded')->default(false);
            $table->foreignId('goal_org_unit_id')->nullable()->constrained('goal_org_units')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('assigned_at')->nullable();
            $table->string('closure_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'goal_org_unit_id']);
            $table->index('assigned_to');
        });

        Schema::create('goal_loan_lead_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('goal_loan_lead_id')->constrained('goal_loan_leads')->cascadeOnDelete();
            $table->foreignId('from_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('to_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('assigned_by')->constrained('users')->cascadeOnDelete();
            $table->string('action', 20)->default('assign'); // assign | reassign
            $table->string('reason')->nullable();
            $table->timestamps();
        });

        Schema::create('goal_loan_cases', function (Blueprint $table) {
            $table->id();
            $table->string('case_no', 40)->unique();
            $table->foreignId('goal_loan_lead_id')->nullable()->constrained('goal_loan_leads')->nullOnDelete();
            $table->foreignId('goal_org_unit_id')->nullable()->constrained('goal_org_units')->nullOnDelete();
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 40)->default('Draft');
            $table->string('customer_name');
            $table->string('mobile', 20)->nullable();
            $table->string('product')->nullable();
            $table->decimal('loan_amount', 15, 2)->nullable();
            $table->string('lender_name')->nullable();
            $table->string('application_ref')->nullable();
            $table->json('customer_details')->nullable();
            $table->json('bm_reco')->nullable();
            $table->json('collateral')->nullable();
            $table->json('existing_loans')->nullable();
            $table->json('branch_mis')->nullable();
            $table->string('bank_query')->nullable();
            $table->date('bank_query_due')->nullable();
            $table->decimal('sanction_amount', 15, 2)->nullable();
            $table->date('sanction_date')->nullable();
            $table->string('rejection_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'goal_org_unit_id']);
        });

        Schema::create('goal_disbursements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('goal_loan_case_id')->constrained('goal_loan_cases')->cascadeOnDelete();
            $table->decimal('amount', 15, 2);
            $table->date('disbursed_on');
            $table->string('bank_reference', 100);
            $table->string('evidence_note')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->string('idempotency_key', 80)->nullable()->unique();
            $table->timestamps();
        });

        Schema::create('goal_revenue_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('goal_loan_case_id')->constrained('goal_loan_cases')->cascadeOnDelete();
            $table->foreignId('goal_disbursement_id')->constrained('goal_disbursements')->cascadeOnDelete();
            $table->decimal('amount', 15, 2);
            $table->foreignId('attributed_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('goal_org_unit_id')->nullable()->constrained('goal_org_units')->nullOnDelete();
            $table->string('note')->nullable();
            $table->timestamps();

            $table->unique('goal_disbursement_id');
        });

        Schema::create('goal_pms_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('goal_loan_case_id')->constrained('goal_loan_cases')->cascadeOnDelete();
            $table->foreignId('goal_disbursement_id')->constrained('goal_disbursements')->cascadeOnDelete();
            $table->string('status', 40)->default('Pending'); // Pending | Verified | Follow-up Due | Closed
            $table->text('notes')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
        });

        Schema::create('goal_accounts_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('goal_loan_case_id')->constrained('goal_loan_cases')->cascadeOnDelete();
            $table->foreignId('goal_disbursement_id')->constrained('goal_disbursements')->cascadeOnDelete();
            $table->string('status', 40)->default('Awaiting Report'); // Awaiting Report | Report Entered
            $table->string('report_number')->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('goal_payouts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('goal_loan_case_id')->constrained('goal_loan_cases')->cascadeOnDelete();
            $table->foreignId('goal_disbursement_id')->constrained('goal_disbursements')->cascadeOnDelete();
            $table->decimal('expected_amount', 15, 2)->default(0);
            $table->decimal('received_amount', 15, 2)->default(0);
            $table->string('status', 40)->default('On Hold'); // On Hold | Eligible | Processing | Paid | Exception
            $table->string('exception_reason')->nullable();
            $table->timestamps();

            $table->unique('goal_disbursement_id');
        });

        Schema::create('goal_payout_receipts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('goal_payout_id')->constrained('goal_payouts')->cascadeOnDelete();
            $table->decimal('amount', 15, 2);
            $table->date('received_on');
            $table->string('reference')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('goal_targets', function (Blueprint $table) {
            $table->id();
            $table->string('period', 20); // e.g. 2026-10
            $table->string('metric', 30); // revenue | disbursement
            $table->foreignId('goal_org_unit_id')->nullable()->constrained('goal_org_units')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('base_target', 15, 2)->default(0);
            $table->decimal('opening_shortfall', 15, 2)->default(0);
            $table->decimal('adjustments', 15, 2)->default(0);
            $table->decimal('actual', 15, 2)->default(0);
            $table->decimal('closing_shortfall', 15, 2)->default(0);
            $table->string('status', 20)->default('Draft'); // Draft | Approved
            $table->timestamps();

            $table->index(['period', 'metric']);
        });

        Schema::create('goal_vrm_call_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('goal_loan_lead_id')->nullable()->constrained('goal_loan_leads')->nullOnDelete();
            $table->string('customer_name')->nullable();
            $table->string('mobile', 20)->nullable();
            $table->string('disposition', 40);
            $table->timestamp('called_at');
            $table->timestamp('callback_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'called_at']);
        });

        Schema::create('goal_case_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('goal_loan_case_id')->constrained('goal_loan_cases')->cascadeOnDelete();
            $table->string('title');
            $table->string('task_type', 40)->default('visit'); // visit | followup | document | other
            $table->string('status', 30)->default('Open');
            $table->dateTime('due_at')->nullable();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('goal_case_tasks');
        Schema::dropIfExists('goal_vrm_call_logs');
        Schema::dropIfExists('goal_targets');
        Schema::dropIfExists('goal_payout_receipts');
        Schema::dropIfExists('goal_payouts');
        Schema::dropIfExists('goal_accounts_items');
        Schema::dropIfExists('goal_pms_items');
        Schema::dropIfExists('goal_revenue_entries');
        Schema::dropIfExists('goal_disbursements');
        Schema::dropIfExists('goal_loan_cases');
        Schema::dropIfExists('goal_loan_lead_assignments');
        Schema::dropIfExists('goal_loan_leads');
    }
};

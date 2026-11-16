<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('goal_bank_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('goal_loan_case_id')->constrained('goal_loan_cases')->cascadeOnDelete();
            $table->string('lender_name', 150);
            $table->string('application_ref', 100)->nullable();
            $table->date('login_date')->nullable();
            $table->string('status', 40)->default('Bank Login'); // Bank Login|Bank Query|Processing|Sanctioned|Rejected|Disbursed
            $table->decimal('requested_amount', 15, 2)->nullable();
            $table->date('follow_up_at')->nullable();
            $table->string('banker_name', 150)->nullable();
            $table->string('banker_contact', 30)->nullable();
            $table->string('banker_email', 150)->nullable();
            $table->string('evidence_path')->nullable();
            $table->string('evidence_name')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['goal_loan_case_id', 'status']);
        });

        Schema::create('goal_bank_queries', function (Blueprint $table) {
            $table->id();
            $table->string('query_ref', 40)->unique();
            $table->foreignId('goal_loan_case_id')->constrained('goal_loan_cases')->cascadeOnDelete();
            $table->foreignId('goal_bank_application_id')->constrained('goal_bank_applications')->cascadeOnDelete();
            $table->text('details');
            $table->foreignId('responsible_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('raised_at')->nullable();
            $table->date('due_at')->nullable();
            $table->string('status', 40)->default('Open'); // Open|In Progress|Resolved
            $table->boolean('blocks_processing')->default(true);
            $table->text('resolution_remarks')->nullable();
            $table->string('resolution_evidence_path')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['goal_loan_case_id', 'status']);
        });

        Schema::create('goal_sanctions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('goal_loan_case_id')->constrained('goal_loan_cases')->cascadeOnDelete();
            $table->foreignId('goal_bank_application_id')->constrained('goal_bank_applications')->cascadeOnDelete();
            $table->decimal('sanction_amount', 15, 2);
            $table->date('sanction_date');
            $table->date('validity_until')->nullable();
            $table->string('status', 40)->default('Active'); // Active|Superseded|Cancelled
            $table->unsignedInteger('revision')->default(1);
            $table->text('terms')->nullable();
            $table->string('evidence_path')->nullable();
            $table->string('evidence_name')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('change_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['goal_loan_case_id', 'status']);
        });

        Schema::create('goal_case_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('goal_loan_case_id')->constrained('goal_loan_cases')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('event_type', 40); // created|updated|document|assignment|application|query|sanction|disbursement|status|other
            $table->string('title', 200);
            $table->text('body')->nullable();
            $table->string('from_value', 255)->nullable();
            $table->string('to_value', 255)->nullable();
            $table->string('related_type', 80)->nullable();
            $table->unsignedBigInteger('related_id')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->index(['goal_loan_case_id', 'event_type']);
        });

        Schema::table('goal_disbursements', function (Blueprint $table) {
            if (!Schema::hasColumn('goal_disbursements', 'goal_bank_application_id')) {
                $table->foreignId('goal_bank_application_id')->nullable()->after('goal_loan_case_id')
                    ->constrained('goal_bank_applications')->nullOnDelete();
            }
            if (!Schema::hasColumn('goal_disbursements', 'goal_sanction_id')) {
                $table->foreignId('goal_sanction_id')->nullable()->after('goal_bank_application_id')
                    ->constrained('goal_sanctions')->nullOnDelete();
            }
            if (!Schema::hasColumn('goal_disbursements', 'status')) {
                $table->string('status', 40)->default('Pending Verification')->after('bank_reference');
            }
            if (!Schema::hasColumn('goal_disbursements', 'created_by')) {
                $table->foreignId('created_by')->nullable()->after('evidence_note')
                    ->constrained('users')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('goal_disbursements', function (Blueprint $table) {
            if (Schema::hasColumn('goal_disbursements', 'goal_sanction_id')) {
                $table->dropConstrainedForeignId('goal_sanction_id');
            }
            if (Schema::hasColumn('goal_disbursements', 'goal_bank_application_id')) {
                $table->dropConstrainedForeignId('goal_bank_application_id');
            }
            if (Schema::hasColumn('goal_disbursements', 'created_by')) {
                $table->dropConstrainedForeignId('created_by');
            }
            if (Schema::hasColumn('goal_disbursements', 'status')) {
                $table->dropColumn('status');
            }
        });

        Schema::dropIfExists('goal_case_events');
        Schema::dropIfExists('goal_sanctions');
        Schema::dropIfExists('goal_bank_queries');
        Schema::dropIfExists('goal_bank_applications');
    }
};

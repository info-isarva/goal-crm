<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('goal_customer_parties')) {
            Schema::create('goal_customer_parties', function (Blueprint $table) {
                $table->id();
                $table->string('normalized_mobile', 20)->nullable()->index();
                $table->string('canonical_name')->nullable();
                $table->timestamps();
            });
        }

        Schema::table('goal_loan_leads', function (Blueprint $table) {
            if (! Schema::hasColumn('goal_loan_leads', 'goal_party_id')) {
                $table->foreignId('goal_party_id')->nullable()->after('goal_org_unit_id')
                    ->constrained('goal_customer_parties')->nullOnDelete();
            }
        });

        Schema::table('goal_loan_cases', function (Blueprint $table) {
            if (! Schema::hasColumn('goal_loan_cases', 'goal_party_id')) {
                $table->foreignId('goal_party_id')->nullable()->after('goal_loan_lead_id')
                    ->constrained('goal_customer_parties')->nullOnDelete();
            }
        });

        // REV-03 adjustments are not tied to a single disbursement (MySQL/MariaDB).
        $driver = Schema::getConnection()->getDriverName();
        if (in_array($driver, ['mysql', 'mariadb'], true)
            && Schema::hasColumn('goal_revenue_entries', 'goal_disbursement_id')) {
            try {
                Schema::table('goal_revenue_entries', function (Blueprint $table) {
                    $table->dropForeign(['goal_disbursement_id']);
                });
            } catch (\Throwable) {
                // FK name may differ across environments.
            }
            \Illuminate\Support\Facades\DB::statement(
                'ALTER TABLE goal_revenue_entries MODIFY goal_disbursement_id BIGINT UNSIGNED NULL'
            );
            Schema::table('goal_revenue_entries', function (Blueprint $table) {
                $table->foreign('goal_disbursement_id')
                    ->references('id')->on('goal_disbursements')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::table('goal_loan_cases', function (Blueprint $table) {
            if (Schema::hasColumn('goal_loan_cases', 'goal_party_id')) {
                $table->dropConstrainedForeignId('goal_party_id');
            }
        });
        Schema::table('goal_loan_leads', function (Blueprint $table) {
            if (Schema::hasColumn('goal_loan_leads', 'goal_party_id')) {
                $table->dropConstrainedForeignId('goal_party_id');
            }
        });
        Schema::dropIfExists('goal_customer_parties');
    }
};

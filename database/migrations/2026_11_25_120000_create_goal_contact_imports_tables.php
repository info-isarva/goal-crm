<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('goal_contact_imports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('imported_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('goal_org_unit_id')->nullable()->constrained('goal_org_units')->nullOnDelete();
            $table->string('filename');
            $table->string('source', 20)->default('csv'); // csv|xlsx|xls
            $table->unsignedInteger('total_rows')->default(0);
            $table->unsignedInteger('success_count')->default(0);
            $table->unsignedInteger('failed_count')->default(0);
            $table->string('status', 20)->default('processing'); // processing|ready|partial|failed
            $table->string('default_lead_status', 40)->default('Future Prospect');
            $table->timestamps();
        });

        Schema::create('goal_contact_import_rows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('goal_contact_import_id')->constrained('goal_contact_imports')->cascadeOnDelete();
            $table->unsignedInteger('row_number');
            $table->json('payload')->nullable();
            $table->foreignId('goal_loan_lead_id')->nullable()->constrained('goal_loan_leads')->nullOnDelete();
            $table->string('status', 20)->default('ok'); // ok|error|duplicate
            $table->string('error_message', 500)->nullable();
            $table->timestamps();

            $table->index(['goal_contact_import_id', 'status']);
        });

        Schema::table('goal_loan_leads', function (Blueprint $table) {
            if (! Schema::hasColumn('goal_loan_leads', 'goal_contact_import_id')) {
                $table->foreignId('goal_contact_import_id')
                    ->nullable()
                    ->after('goal_party_id')
                    ->constrained('goal_contact_imports')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('goal_loan_leads', function (Blueprint $table) {
            if (Schema::hasColumn('goal_loan_leads', 'goal_contact_import_id')) {
                $table->dropConstrainedForeignId('goal_contact_import_id');
            }
        });
        Schema::dropIfExists('goal_contact_import_rows');
        Schema::dropIfExists('goal_contact_imports');
    }
};

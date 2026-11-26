<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('goal_contact_imports', function (Blueprint $table) {
            if (! Schema::hasColumn('goal_contact_imports', 'campaign_name')) {
                $table->string('campaign_name')->nullable()->after('filename');
            }
            if (! Schema::hasColumn('goal_contact_imports', 'description')) {
                $table->text('description')->nullable()->after('campaign_name');
            }
            if (! Schema::hasColumn('goal_contact_imports', 'lead_source')) {
                $table->string('lead_source', 80)->nullable()->after('description');
            }
            if (! Schema::hasColumn('goal_contact_imports', 'assign_method')) {
                $table->string('assign_method', 20)->nullable()->after('default_lead_status');
            }
            if (! Schema::hasColumn('goal_contact_imports', 'assigned_label')) {
                $table->string('assigned_label')->nullable()->after('assign_method');
            }
            if (! Schema::hasColumn('goal_contact_imports', 'assigned_at')) {
                $table->timestamp('assigned_at')->nullable()->after('assigned_label');
            }
        });
    }

    public function down(): void
    {
        Schema::table('goal_contact_imports', function (Blueprint $table) {
            foreach (['campaign_name', 'description', 'lead_source', 'assign_method', 'assigned_label', 'assigned_at'] as $col) {
                if (Schema::hasColumn('goal_contact_imports', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};

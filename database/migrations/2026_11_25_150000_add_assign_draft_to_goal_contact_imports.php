<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('goal_contact_imports', function (Blueprint $table) {
            if (! Schema::hasColumn('goal_contact_imports', 'assign_draft')) {
                $table->json('assign_draft')->nullable()->after('default_lead_status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('goal_contact_imports', function (Blueprint $table) {
            if (Schema::hasColumn('goal_contact_imports', 'assign_draft')) {
                $table->dropColumn('assign_draft');
            }
        });
    }
};

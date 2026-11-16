<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('goal_org_unit_id')
                ->nullable()
                ->after('crm_role_type')
                ->constrained('goal_org_units')
                ->nullOnDelete();
            $table->foreignId('reports_to_user_id')
                ->nullable()
                ->after('goal_org_unit_id')
                ->constrained('users')
                ->nullOnDelete();
            $table->string('job_role', 30)->nullable()->after('reports_to_user_id');
            $table->index('job_role');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('goal_org_unit_id');
            $table->dropConstrainedForeignId('reports_to_user_id');
            $table->dropColumn('job_role');
        });
    }
};

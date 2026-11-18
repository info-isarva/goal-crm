<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('goal_case_tasks', function (Blueprint $table) {
            if (! Schema::hasColumn('goal_case_tasks', 'completed_at')) {
                $table->timestamp('completed_at')->nullable()->after('due_at');
            }
            if (! Schema::hasColumn('goal_case_tasks', 'completed_by')) {
                $table->foreignId('completed_by')->nullable()->after('completed_at')
                    ->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('goal_case_tasks', 'outcome')) {
                $table->string('outcome', 255)->nullable()->after('notes');
            }
            if (! Schema::hasColumn('goal_case_tasks', 'location')) {
                $table->string('location', 255)->nullable()->after('outcome');
            }
            if (! Schema::hasColumn('goal_case_tasks', 'escalated_level')) {
                $table->string('escalated_level', 40)->nullable()->after('location');
            }
            if (! Schema::hasColumn('goal_case_tasks', 'escalated_at')) {
                $table->timestamp('escalated_at')->nullable()->after('escalated_level');
            }
        });
    }

    public function down(): void
    {
        Schema::table('goal_case_tasks', function (Blueprint $table) {
            foreach (['completed_by'] as $fk) {
                if (Schema::hasColumn('goal_case_tasks', $fk)) {
                    $table->dropConstrainedForeignId($fk);
                }
            }
            foreach (['completed_at', 'outcome', 'location', 'escalated_level', 'escalated_at'] as $col) {
                if (Schema::hasColumn('goal_case_tasks', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};

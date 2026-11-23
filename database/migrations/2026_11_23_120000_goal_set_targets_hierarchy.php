<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('goal_targets', function (Blueprint $table) {
            if (! Schema::hasColumn('goal_targets', 'owner_target')) {
                $table->decimal('owner_target', 15, 2)->default(0)->after('base_target');
            }
            if (! Schema::hasColumn('goal_targets', 'row_source')) {
                $table->string('row_source', 20)->default('auto')->after('status'); // auto | edited
            }
            if (! Schema::hasColumn('goal_targets', 'set_by')) {
                $table->foreignId('set_by')->nullable()->after('row_source')->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('goal_targets', 'approved_by')) {
                $table->foreignId('approved_by')->nullable()->after('set_by')->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('goal_targets', 'assigned_at')) {
                $table->timestamp('assigned_at')->nullable()->after('approved_by');
            }
            if (! Schema::hasColumn('goal_targets', 'revision_reason')) {
                $table->string('revision_reason', 500)->nullable()->after('assigned_at');
            }
        });

        if (! Schema::hasTable('goal_target_audit_logs')) {
            Schema::create('goal_target_audit_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('goal_target_id')->nullable()->constrained('goal_targets')->nullOnDelete();
                $table->foreignId('employee_id')->constrained('users')->cascadeOnDelete();
                $table->string('period', 20);
                $table->string('metric', 30);
                $table->foreignId('changed_by')->constrained('users')->cascadeOnDelete();
                $table->string('field', 40)->default('base_target');
                $table->decimal('old_value', 15, 2)->nullable();
                $table->decimal('new_value', 15, 2)->nullable();
                $table->string('action', 40)->default('update'); // update | assign | revision_request | auto_split
                $table->json('meta')->nullable();
                $table->timestamps();
                $table->index(['period', 'metric']);
                $table->index(['employee_id', 'period']);
            });
        }

        if (! Schema::hasTable('goal_target_role_weights')) {
            Schema::create('goal_target_role_weights', function (Blueprint $table) {
                $table->id();
                $table->string('job_role', 40)->unique();
                $table->decimal('weight', 8, 4)->default(1);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('goal_target_role_weights');
        Schema::dropIfExists('goal_target_audit_logs');

        Schema::table('goal_targets', function (Blueprint $table) {
            foreach (['revision_reason', 'assigned_at', 'approved_by', 'set_by', 'row_source', 'owner_target'] as $col) {
                if (Schema::hasColumn('goal_targets', $col)) {
                    if (in_array($col, ['approved_by', 'set_by'], true)) {
                        $table->dropConstrainedForeignId($col);
                    } else {
                        $table->dropColumn($col);
                    }
                }
            }
        });
    }
};

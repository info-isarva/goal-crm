<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('goal_scheduled_reports')) {
            Schema::create('goal_scheduled_reports', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('name', 160);
                $table->string('report_type', 40); // overview|leads|processing|disbursement|revenue|performance
                $table->string('frequency', 20); // daily|weekly|monthly|custom
                $table->json('frequency_config')->nullable(); // weekdays, day_of_month, interval_days
                $table->string('run_time', 5)->default('09:00'); // HH:MM 24h
                $table->date('start_date');
                $table->date('end_date')->nullable();
                $table->boolean('no_end_date')->default(false);
                $table->string('format', 10)->default('pdf'); // pdf|xlsx|csv
                $table->json('recipients');
                $table->json('filters')->nullable(); // role-scoped filter snapshot
                $table->string('status', 20)->default('active'); // active|paused|failed
                $table->timestamp('next_run_at')->nullable()->index();
                $table->timestamp('last_run_at')->nullable();
                $table->text('last_error')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->index(['status', 'next_run_at']);
                $table->index(['user_id', 'status']);
            });
        }

        if (! Schema::hasTable('goal_scheduled_report_runs')) {
            Schema::create('goal_scheduled_report_runs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('goal_scheduled_report_id')
                    ->constrained('goal_scheduled_reports')
                    ->cascadeOnDelete();
                $table->string('status', 20); // success|failed
                $table->timestamp('started_at')->nullable();
                $table->timestamp('finished_at')->nullable();
                $table->json('recipients_sent')->nullable();
                $table->string('file_name', 255)->nullable();
                $table->text('error_message')->nullable();
                $table->timestamps();

                $table->index(['goal_scheduled_report_id', 'created_at'], 'gsr_runs_report_created_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('goal_scheduled_report_runs');
        Schema::dropIfExists('goal_scheduled_reports');
    }
};

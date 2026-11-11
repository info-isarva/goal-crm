<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('outreach_types', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('description')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        $now = now();
        $defaults = [
            'Prospect',
            'Data Pre Analysis',
            'LinkedIn',
            'Mail',
            'WhatsApp',
            'Meeting',
        ];
        foreach ($defaults as $i => $name) {
            DB::table('outreach_types')->insert([
                'name' => $name,
                'description' => null,
                'sort_order' => $i + 1,
                'is_active' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        Schema::create('daily_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->date('report_date');
            $table->longText('other_activity')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['user_id', 'report_date']);
        });

        Schema::create('daily_report_outreaches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('daily_report_id')->constrained('daily_reports')->cascadeOnDelete();
            $table->foreignId('outreach_type_id')->constrained('outreach_types')->cascadeOnDelete();
            $table->unsignedInteger('count')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_report_outreaches');
        Schema::dropIfExists('daily_reports');
        Schema::dropIfExists('outreach_types');
    }
};

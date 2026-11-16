<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('goal_loan_lead_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('goal_loan_lead_id')->constrained('goal_loan_leads')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('event_type', 40); // status_change|follow_up|note|visit|allocation|reassign|case|call|other
            $table->string('title');
            $table->text('body')->nullable();
            $table->string('from_status', 40)->nullable();
            $table->string('to_status', 40)->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['goal_loan_lead_id', 'created_at']);
            $table->index(['goal_loan_lead_id', 'event_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('goal_loan_lead_events');
    }
};

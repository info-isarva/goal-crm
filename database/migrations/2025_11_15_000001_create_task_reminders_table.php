<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('task_reminders')) {
            return;
        }
        Schema::create('task_reminders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('task_id');
            $table->unsignedBigInteger('user_id');
            $table->dateTime('remind_at')->index(); // When to send the reminder (30 min before task due_at)
            $table->enum('reminder_type', ['email', 'notification', 'both'])->default('both');
            $table->boolean('email_sent')->default(false);
            $table->boolean('notification_sent')->default(false);
            $table->timestamp('email_sent_at')->nullable();
            $table->timestamp('notification_sent_at')->nullable();
            $table->timestamps();
            
            $table->foreign('task_id')->references('id')->on('tasks')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->index(['remind_at', 'email_sent', 'notification_sent']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('task_reminders');
    }
};

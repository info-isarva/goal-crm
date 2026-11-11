<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quotation_send_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quotation_id')->constrained('quotations')->cascadeOnDelete();
            $table->string('channel', 20); // whatsapp | email
            $table->string('status', 20)->default('sent'); // sent | failed
            $table->string('recipient', 500)->nullable();
            $table->string('cc', 500)->nullable();
            $table->string('bcc', 500)->nullable();
            $table->string('subject', 255)->nullable();
            $table->string('from_address', 255)->nullable();
            $table->foreignId('sent_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('message_preview')->nullable();
            $table->text('error_message')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['quotation_id', 'channel']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quotation_send_logs');
    }
};

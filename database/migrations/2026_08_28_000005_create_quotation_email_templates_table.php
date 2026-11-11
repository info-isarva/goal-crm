<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quotation_email_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('description')->nullable();
            $table->string('subject');
            $table->longText('body');
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        $defaultBody = '<p>Dear {contact_name},</p>'
            . '<p>Greetings from <strong>{company_name}</strong>.</p>'
            . '<p>Please find attached Quotation No. <strong>{quote_no}</strong> for <strong>{client_name}</strong>. '
            . 'Total amount: <strong>{amount}</strong>.</p>'
            . '<p>Kindly review and share your confirmation at your earliest convenience.</p>'
            . '<p>Best regards,<br>{sender_name}</p>';

        DB::table('quotation_email_templates')->insert([
            'name'        => 'Standard Quotation Email',
            'description' => 'Default professional quotation email with PDF attachment.',
            'subject'     => 'Quotation — {quote_no}',
            'body'        => $defaultBody,
            'is_default'  => true,
            'is_active'   => true,
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('quotation_email_templates');
    }
};

<?php

use App\Models\QuotationEmailTemplate;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (!\Illuminate\Support\Facades\Schema::hasTable('quotation_email_templates')) {
            return;
        }

        $default = DB::table('quotation_email_templates')
            ->where('is_default', true)
            ->orderBy('id')
            ->first();

        if (!$default) {
            return;
        }

        $plainBody = '<p>Dear {contact_name},</p>'
            . '<p>Greetings from <strong>{company_name}</strong>.</p>'
            . '<p>Please find attached Quotation No. <strong>{quote_no}</strong> for <strong>{client_name}</strong>. '
            . 'Total amount: <strong>{amount}</strong>.</p>'
            . '<p>Kindly review and share your confirmation at your earliest convenience.</p>'
            . '<p>Best regards,<br>{sender_name}</p>';

        if (trim((string) $default->body) !== trim($plainBody)) {
            return;
        }

        DB::table('quotation_email_templates')
            ->where('id', $default->id)
            ->update([
                'name' => 'Standard Quotation Email',
                'description' => 'Professional branded email with quotation summary box.',
                'subject' => 'Quotation {quote_no} — {client_name}',
                'body' => QuotationEmailTemplate::professionalCardBody(),
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        // No rollback — template content is user-managed.
    }
};

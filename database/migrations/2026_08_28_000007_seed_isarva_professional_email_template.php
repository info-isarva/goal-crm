<?php

use App\Models\QuotationEmailTemplate;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('quotation_email_templates')) {
            return;
        }

        $exists = DB::table('quotation_email_templates')
            ->where('name', 'Isarva Professional')
            ->exists();

        if ($exists) {
            return;
        }

        DB::table('quotation_email_templates')->insert([
            'name'        => 'Isarva Professional',
            'description' => 'Branded professional layout with Isarva / company logo header and green accent styling.',
            'subject'     => 'Quotation {quote_no} from {company_name}',
            'body'        => QuotationEmailTemplate::isarvaProfessionalBody(),
            'is_default'  => false,
            'is_active'   => true,
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);
    }

    public function down(): void
    {
        if (!Schema::hasTable('quotation_email_templates')) {
            return;
        }

        DB::table('quotation_email_templates')
            ->where('name', 'Isarva Professional')
            ->delete();
    }
};

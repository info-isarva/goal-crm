<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WhatsappSetting extends Model
{
    use HasFactory;

    protected $table = 'whatsapp_settings';

    protected $fillable = [
        'provider_type',
        'whatsapp_access_token',
        'whatsapp_phone_number_id',
        'whatsapp_api_url',
        'whatsapp_api_version',
        'whatsapp_template_id',
        'quotation_template_id',
        'quotation_language_code',
        'quotation_message_format',
        'whatsapp_language_code',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Retrieve settings record for active provider or specific provider type.
     *
     * @param string|null $providerType 'isarva' or 'other'
     * @return static
     */
    public static function getSettings(?string $providerType = null): static
    {
        if ($providerType) {
            $setting = static::where('provider_type', $providerType)->first();
        } else {
            // Get active record first, or fallback to first record
            $setting = static::where('is_active', true)->first() ?: static::first();
        }

        if (!$setting) {
            $type = $providerType ?: config('services.whatsapp.provider_type', 'isarva');
            $setting = new static([
                'provider_type'            => $type,
                'whatsapp_access_token'    => config('services.whatsapp.access_token'),
                'whatsapp_phone_number_id' => config('services.whatsapp.phone_number_id'),
                'whatsapp_api_url'         => config('services.whatsapp.api_url'),
                'whatsapp_api_version'     => config('services.whatsapp.api_version'),
                'whatsapp_template_id'     => config('services.whatsapp.template_id'),
                'quotation_template_id'    => config('services.whatsapp.quotation_template_id'),
                'quotation_language_code'  => config('services.whatsapp.quotation_language_code', config('services.whatsapp.language_code')),
                'whatsapp_language_code'   => config('services.whatsapp.language_code'),
                'is_active'                => false,
            ]);
        }

        return $setting;
    }

    /**
     * Quotation template name for the active/current provider record.
     */
    public function getQuotationTemplateNameAttribute(): ?string
    {
        return $this->quotation_template_id ?: null;
    }

    /**
     * Default preview/body mapping for quotation WhatsApp templates.
     */
    public static function defaultQuotationMessageFormat(): string
    {
        return 'Dear {contact_name}, Greetings from {company_name}. Please find attached Quotation No. {quote_no} for {client_name}. Total Amount: {amount}. Kindly review and share your confirmation. Best regards, {sender_name} + Quotation PDF.';
    }

    /**
     * Placeholder labels shown in settings UI.
     *
     * @return array<string, string>
     */
    public static function quotationMessagePlaceholders(): array
    {
        return [
            '{contact_name}' => 'Contact / recipient name (body {{1}})',
            '{company_name}' => 'Your company name (body {{2}})',
            '{quote_no}'     => 'Quotation number (body {{3}})',
            '{client_name}'  => 'Client / organization name (body {{4}})',
            '{amount}'       => 'Grand total amount (body {{5}})',
            '{sender_name}'  => 'Quotation owner / sender (body {{6}})',
        ];
    }

    public function getQuotationMessageFormatAttribute(?string $value): string
    {
        return trim((string) ($value ?: '')) ?: static::defaultQuotationMessageFormat();
    }

    /**
     * Render the configured message format with quotation values.
     *
     * @param  array<string, string>  $variables
     */
    public static function renderQuotationMessageFormat(string $format, array $variables): string
    {
        $aliases = [
            '{contact_name}' => $variables['contact_name'] ?? '',
            '{company_name}' => $variables['company_name'] ?? '',
            '{quote_no}'     => $variables['quote_no'] ?? '',
            '{client_name}'  => $variables['client_name'] ?? '',
            '{amount}'       => $variables['amount'] ?? '',
            '{sender_name}'  => $variables['sender_name'] ?? '',
            '[Name]'         => $variables['contact_name'] ?? '',
            '[Company]'      => $variables['company_name'] ?? '',
            '[Quote]'        => $variables['quote_no'] ?? '',
            '[Client]'       => $variables['client_name'] ?? '',
            '[Amount]'       => $variables['amount'] ?? '',
            '[Sender]'       => $variables['sender_name'] ?? '',
            '{{1}}'          => $variables['contact_name'] ?? '',
            '{{2}}'          => $variables['company_name'] ?? '',
            '{{3}}'          => $variables['quote_no'] ?? '',
            '{{4}}'          => $variables['client_name'] ?? '',
            '{{5}}'          => $variables['amount'] ?? '',
            '{{6}}'          => $variables['sender_name'] ?? '',
        ];

        return str_replace(array_keys($aliases), array_values($aliases), $format);
    }

    /**
     * Build WhatsApp body variables for a quotation record.
     *
     * @return array{variables: array<string, string>, body_params: array<int, string>}
     */
    public static function quotationMessageVariables($quotation, $company = null, $user = null): array
    {
        $company = $company ?: Company::first();
        $user = $user ?: auth()->user();

        $contactName = trim(optional($quotation->people)->full_name ?? optional($quotation->people)->first_name ?? 'Customer');
        $companyName = $company->name ?? config('app.name', 'CRM');
        $quoteNo = $quotation->quote_no ?? ('Q-' . $quotation->id);
        $clientName = optional($quotation->organization)->name ?? $contactName;
        $amount = format_email_money(
            $quotation->grand_total ?? 0,
            $quotation->currency_symbol ?? null,
            $quotation->currency_code ?? null
        );
        $senderName = optional($quotation->owner)->name
            ?? optional($user)->name
            ?? $companyName;

        $variables = [
            'contact_name' => $contactName,
            'company_name' => $companyName,
            'quote_no'     => $quoteNo,
            'client_name'  => $clientName,
            'amount'       => $amount,
            'sender_name'  => $senderName,
        ];

        return [
            'variables'   => $variables,
            'body_params' => array_values($variables),
        ];
    }

    /**
     * Language code used when sending quotation templates.
     */
    public function getQuotationLanguageAttribute(): string
    {
        return $this->quotation_language_code
            ?: ($this->whatsapp_language_code ?? 'en_US');
    }
}

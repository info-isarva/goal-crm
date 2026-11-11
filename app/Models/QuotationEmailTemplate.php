<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuotationEmailTemplate extends Model
{
    public const EMAIL_LOGO_CID = 'company-logo@isarva';

    public const EMAIL_LOGO_CID_SRC = 'cid:company-logo@isarva';

    protected $fillable = [
        'name',
        'description',
        'subject',
        'body',
        'is_default',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * @return array<string, string>
     */
    public static function placeholders(): array
    {
        return [
            '{contact_name}' => 'Contact / recipient name',
            '{company_name}' => 'Your company name',
            '{quote_no}'     => 'Quotation number',
            '{client_name}'  => 'Client / organization name',
            '{amount}'       => 'Grand total with currency symbol (from quotation country/currency)',
            '{currency_symbol}' => 'Currency symbol (₹, $, €, …)',
            '{currency_code}' => 'Currency code (INR, USD, EUR, …)',
            '{sender_name}'  => 'Quotation owner / sender',
            '{subject}'      => 'Quotation subject line',
            '{quote_date}'   => 'Quotation created date',
            '{valid_until}'  => 'Quotation valid until date',
            '{company_logo}' => 'Company / Isarva logo image (HTML)',
            '{company_logo_url}' => 'Logo image URL only',
            '{email_signature}' => 'Your Email Configuration signature (auto-filled)',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function sampleVariables(): array
    {
        $company = Company::first();
        $symbol = trim((string) ($company->currency_symbol ?? '')) ?: '₹';

        return [
            'contact_name' => 'John Doe',
            'company_name' => 'ISARVA INFOTECH PRIVATE LIMITED',
            'quote_no'     => 'Q-123456',
            'client_name'  => 'SRJ Piping',
            'amount'       => format_smart_money(76000, $symbol),
            'currency_symbol' => $symbol,
            'currency_code' => (string) ($company->currency_code ?? 'INR'),
            'sender_name'  => 'Abhishek A',
            'subject'      => 'Industrial Piping Supply',
            'quote_date'   => now()->format('d M Y'),
            'valid_until'  => now()->addDays(30)->format('d M Y'),
            'company_logo_url' => static::resolveCompanyLogoUrl($company),
            'company_logo' => static::companyLogoImgHtml($company, 52),
        ];
    }

    /**
     * @return array<string, array{id: string, name: string, description: string, subject: string, body: string}>
     */
    public static function designPresets(): array
    {
        return [
            'standard' => [
                'id' => 'standard',
                'name' => 'Standard Quotation Email',
                'description' => 'Blue header card with quotation summary — matches standard send design.',
                'subject' => 'Quotation {quote_no} — {client_name}',
                'body' => static::professionalCardBody(),
            ],
            'professional' => [
                'id' => 'professional',
                'name' => 'Professional Card',
                'description' => 'Same blue branded card as Standard Quotation Email.',
                'subject' => 'Quotation {quote_no} — {client_name}',
                'body' => static::professionalCardBody(),
            ],
            'minimal' => [
                'id' => 'minimal',
                'name' => 'Minimal',
                'description' => 'Clean layout with subtle borders.',
                'subject' => 'Your quotation {quote_no} from {company_name}',
                'body' => static::minimalBody(),
            ],
            'corporate' => [
                'id' => 'corporate',
                'name' => 'Corporate',
                'description' => 'Formal business letter style.',
                'subject' => 'Quotation Ref: {quote_no} | {subject}',
                'body' => static::corporateBody(),
            ],
            'isarva_professional' => [
                'id' => 'isarva_professional',
                'name' => 'Isarva Professional',
                'description' => 'Logo header, green accents, quotation details card.',
                'subject' => 'Quotation {quote_no} from {company_name}',
                'body' => static::isarvaProfessionalBody(),
            ],
        ];
    }

    public static function resolveCompanyLogoPath(?Company $company = null): ?string
    {
        $company = $company ?? Company::first();

        if ($company && !empty($company->logo)) {
            $relative = ltrim((string) $company->logo, '/');
            $path = public_path('assets/company_image/' . $relative);
            if (is_file($path)) {
                return $path;
            }
        }

        foreach (static::defaultEmailLogoPaths() as $path) {
            if (is_file($path)) {
                return $path;
            }
        }

        return null;
    }

    /**
     * @return array<int, string>
     */
    protected static function defaultEmailLogoPaths(): array
    {
        return [
            public_path('images/isarva-logo-email.png'),
            public_path('images/isarva-logo-email.jpg'),
            public_path('assets/company_image/company/logo_1772377694.png'),
        ];
    }

    public static function logoDataUriFromPath(?string $path): ?string
    {
        if (!$path || !is_file($path)) {
            return null;
        }

        $ext = strtolower((string) pathinfo($path, PATHINFO_EXTENSION));
        $mime = match ($ext) {
            'png'  => 'image/png',
            'jpg', 'jpeg' => 'image/jpeg',
            'gif'  => 'image/gif',
            'webp' => 'image/webp',
            default => null,
        };

        if ($mime === null) {
            return null;
        }

        $binary = @file_get_contents($path);
        if ($binary === false || $binary === '') {
            return null;
        }

        return 'data:' . $mime . ';base64,' . base64_encode($binary);
    }

    public static function resolveCompanyLogoUrl(?Company $company = null): string
    {
        $path = static::resolveCompanyLogoPath($company);
        if ($path) {
            $relative = ltrim(str_replace('\\', '/', str_replace(public_path(), '', $path)), '/');

            return rtrim((string) config('app.url'), '/') . '/' . $relative;
        }

        return rtrim((string) config('app.url'), '/') . '/images/isarva-logo-email.jpg';
    }

    public static function resolveCompanyLogoMime(?string $path = null): ?string
    {
        $path = $path ?? static::resolveCompanyLogoPath();
        if (!$path || !is_file($path)) {
            return null;
        }

        $ext = strtolower((string) pathinfo($path, PATHINFO_EXTENSION));

        return match ($ext) {
            'png'  => 'image/png',
            'jpg', 'jpeg' => 'image/jpeg',
            'gif'  => 'image/gif',
            'webp' => 'image/webp',
            default => null,
        };
    }

    public static function companyLogoImgHtml(?Company $company = null, int $height = 52, bool $forEmailSend = false): string
    {
        $company = $company ?? Company::first();
        $alt = htmlspecialchars((string) ($company->name ?? 'Isarva'), ENT_QUOTES, 'UTF-8');
        $style = 'height:' . $height . 'px;max-width:200px;width:auto;display:block;margin:0 auto;border:0;outline:none;';

        if (!static::resolveCompanyLogoPath($company)) {
            return '<div style="font-size:18px;font-weight:700;color:#1D874C;text-align:center;">' . $alt . '</div>';
        }

        if ($forEmailSend) {
            $src = static::EMAIL_LOGO_CID_SRC;
        } else {
            $src = static::logoDataUriFromPath(static::resolveCompanyLogoPath($company))
                ?? static::resolveCompanyLogoUrl($company);
        }

        return '<img src="' . htmlspecialchars((string) $src, ENT_QUOTES, 'UTF-8') . '" alt="' . $alt . '" height="' . $height . '" style="' . $style . '">';
    }

    /**
     * Point logo images in the HTML to the inline CID used by QuotationSentMail.
     */
    public static function embedEmailLogos(string $html, ?Company $company = null): string
    {
        if (!static::resolveCompanyLogoPath($company) || trim($html) === '') {
            return $html;
        }

        $cid = static::EMAIL_LOGO_CID_SRC;
        $company = $company ?? Company::first();
        $companyName = preg_quote((string) ($company->name ?? ''), '/');

        $html = preg_replace_callback(
            '/<img\b[^>]*>/i',
            function (array $matches) use ($cid, $companyName) {
                $tag = $matches[0];

                $isLogo = (bool) preg_match(
                    '/\b(logo|company_image|logoisarva|isarva-logo-email|data:image\/|cid:)/i',
                    $tag
                );

                if (!$isLogo && $companyName !== '') {
                    $isLogo = (bool) preg_match('/\balt=(["\'])' . $companyName . '\1/i', $tag);
                }

                if (!$isLogo) {
                    return $tag;
                }

                $updated = preg_replace('/\bsrc=(["\'])(.*?)\1/i', 'src=$1' . $cid . '$1', $tag, 1);

                return is_string($updated) ? $updated : $tag;
            },
            $html
        ) ?? $html;

        if (!str_contains($html, static::EMAIL_LOGO_CID_SRC)) {
            $html = preg_replace(
                '/<img\b[^>]*>/i',
                static::companyLogoImgHtml($company, 52, true),
                $html,
                1
            ) ?? $html;
        }

        return $html;
    }

    public static function isarvaProfessionalBody(): string
    {
        // Quill-safe structure (p/strong) that still matches the Isarva Professional screenshot
        return '<div style="font-family:Arial,Helvetica,sans-serif;max-width:600px;margin:0 auto;background:#ffffff;border:1px solid #e5e7eb;border-radius:10px;overflow:hidden;">'
            . '<div style="padding:28px 32px 20px;text-align:center;background:#ffffff;border-bottom:3px solid #4FA64C;">'
            . '{company_logo}'
            . '</div>'
            . '<p style="margin:0;padding:12px 24px;background:#eef8f0;text-align:center;'
            . 'font-size:12px;font-weight:700;color:#1D874C;letter-spacing:1.4px;text-transform:uppercase;">'
            . 'Quotation &mdash; {quote_no}</p>'
            . '<div style="padding:28px 32px 24px;color:#334155;font-size:14px;line-height:1.65;background:#ffffff;">'
            . '<p style="margin:0 0 14px;">Dear <strong>{contact_name}</strong>,</p>'
            . '<p style="margin:0 0 20px;">Thank you for your interest in <strong>{company_name}</strong>. '
            . 'We are pleased to share quotation <strong>{quote_no}</strong> prepared for <strong>{client_name}</strong>.</p>'
            . '<div style="border:1px solid #d7eadc;border-radius:8px;margin:0 0 22px;overflow:hidden;background:#ffffff;">'
            . '<p style="margin:0;padding:11px 18px;background:#4FA64C;color:#ffffff;font-size:12px;'
            . 'font-weight:700;letter-spacing:.8px;text-transform:uppercase;">Quotation Details</p>'
            . '<div style="padding:16px 18px;background:#fbfffc;">'
            . '<p style="margin:0 0 10px;"><strong style="color:#64748b;">Quote Number:&nbsp;</strong>{quote_no}</p>'
            . '<p style="margin:0 0 10px;"><strong style="color:#64748b;">Client:&nbsp;</strong>{client_name}</p>'
            . '<p style="margin:0 0 10px;"><strong style="color:#64748b;">Subject:&nbsp;</strong>{subject}</p>'
            . '<p style="margin:0 0 10px;"><strong style="color:#64748b;">Quote Date:&nbsp;</strong>{quote_date}</p>'
            . '<p style="margin:0 0 10px;"><strong style="color:#64748b;">Valid Until:&nbsp;</strong>{valid_until}</p>'
            . '<p style="margin:12px 0 0;padding-top:12px;border-top:1px solid #e2efe6;">'
            . '<strong style="color:#64748b;">Total Amount:&nbsp;</strong>'
            . '<strong style="font-size:16px;color:#1D874C;">{amount}</strong></p>'
            . '</div></div>'
            . '<p style="margin:0 0 18px;">The detailed quotation is attached as a PDF. Kindly review and let us know if you need any clarification.</p>'
            . '<p style="margin:0;">Warm regards,<br><strong>{sender_name}</strong><br>'
            . '<span style="color:#64748b;">{company_name}</span></p>'
            . '</div>'
            . '<div style="margin:0;padding:16px 32px;background:#1D874C;color:#ffffff;text-align:center;'
            . 'font-size:12px;line-height:1.55;width:100%;box-sizing:border-box;">'
            . '<strong style="color:#ffffff;">{company_name}</strong><br>'
            . '<span style="opacity:.92;font-size:11px;color:#ffffff;">Powered by Isarva CRM</span></div>'
            . '</div>';
    }

    public static function professionalCardBody(): string
    {
        // Quill-safe Standard / Professional Card (blue header + summary box)
        return '<div style="font-family:Arial,Helvetica,sans-serif;max-width:600px;margin:0 auto;background:#ffffff;border:1px solid #e5e7eb;border-radius:8px;overflow:hidden;">'
            . '<p style="margin:0;padding:22px 28px;background:#2563eb;color:#ffffff;">'
            . '<strong style="font-size:20px;display:block;line-height:1.3;">{company_name}</strong>'
            . '<span style="font-size:13px;opacity:.95;">Quotation &mdash; {quote_no}</span></p>'
            . '<div style="padding:28px;color:#334155;font-size:14px;line-height:1.65;background:#ffffff;">'
            . '<p style="margin:0 0 14px;">Dear <strong>{contact_name}</strong>,</p>'
            . '<p style="margin:0 0 18px;">Greetings from <strong>{company_name}</strong>. Please find attached your quotation for review.</p>'
            . '<div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;margin:0 0 20px;padding:18px 18px 14px;">'
            . '<p style="margin:0 0 12px;font-size:11px;color:#64748b;text-transform:uppercase;letter-spacing:.7px;"><strong>Quotation Summary</strong></p>'
            . '<p style="margin:0 0 8px;"><strong>Quote No:&nbsp;</strong>{quote_no}</p>'
            . '<p style="margin:0 0 8px;"><strong>Client:&nbsp;</strong>{client_name}</p>'
            . '<p style="margin:0 0 8px;"><strong>Subject:&nbsp;</strong>{subject}</p>'
            . '<p style="margin:0 0 8px;"><strong>Amount:&nbsp;</strong><strong style="color:#2563eb;">{amount}</strong></p>'
            . '<p style="margin:0;"><strong>Valid Until:&nbsp;</strong>{valid_until}</p>'
            . '</div>'
            . '<p style="margin:0;">Kindly review the attached PDF and share your confirmation at your earliest convenience.</p>'
            . '</div>'
            . '<div style="margin:0;padding:14px 28px;background:#f8fafc;font-size:11px;color:#94a3b8;text-align:center;border-top:1px solid #e2e8f0;width:100%;box-sizing:border-box;">'
            . 'Sent by {company_name} &bull; Quotation dated {quote_date}</div>'
            . '</div>';
    }

    protected static function emailDetailRow(string $label, string $valueHtml): string
    {
        return '<p style="margin:0 0 10px;line-height:1.5;">'
            . '<strong style="color:#64748b;">' . $label . ':&nbsp;</strong>'
            . $valueHtml
            . '</p>';
    }

    /**
     * Ensure outgoing HTML is a UTF-8 email document with a centered width shell
     * so footers stay inside the card (Gmail often stretches bare block backgrounds).
     */
    public static function wrapOutgoingEmailHtml(string $html): string
    {
        $html = trim($html);
        if ($html === '') {
            return $html;
        }

        // Repair Arabic / RTL currency glyphs already baked into the body
        $html = static::normalizeEmailCurrencyGlyphs($html);

        if (stripos($html, '<html') !== false) {
            if (!preg_match('/<meta[^>]+charset=/i', $html)) {
                $html = preg_replace(
                    '/<head([^>]*)>/i',
                    '<head$1><meta http-equiv="Content-Type" content="text/html; charset=UTF-8">',
                    $html,
                    1
                ) ?? $html;
            }

            return $html;
        }

        return '<!DOCTYPE html><html><head>'
            . '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">'
            . '<meta charset="UTF-8">'
            . '<meta name="viewport" content="width=device-width, initial-scale=1.0">'
            . '</head><body style="margin:0;padding:0;background:#f1f5f9;">'
            . '<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" '
            . 'style="border-collapse:collapse;background:#f1f5f9;width:100%;">'
            . '<tr><td align="center" style="padding:20px 12px;">'
            . '<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="600" '
            . 'style="border-collapse:collapse;width:100%;max-width:600px;">'
            . '<tr><td style="text-align:left;font-family:Arial,Helvetica,sans-serif;">'
            . $html
            . '</td></tr></table></td></tr></table></body></html>';
    }

    /**
     * Replace Arabic currency glyphs that email clients often corrupt.
     */
    public static function normalizeEmailCurrencyGlyphs(string $html): string
    {
        $replacements = [
            'د.إ' => 'AED ',
            'د٫إ' => 'AED ',
            'ريال' => 'SAR ',
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $html);
    }

    /**
     * Push screenshot-matched designs into existing named templates in the database.
     */
    public static function syncScreenshotDesignTemplates(): int
    {
        $map = [
            'standard quotation email' => 'standard',
            'standard' => 'standard',
            'professional card' => 'professional',
            'isarva professional' => 'isarva_professional',
        ];

        $presets = static::designPresets();
        $updated = 0;

        foreach (static::query()->get() as $template) {
            $key = strtolower(trim((string) $template->name));
            $presetId = $map[$key] ?? null;
            if (!$presetId || empty($presets[$presetId])) {
                continue;
            }
            $preset = $presets[$presetId];
            $template->update([
                'subject' => $preset['subject'],
                'body' => $preset['body'],
                'description' => $preset['description'],
            ]);
            $updated++;
        }

        return $updated;
    }

    public static function minimalBody(): string
    {
        return '<div style="font-family:Georgia,Times New Roman,serif;max-width:560px;margin:0 auto;border:1px solid #e5e7eb;border-radius:4px;padding:32px 36px;color:#1f2937;font-size:15px;line-height:1.7;background:#ffffff;">'
            . '<p style="margin:0 0 20px;font-size:13px;color:#6b7280;letter-spacing:1px;text-transform:uppercase;">Quotation {quote_no}</p>'
            . '<p style="margin:0 0 20px;">Dear {contact_name},</p>'
            . '<p style="margin:0 0 20px;">Please find attached quotation <strong>{quote_no}</strong> for <em>{client_name}</em>. '
            . 'The total amount is <strong>{amount}</strong>, valid until <strong>{valid_until}</strong>.</p>'
            . '<p style="margin:0 0 28px;">We look forward to your response.</p>'
            . '<p style="margin:0;color:#374151;">{sender_name}<br>'
            . '<span style="font-size:13px;color:#6b7280;">{company_name}</span></p>'
            . '</div>';
    }

    public static function corporateBody(): string
    {
        return '<div style="font-family:Arial,Helvetica,sans-serif;max-width:600px;margin:0 auto;border-top:4px solid #1e40af;background:#ffffff;color:#111827;font-size:14px;line-height:1.65;">'
            . '<div style="padding:24px 0 8px;border-bottom:2px solid #1e40af;">'
            . '<div style="font-size:18px;font-weight:bold;color:#1e40af;">{company_name}</div>'
            . '</div>'
            . '<div style="padding:24px 0;">'
            . '<p style="margin:0 0 16px;"><strong>Date:</strong> {quote_date}</p>'
            . '<p style="margin:0 0 16px;"><strong>To:</strong> {contact_name}<br><strong>Organization:</strong> {client_name}</p>'
            . '<p style="margin:0 0 16px;"><strong>Subject:</strong> Quotation — {quote_no} ({subject})</p>'
            . '<p style="margin:0 0 16px;">Dear Sir/Madam,</p>'
            . '<p style="margin:0 0 16px;">With reference to the above subject, we are pleased to submit our quotation amounting to '
            . '<strong>{amount}</strong>. The detailed quotation is attached as a PDF document for your perusal.</p>'
            . '<p style="margin:0 0 16px;">This quotation remains valid until <strong>{valid_until}</strong>. We request you to review and confirm at your earliest convenience.</p>'
            . '<p style="margin:0 0 8px;">Thank you for your consideration.</p>'
            . '<p style="margin:24px 0 0;">Yours faithfully,<br><strong>{sender_name}</strong><br>{company_name}</p>'
            . '</div>'
            . '</div>';
    }

    /**
     * Repair Quill-mangled email HTML where nested tables become many 1-character columns,
     * and convert presentation tables to divs so Quill composers keep a proper layout.
     */
    public static function sanitizeEmailBodyHtml(string $html): string
    {
        $html = trim($html);
        if ($html === '') {
            return $html;
        }

        if (!class_exists(\DOMDocument::class)) {
            return $html;
        }

        if (preg_match('/<table[\s>]/i', $html)) {
            $html = static::repairBrokenQuillTables($html);
            $html = static::convertPresentationTablesToDivs($html);
        }

        return $html;
    }

    protected static function repairBrokenQuillTables(string $html): string
    {
        $previous = libxml_use_internal_errors(true);
        $dom = new \DOMDocument('1.0', 'UTF-8');
        $wrapped = '<?xml encoding="UTF-8"><div id="qet-root">' . $html . '</div>';
        $loaded = $dom->loadHTML($wrapped, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if (!$loaded) {
            return $html;
        }

        $xpath = new \DOMXPath($dom);
        $tables = $xpath->query('//table');
        if (!$tables || $tables->length === 0) {
            return $html;
        }

        $changed = false;
        /** @var \DOMElement $table */
        foreach (iterator_to_array($tables) as $table) {
            $rows = $table->getElementsByTagName('tr');
            $maxCols = 0;
            $cellTexts = [];
            foreach ($rows as $row) {
                $cells = [];
                foreach ($row->childNodes as $child) {
                    if ($child instanceof \DOMElement && in_array(strtolower($child->tagName), ['td', 'th'], true)) {
                        $text = trim(preg_replace('/\s+/u', ' ', $child->textContent ?? '') ?? '');
                        $cells[] = $text;
                    }
                }
                $maxCols = max($maxCols, count($cells));
                $cellTexts[] = $cells;
            }

            $flat = [];
            foreach ($cellTexts as $cells) {
                foreach ($cells as $text) {
                    if ($text !== '') {
                        $flat[] = $text;
                    }
                }
            }

            $avgLen = 0;
            if (count($flat) > 0) {
                $avgLen = array_sum(array_map('mb_strlen', $flat)) / count($flat);
            }

            if (!($maxCols >= 8 && $avgLen <= 4)) {
                continue;
            }

            $paragraphs = [];
            $buffer = '';
            foreach ($flat as $chunk) {
                if (mb_strlen($chunk) <= 2) {
                    $buffer .= $chunk;
                    continue;
                }
                if ($buffer !== '') {
                    $paragraphs[] = $buffer;
                    $buffer = '';
                }
                $paragraphs[] = $chunk;
            }
            if ($buffer !== '') {
                $paragraphs[] = $buffer;
            }

            $replacement = $dom->createElement('div');
            $replacement->setAttribute('style', 'font-family:Arial,Helvetica,sans-serif;font-size:14px;line-height:1.6;color:#334155;');
            foreach ($paragraphs as $para) {
                $p = $dom->createElement('p');
                $p->setAttribute('style', 'margin:0 0 12px;');
                $p->appendChild($dom->createTextNode($para));
                $replacement->appendChild($p);
            }

            if ($table->parentNode) {
                $table->parentNode->replaceChild($replacement, $table);
                $changed = true;
            }
        }

        if (!$changed) {
            return $html;
        }

        return static::domRootInnerHtml($dom) ?: $html;
    }

    /**
     * Convert email presentation tables into nested divs (Quill-safe).
     */
    protected static function convertPresentationTablesToDivs(string $html): string
    {
        if (!preg_match('/<table[\s>]/i', $html)) {
            return $html;
        }

        $previous = libxml_use_internal_errors(true);
        $dom = new \DOMDocument('1.0', 'UTF-8');
        $wrapped = '<?xml encoding="UTF-8"><div id="qet-root">' . $html . '</div>';
        $loaded = $dom->loadHTML($wrapped, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if (!$loaded) {
            return $html;
        }

        $guard = 0;
        while ($guard < 40) {
            $tables = $dom->getElementsByTagName('table');
            if (!$tables || $tables->length === 0) {
                break;
            }

            // Convert deepest tables first
            $table = null;
            foreach ($tables as $candidate) {
                if ($candidate->getElementsByTagName('table')->length === 0) {
                    $table = $candidate;
                }
            }
            if (!$table) {
                $table = $tables->item($tables->length - 1);
            }
            if (!$table || !$table->parentNode) {
                break;
            }

            $wrapper = $dom->createElement('div');
            $tableStyle = trim((string) $table->getAttribute('style'));
            $width = $table->getAttribute('width');
            $styles = [];
            if ($width !== '') {
                $styles[] = (str_contains($width, '%') ? 'width:' . $width : 'width:' . $width . 'px');
            }
            if ($tableStyle !== '') {
                $styles[] = rtrim($tableStyle, ';');
            }
            if ($styles) {
                $wrapper->setAttribute('style', implode(';', $styles) . ';');
            }

            foreach (iterator_to_array($table->childNodes) as $section) {
                if (!($section instanceof \DOMElement)) {
                    continue;
                }
                $tag = strtolower($section->tagName);
                $rowNodes = [];
                if ($tag === 'tr') {
                    $rowNodes = [$section];
                } elseif (in_array($tag, ['tbody', 'thead', 'tfoot'], true)) {
                    foreach ($section->childNodes as $child) {
                        if ($child instanceof \DOMElement && strtolower($child->tagName) === 'tr') {
                            $rowNodes[] = $child;
                        }
                    }
                } else {
                    continue;
                }

                foreach ($rowNodes as $row) {
                    $rowDiv = $dom->createElement('div');
                    $rowStyle = trim((string) $row->getAttribute('style'));
                    if ($rowStyle !== '') {
                        $rowDiv->setAttribute('style', rtrim($rowStyle, ';') . ';');
                    }
                    foreach (iterator_to_array($row->childNodes) as $cell) {
                        if (!($cell instanceof \DOMElement)) {
                            continue;
                        }
                        if (!in_array(strtolower($cell->tagName), ['td', 'th'], true)) {
                            continue;
                        }
                        $cellDiv = $dom->createElement('div');
                        $cellStyle = trim((string) $cell->getAttribute('style'));
                        $align = $cell->getAttribute('align');
                        $cellStyles = [];
                        if ($align !== '') {
                            $cellStyles[] = 'text-align:' . $align;
                        }
                        if ($cellStyle !== '') {
                            $cellStyles[] = rtrim($cellStyle, ';');
                        }
                        if ($cellStyles) {
                            $cellDiv->setAttribute('style', implode(';', $cellStyles) . ';');
                        }
                        while ($cell->firstChild) {
                            $cellDiv->appendChild($cell->firstChild);
                        }
                        $rowDiv->appendChild($cellDiv);
                    }
                    $wrapper->appendChild($rowDiv);
                }
            }

            $table->parentNode->replaceChild($wrapper, $table);
            $guard++;
        }

        return static::domRootInnerHtml($dom) ?: $html;
    }

    protected static function domRootInnerHtml(\DOMDocument $dom): string
    {
        $root = $dom->getElementById('qet-root');
        if (!$root) {
            return '';
        }

        $out = '';
        foreach ($root->childNodes as $child) {
            $out .= $dom->saveHTML($child);
        }

        return $out;
    }

    public static function getDefault(): ?self
    {
        return static::active()
            ->orderByDesc('is_default')
            ->orderBy('id')
            ->first();
    }

    /**
     * @return array<string, string>
     */
    public static function variablesFromQuotation(Quotation $quotation, ?Company $company = null, $user = null): array
    {
        $company = $company ?? Company::first();
        $user = $user ?? auth()->user();

        $contactName = trim(optional($quotation->people)->full_name ?? optional($quotation->people)->first_name ?? 'Customer');
        $companyName = $company->name ?? config('app.name', 'CRM');
        $quoteNo = $quotation->quote_no ?? ('Q-' . $quotation->id);
        $clientName = optional($quotation->organization)->name ?? $contactName;
        $currencySymbol = trim((string) ($quotation->currency_symbol ?? ''))
            ?: (trim((string) ($company->currency_symbol ?? '')) ?: '₹');
        $currencyCode = trim((string) ($quotation->currency_code ?? ''))
            ?: (trim((string) ($company->currency_code ?? '')) ?: 'INR');
        // Prefer ASCII-safe amount for email clients (Arabic glyphs like د.إ get corrupted)
        $amount = format_email_money($quotation->grand_total ?? 0, $currencySymbol, $currencyCode);
        $senderName = optional($quotation->owner)->name
            ?? optional($user)->name
            ?? $companyName;
        $quoteDate = $quotation->created_at
            ? $quotation->created_at->format('d M Y')
            : now()->format('d M Y');
        $validUntil = $quotation->valid_until
            ? $quotation->valid_until->format('d M Y')
            : '—';

        return [
            'contact_name' => $contactName,
            'company_name' => $companyName,
            'quote_no'     => $quoteNo,
            'client_name'  => $clientName,
            'amount'       => $amount,
            'currency_symbol' => $currencySymbol,
            'currency_code' => $currencyCode,
            'sender_name'  => $senderName,
            'subject'      => (string) ($quotation->subject ?? ''),
            'quote_date'   => $quoteDate,
            'valid_until'  => $validUntil,
            'company_logo_url' => static::resolveCompanyLogoUrl($company),
            'company_logo' => static::companyLogoImgHtml($company, 52),
        ];
    }

    public static function renderString(string $text, array $variables): string
    {
        $map = [];
        foreach ($variables as $key => $value) {
            $map['{' . $key . '}'] = (string) $value;
        }

        return str_replace(array_keys($map), array_values($map), $text);
    }

    /**
     * @return array{subject: string, body: string}
     */
    public function renderForQuotation(Quotation $quotation, ?Company $company = null, $user = null): array
    {
        $variables = static::variablesFromQuotation($quotation, $company, $user);

        return [
            'subject' => static::renderString($this->subject, $variables),
            'body'    => static::sanitizeEmailBodyHtml(static::renderString($this->body, $variables)),
        ];
    }
}

<?php

namespace App\Services;

use App\Models\User;

class EmailComposeSettingsService
{
    public const DEFAULT_FONT_FAMILY = 'Verdana';
    public const DEFAULT_FONT_SIZE = 14;

    /** @return array<int, string> */
    public function fontFamilies(): array
    {
        return [
            'Verdana',
            'Arial',
            'Helvetica',
            'Tahoma',
            'Trebuchet MS',
            'Times New Roman',
            'Georgia',
            'Courier New',
            'Segoe UI',
            'Calibri',
        ];
    }

    /** @return array<int, int> */
    public function fontSizes(): array
    {
        return [10, 11, 12, 13, 14, 16, 18, 20, 22, 24];
    }

    public function fontFamily(?User $user): string
    {
        $family = trim((string) ($user->email_compose_font_family ?? ''));
        if ($family === '' || !in_array($family, $this->fontFamilies(), true)) {
            return self::DEFAULT_FONT_FAMILY;
        }

        return $family;
    }

    public function fontSize(?User $user): int
    {
        $size = (int) ($user->email_compose_font_size ?? 0);
        if (!in_array($size, $this->fontSizes(), true)) {
            return self::DEFAULT_FONT_SIZE;
        }

        return $size;
    }

    public function signatureName(?User $user): string
    {
        $name = trim((string) ($user->email_signature_name ?? ''));
        if ($name !== '') {
            return $name;
        }

        $full = trim((string) ($user->name ?? ''));
        if ($full === '') {
            return 'Signature';
        }

        return explode(' ', $full)[0] ?: 'Signature';
    }

    public function signatureHtml(?User $user): string
    {
        return trim((string) ($user->email_signature_html ?? ''));
    }

    /**
     * Signature block used inside compose editors.
     */
    public function signatureBlockHtml(?User $user): string
    {
        $signature = $this->signatureHtml($user);
        if ($signature === '') {
            return '';
        }

        return '<div data-crm-email-signature="1">' . $signature . '</div>';
    }

    /**
     * Default body for lead/deal compose (greeting + signature).
     */
    public function buildGreetingBody(?User $user, string $recipientName, ?string $appName = null): string
    {
        $name = trim($recipientName) !== '' ? $recipientName : 'Customer';
        $sender = trim((string) ($user->name ?? '')) ?: (string) config('app.name');
        $brand = $appName ?: (string) config('app.name');

        $html = '<p>Dear ' . e($name) . ',</p><p><br></p>';

        $signature = $this->signatureBlockHtml($user);
        if ($signature !== '') {
            $html .= $signature;
        } else {
            $html .= '<p>' . e($sender) . '<br>' . e($brand) . '</p>';
        }

        return $this->wrapWithComposeFont($user, $html, false);
    }

    /**
     * Paste compose signature into a quotation email template body,
     * replacing the template's closing regards/signature block when present.
     */
    public function injectSignatureIntoTemplateBody(?User $user, string $html): string
    {
        // Always strip previously injected CRM signature blocks first
        $html = preg_replace('/\s*<div[^>]*data-crm-email-signature=["\']1["\'][^>]*>.*?<\/div>/is', '', $html) ?? $html;

        $signature = $this->signatureBlockHtml($user);
        if ($signature === '') {
            return $html;
        }

        // Explicit placeholder support
        if (stripos($html, '{email_signature}') !== false) {
            return str_ireplace('{email_signature}', $signature, $html);
        }

        $pattern = '/<p\b[^>]*>\s*(?:Best\s+regards|Warm\s+regards|Thanks\s*(?:&|&amp;)\s*Regards|Kind\s+regards|Regards)[\s\S]*?<\/p>/i';
        if (preg_match_all($pattern, $html, $matches, PREG_OFFSET_CAPTURE)) {
            $last = end($matches[0]);
            $start = $last[1];
            $length = strlen($last[0]);

            return substr($html, 0, $start) . $signature . substr($html, $start + $length);
        }

        // Fallback: append once at the end of the main content
        return rtrim($html) . '<br>' . $signature;
    }

    /**
     * Prepare quotation template HTML for compose editors (inject signature + font).
     */
    public function prepareQuotationBody(?User $user, string $html): string
    {
        $html = $this->injectSignatureIntoTemplateBody($user, $html);

        return $this->wrapWithComposeFont($user, $html, true);
    }

    /**
     * Append configured signature (if missing) without changing the rest of the body.
     */
    public function appendSignatureIfNeeded(?User $user, string $html): string
    {
        return $this->injectSignatureIntoTemplateBody($user, $html);
    }

    /**
     * Append configured signature (if missing) and apply font styles before sending.
     */
    public function finalizeOutgoingHtml(?User $user, string $htmlBody): string
    {
        $body = trim($htmlBody);
        if ($body === '') {
            $body = '<p></p>';
        }

        if (strip_tags($body) === $body) {
            $body = nl2br(e($body));
        }

        $body = $this->injectSignatureIntoTemplateBody($user, $body);
        $body = $this->wrapWithComposeFont($user, $body, true);

        return \App\Models\QuotationEmailTemplate::wrapOutgoingEmailHtml($body);
    }

    /**
     * Editor content style for Quill / compose editors.
     */
    public function editorContentStyle(?User $user): string
    {
        $family = $this->fontFamily($user);
        $size = $this->fontSize($user);

        return 'body { font-family:' . $family . ', sans-serif; font-size:' . $size . 'px; color:#202124; line-height:1.5; }';
    }

    /**
     * Ensure body uses compose font (optionally replace existing wrapper).
     */
    public function wrapWithComposeFont(?User $user, string $html, bool $forceReplace = false): string
    {
        $family = $this->cssFontFamily($this->fontFamily($user));
        $size = $this->fontSize($user);
        $inner = $html;

        if (preg_match('/<div[^>]*data-crm-email-compose=["\']1["\'][^>]*>(.*)<\/div>\s*$/is', $html, $m)) {
            if (!$forceReplace) {
                return $html;
            }
            $inner = $m[1];
        }

        return '<div data-crm-email-compose="1" style="font-family:' . $family
            . '; font-size:' . $size . 'px; color:#202124; line-height:1.5;">'
            . $inner
            . '</div>';
    }

    protected function containsSignatureMarker(string $html): bool
    {
        return stripos($html, 'data-crm-email-signature') !== false;
    }

    protected function cssFontFamily(string $family): string
    {
        // Quote multi-word families for CSS
        if (str_contains($family, ' ')) {
            return "'" . str_replace("'", '', $family) . "', sans-serif";
        }

        return $family . ', sans-serif';
    }
}

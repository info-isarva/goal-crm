<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Quotation;
use App\Models\QuotationTemplate;
use App\Models\QuotationTemplateVersion;
use App\Library\TcpdfSafeImage;
use App\Library\MpdfDocument;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class QuotationTemplateService
{
    /**
     * Placeholder catalog grouped by category.
     */
    public static function placeholderGroups(): array
    {
        return [
            'Company' => [
                '{{company_name}}',
                '{{company_logo}}',
                '{{company_address}}',
                '{{company_phone}}',
                '{{company_email}}',
                '{{company_website}}',
                '{{company_phone_line}}',
                '{{company_email_line}}',
                '{{company_website_line}}',
                '{{company_gst}}',
            ],
            'Customer' => [
                '{{customer_name}}',
                '{{customer_company}}',
                '{{customer_address}}',
                '{{customer_phone}}',
                '{{customer_email}}',
                '{{customer_gst}}',
            ],
            'Quotation' => [
                '{{quotation_number}}',
                '{{quotation_date}}',
                '{{quotation_expiry_date}}',
                '{{quotation_reference}}',
                '{{quotation_status}}',
                '{{proposal_version}}',
            ],
            'Sales / User' => [
                '{{sales_person}}',
                '{{sales_person_email}}',
                '{{sales_person_phone}}',
            ],
            'Financial' => [
                '{{subtotal}}',
                '{{discount}}',
                '{{tax}}',
                '{{shipping}}',
                '{{grand_total}}',
                '{{amount_in_words}}',
            ],
            'Other' => [
                '{{terms_conditions}}',
                '{{notes}}',
                '{{payment_terms}}',
                '{{signature}}',
                '{{designation}}',
                '{{closing_block}}',
                '{{bank_details_block}}',
                '{{project_detail}}',
                '{{quote_summary_block}}',
                '{{subject_block}}',
                '{{subject}}',
                '{{quotation_title}}',
            ],
            'Items' => [
                '{{items}}',
            ],
        ];
    }

    public static function defaultHtml(): string
    {
        return '<div class="qt-page">'
            . '<div class="qt-page-header">'
            . self::standardQuotationHeaderHtml()
            . <<<'HTML'

    </div>

    <div class="qt-page-body">
    {{subject_block}}
            <table class="qt-titlebar" width="100%" cellpadding="0" cellspacing="0">
        <tr>
            <td width="100%" valign="bottom" class="qt-right">
                Quotation No.: <strong>{{quotation_number}}</strong>
            </td>
        </tr>
    </table>

    <table class="qt-info" width="100%" cellpadding="0" cellspacing="0">
        <tr>
            <td class="qt-box" width="48%" valign="top">
                <div class="qt-label">BILL TO</div>
                <div class="qt-customer">{{customer_name}}</div>
                <div>{{customer_company}}</div>
                <div>{{customer_address}}</div>
                <div>Email: {{customer_email}}</div>
                <div>Phone: {{customer_phone}}</div>
                <div>{{customer_gst}}</div>
            </td>
            <td width="4%"></td>
            <td class="qt-box" width="48%" valign="top">
                <table class="qt-meta" width="100%" cellpadding="0" cellspacing="0">
                    <tr>
                        <td class="qt-meta-label">Quotation Date</td>
                        <td class="qt-meta-value">{{quotation_date}}</td>
                    </tr>
                    <tr>
                        <td class="qt-meta-label">Valid Until</td>
                        <td class="qt-meta-value">{{quotation_expiry_date}}</td>
                    </tr>
                    <tr>
                        <td class="qt-meta-label">Prepared By</td>
                        <td class="qt-meta-value">{{sales_person}}</td>
                    </tr>
                    <tr>
                        <td class="qt-meta-label">Proposal Version</td>
                        <td class="qt-meta-value">{{proposal_version}}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    {{quote_summary_block}}

    <div class="qt-items-wrap">{{items}}</div>

    <table class="qt-bottom" width="100%" cellpadding="0" cellspacing="0">
        <tr>
            <td class="qt-box qt-words" width="48%" valign="top">
                <div class="qt-label">AMOUNT IN WORDS</div>
                <div class="qt-words-text">{{amount_in_words}}</div>
            </td>
            <td width="4%"></td>
            <td width="48%" valign="top">
                <table class="qt-totals-box" width="100%" cellpadding="0" cellspacing="0">
                    <tr>
                        <td>Subtotal</td>
                        <td class="qt-right">{{subtotal}}</td>
                    </tr>
                    <tr>
                        <td>Discount</td>
                        <td class="qt-right">{{discount}}</td>
                    </tr>
                    {{tax_row}}
                    {{shipping_row}}
                    <tr class="qt-grand">
                        <td><strong>Grand Total</strong></td>
                        <td class="qt-right"><strong>{{grand_total}}</strong></td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    {{notes_block}}
    {{bank_details_block}}
    {{terms_block}}

    {{closing_block}}
    </div>

    <div class="qt-page-footer">
        <table class="qt-footer" width="100%" cellpadding="0" cellspacing="0">
            <tr>
                <td style="text-align:left;">{{footer_left}}</td>
                <td class="qt-footer-right" style="text-align:right;">Page {PAGENO} of {nbpg}</td>
            </tr>
        </table>
    </div>
</div>
HTML;
    }

    /**
     * Standard Quotation header: green top/bottom bars + logo | company | contact.
     */
    public static function standardQuotationHeaderHtml(): string
    {
        return <<<'HTML'
<table class="qt-header-frame" width="100%" cellpadding="0" cellspacing="0" border="0">
    <tr>
        <td class="qt-header-bar" bgcolor="#2E7D32" style="height:4px;line-height:4px;font-size:1px;background:#2E7D32;padding:0;">&nbsp;</td>
    </tr>
    <tr>
        <td class="qt-header-body" style="padding:10px 0;vertical-align:middle;">
HTML
            . self::standardQuotationTopTableHtml()
            . <<<'HTML'
        </td>
    </tr>
    <tr>
        <td class="qt-header-bar" bgcolor="#2E7D32" style="height:4px;line-height:4px;font-size:1px;background:#2E7D32;padding:0;">&nbsp;</td>
    </tr>
</table>
HTML;
    }

    /**
     * Standard Quotation header row: logo | company name (full width, 1 line) + address | contact (right).
     */
    public static function standardQuotationTopTableHtml(): string
    {
        return <<<'HTML'
<table class="qt-top" width="100%" cellpadding="0" cellspacing="0" border="0">
    <tr>
        <td class="qt-logo-cell" width="20%" rowspan="2" valign="middle" style="width:20%;vertical-align:middle;padding:0 28px 0 0;">{{company_logo}}</td>
        <td class="qt-company-name-row" colspan="2" valign="middle" style="padding:0 12px 6px 0;text-align:left;vertical-align:middle;">
            <div class="qt-company-name" style="white-space:nowrap;font-size:12px;font-weight:bold;color:#2E7D32;text-transform:uppercase;letter-spacing:0.15px;line-height:1.25;margin:0;">{{company_name}}</div>
        </td>
        <td class="qt-contact-cell" width="1%" rowspan="2" valign="middle" align="right" style="width:1%;text-align:right;vertical-align:middle;padding:4px 0 4px 16px;border-left:1px solid #c8d6e5;white-space:nowrap;">
            {{company_contact_block}}
        </td>
    </tr>
    <tr>
        <td class="qt-company-cell" valign="top" style="vertical-align:top;text-align:left;padding:0 12px 0 0;">
            <div class="qt-muted">{{company_address}}</div>
            <div class="qt-gst">{{company_gst}}</div>
        </td>
        <td class="qt-top-spacer-cell" width="*" valign="top" style="width:auto;padding:0;font-size:0;line-height:0;">&nbsp;</td>
    </tr>
</table>
HTML;
    }

    /**
     * Built qt-top header for PDF: company name on its own full-width row; contact rowspan right.
     */
    protected function buildQtTopTableHtml(string $logoHtml, string $companyHtml, string $contactHtml): string
    {
        $nameBlock = '';
        $detailsBlock = trim($companyHtml);
        if (preg_match('/(<div\b[^>]*class=(["\'])[^"\']*\bqt-company-name\b[^"\']*\2[^>]*>.*?<\/div>)/is', $companyHtml, $nameMatch)) {
            $nameBlock = $nameMatch[1];
            $detailsBlock = trim(preg_replace('/<div\b[^>]*class=(["\'])[^"\']*\bqt-company-name\b[^"\']*\1[^>]*>.*?<\/div>\s*/is', '', $companyHtml) ?? $companyHtml);
        }

        if ($nameBlock === '' && $detailsBlock !== '') {
            $nameBlock = '<div class="qt-company-name" style="white-space:nowrap;font-size:12px;font-weight:bold;color:#2E7D32;text-transform:uppercase;letter-spacing:0.15px;line-height:1.25;margin:0;">'
                . trim(strip_tags($detailsBlock))
                . '</div>';
            $detailsBlock = '';
        } elseif ($nameBlock !== '') {
            $nameBlock = preg_replace(
                '/(<div\b[^>]*class=(["\'])[^"\']*\bqt-company-name\b[^"\']*\2[^>]*)(?:\sstyle=(["\'])[^"\']*\3)?/i',
                '$1 style="white-space:nowrap;font-size:12px;font-weight:bold;color:#2E7D32;text-transform:uppercase;letter-spacing:0.15px;line-height:1.25;margin:0;"',
                $nameBlock,
                1
            ) ?? $nameBlock;
        }

        $contactWrapped = '<div class="qt-contact-wrap" style="text-align:right;width:100%;">' . $contactHtml . '</div>';

        return '<table class="qt-top" width="100%" cellpadding="0" cellspacing="0" border="0"><tr>'
            . '<td class="qt-logo-cell" width="20%" rowspan="2" valign="middle" style="width:20%;vertical-align:middle;padding:0 28px 0 0;">'
            . $logoHtml
            . '</td>'
            . '<td class="qt-company-name-row" colspan="2" valign="middle" style="padding:0 12px 6px 0;text-align:left;vertical-align:middle;">'
            . $nameBlock
            . '</td>'
            . '<td class="qt-contact-cell" width="1%" rowspan="2" valign="middle" align="right" style="width:1%;text-align:right;vertical-align:middle;padding:4px 0 4px 16px;border-left:1px solid #c8d6e5;white-space:nowrap;">'
            . $contactWrapped
            . '</td></tr><tr>'
            . '<td class="qt-company-cell" valign="top" style="vertical-align:top;text-align:left;padding:0 12px 0 0;">'
            . $detailsBlock
            . '</td>'
            . '<td class="qt-top-spacer-cell" width="*" valign="top" style="width:auto;padding:0;font-size:0;line-height:0;">&nbsp;</td>'
            . '</tr></table>';
    }

    protected function stripContactLinesFromCompanyHtml(string $html): string
    {
        $html = preg_replace('/<div class="qt-muted"[^>]*>[^<]*@[^<]*<\/div>\s*/i', '', $html) ?? $html;
        $html = preg_replace('/<div class="qt-muted"[^>]*>[^<]*(?:\+?\d[\d\s\-]{6,})[^<]*<\/div>\s*/i', '', $html) ?? $html;
        $html = preg_replace('/<div class="qt-muted"[^>]*>[^<]*(?:www\.|https?:\/\/|\.com\b|\.in\b)[^<]*<\/div>\s*/i', '', $html) ?? $html;

        return trim($html);
    }

    public static function defaultCss(): string
    {
        return <<<'CSS'
/* Exact A4 sheet for preview + PDF */
@page { size: A4; margin: 0; }
* { box-sizing: border-box; }
html, body {
    margin: 0;
    padding: 0;
    background: #e8e8e8;
    color: #222222;
    font-family: DejaVu Sans, Helvetica, Arial, sans-serif;
    font-size: 11px;
    line-height: 1.45;
}
.qt-page {
    width: 210mm;
    min-height: 297mm;
    margin: 0 auto;
    padding: 15mm;
    background: #ffffff;
    box-shadow: none;
    display: flex;
    flex-direction: column;
}
.qt-page-header { flex: 0 0 auto; width: 100%; }
.qt-page-body { flex: 1 1 auto; width: 100%; }
.qt-page-footer { flex: 0 0 auto; width: 100%; margin-top: auto; }
.qt-top, .qt-titlebar, .qt-info, .qt-bottom, .qt-sign, .qt-footer, .qt-rule,
.qt-items-table, .qt-meta, .qt-totals-box, .qt-header-frame { width: 100%; border-collapse: collapse; }
.qt-header-frame { margin: 0 0 12px 0; }
.qt-header-bar { height: 4px; line-height: 4px; font-size: 1px; background: #2E7D32; padding: 0; }
.qt-header-body { padding: 10px 0; vertical-align: middle; }
.qt-top { margin-bottom: 0; }
.qt-logo-cell { vertical-align: middle; padding-right: 28px; }
.qt-logo-cell img { max-width: 150px; max-height: 58px; }
.qt-company-name-row { vertical-align: middle; text-align: left; padding: 0 12px 6px 0; }
.qt-company-cell { vertical-align: top; text-align: left; padding: 0 12px 0 0; width: auto; }
.qt-top-spacer-cell { width: 100%; padding: 0; margin: 0; font-size: 0; line-height: 0; border: 0; }
.qt-contact-cell { vertical-align: middle; text-align: right !important; padding: 4px 0 4px 16px !important; width: 1%; white-space: nowrap; border-left: 1px solid #c8d6e5; }
.qt-contact-wrap { text-align: right; width: 100%; }
.qt-contact-block { border-collapse: collapse; margin: 0 0 0 auto; }
.qt-contact-line { border-collapse: collapse; margin: 0 0 0 auto; }
.qt-contact-icon-cell { width: 16px; padding: 2px 8px 2px 0; vertical-align: middle; }
.qt-contact-icon { display: block; width: 12px; height: 12px; margin: 0; padding: 0; border: 0; }
.qt-contact-text { vertical-align: middle; text-align: left; font-size: 9px; color: #555555; line-height: 1.45; white-space: nowrap; }
.qt-company-name { font-size: 12px; font-weight: bold; color: #2E7D32; margin: 0; text-transform: uppercase; letter-spacing: 0.15px; line-height: 1.25; white-space: nowrap; display: block; }
.qt-muted { color: #555555; font-size: 9px; line-height: 1.45; }
.qt-gst { margin: 2px 0 0 0; font-size: 9px; font-weight: normal; color: #555555; }
.qt-titlebar { margin: 0 0 4px 0; border-bottom: none; }
.qt-doc-title { font-size: 22px; font-weight: bold; letter-spacing: 1px; color: #2E7D32; text-decoration: none; }
.qt-titlebar .qt-right { font-size: 11px; color: #333333; text-decoration: none; border-bottom: none; }
.qt-titlebar td, .qt-titlebar strong { text-decoration: none; border-bottom: none; }
.qt-subject-banner {
    text-align: center;
    border-top: 1px solid #1e4785;
    border-bottom: 1px solid #1e4785;
    padding: 16px 12px;
    margin: 4px 0 12px 0;
    width: 100%;
}
.qt-subject-banner-text {
    color: #1e4785;
    font-size: 15px;
    font-weight: bold;
    line-height: 1.45;
}
.qt-rule { margin: 0 0 12px 0; }
.qt-rule td { height: 2px; line-height: 2px; font-size: 1px; background: #2E7D32; }
.qt-info { margin-bottom: 12px; }
.qt-box { border: 1px solid #d5d5d5; padding: 10px 12px; background: #ffffff; vertical-align: top; }
.qt-label { font-size: 9px; font-weight: bold; letter-spacing: 0.8px; color: #2E7D32; margin: 0 0 6px 0; text-transform: uppercase; }
.qt-customer { font-size: 12px; font-weight: bold; color: #111111; margin: 0 0 3px 0; }
.qt-meta { width: 100%; border-collapse: collapse; }
.qt-meta td { padding: 5px 0; border-bottom: 1px solid #ececec; font-size: 10px; vertical-align: middle; }
.qt-meta tr:last-child td { border-bottom: 0; }
.qt-meta-label { width: 48%; color: #666666; }
.qt-meta-value { width: 52%; color: #111111; text-align: right; font-weight: bold; }
.qt-intro { margin: 0 0 10px 0; color: #333333; }
.qt-intro p { margin: 3px 0; }
.qt-quote-summary { text-align: center; margin: 14px 0 12px 0; }
.qt-quote-summary-title { color: #2F6FED; font-size: 16px; font-weight: bold; margin: 0 0 8px 0; line-height: 1.35; }
.qt-quote-summary-intro { text-align: left; color: #222222; font-size: 11px; margin: 0 0 10px 0; line-height: 1.45; }
.qt-quote-summary-heading { color: #111111; font-size: 13px; font-weight: bold; margin: 0 0 8px 0; }
.qt-items-wrap { margin: 0 0 12px 0; }
.qt-items-table { table-layout: fixed; font-size: 10px; border-collapse: collapse; }
.qt-items-table th { background: #2E7D32; color: #ffffff; padding: 8px 6px; border: 1px solid #2E7D32; font-weight: bold; }
.qt-items-table td { padding: 8px 6px; border-bottom: 1px solid #e0e0e0; vertical-align: top; color: #222222; }
.qt-item-name { font-weight: bold; color: #111111; }
.qt-item-meta { color: #777777; font-size: 9px; margin-top: 2px; }
.qt-c { text-align: center; }
.qt-l { text-align: left; }
.qt-r, .qt-right { text-align: right; }
.qt-center { text-align: center; }
.qt-bottom { margin: 0 0 14px 0; }
.qt-words-text { font-size: 11px; font-weight: bold; color: #111111; line-height: 1.5; }
.qt-totals-box td { padding: 5px 0; border-bottom: 1px solid #ececec; font-size: 10px; color: #444444; }
.qt-totals-box tr:last-child td { border-bottom: 0; }
.qt-grand td { border-bottom: 0; padding-top: 8px; color: #2E7D32; font-size: 13px; font-weight: bold; }
.qt-section { margin: 12px 0 0 0; padding: 0; border: 0; }
.qt-section .qt-label { margin-bottom: 4px; }
.qt-section-body { color: #444444; font-size: 10px; line-height: 1.5; }
.qt-sign { margin-top: 28px; }
.qt-thanks { color: #333333; font-size: 11px; }
.qt-sign-block { text-align: center; }
.qt-sign-for { font-size: 10px; margin-bottom: 4px; }
.qt-sign-line { border-bottom: 1px solid #333333; height: 36px; margin: 0 18px 6px 18px; }
.qt-sign-role { font-size: 9px; color: #666666; }
.qt-sign-name { font-size: 10px; color: #111111; margin-top: 2px; }
.qt-footer { margin-top: 18px; border-top: 1px solid #dddddd; }
.qt-footer td { padding-top: 8px; color: #777777; font-size: 8px; text-align: left; }
.qt-footer-left { text-align: left !important; }
.qt-footer-right { text-align: right !important; white-space: nowrap; }
CSS;
    }

    /**
     * Executive navy layout — repeating page header/footer, clean body content.
     * Distinct from the green Standard Quotation design.
     */
    public static function professionalHtml(): string
    {
        return <<<'HTML'
<div class="qt-page">
    <div class="qt-page-header">
        <table class="qt-hero" width="100%" cellpadding="0" cellspacing="0">
            <tr>
                <td class="qt-hero-left" width="62%" valign="middle">
                    <table width="100%" cellpadding="0" cellspacing="0">
                        <tr>
                            <td class="qt-logo-cell" width="28%" valign="middle">{{company_logo}}</td>
                            <td class="qt-brand-cell" width="72%" valign="middle">
                                <div class="qt-company-name">{{company_name}}</div>
                                <div class="qt-muted">{{company_phone}} &nbsp;|&nbsp; {{company_email}}</div>
                            </td>
                        </tr>
                    </table>
                </td>
                <td class="qt-hero-right" width="38%" valign="middle">
                    <div class="qt-doc-no">{{quotation_number}}</div>
                </td>
            </tr>
        </table>
    </div>

    <div class="qt-page-body">
        {{subject_block}}
        <table class="qt-meta-strip" width="100%" cellpadding="0" cellspacing="0">
            <tr>
                <td class="qt-meta-cell" width="25%" valign="top">
                    <div class="qt-meta-k">Quote Date</div>
                    <div class="qt-meta-v">{{quotation_date}}</div>
                </td>
                <td class="qt-meta-cell" width="25%" valign="top">
                    <div class="qt-meta-k">Valid Until</div>
                    <div class="qt-meta-v">{{quotation_expiry_date}}</div>
                </td>
                <td class="qt-meta-cell" width="25%" valign="top">
                    <div class="qt-meta-k">Prepared By</div>
                    <div class="qt-meta-v">{{sales_person}}</div>
                </td>
                <td class="qt-meta-cell qt-meta-last" width="25%" valign="top">
                    <div class="qt-meta-k">Proposal Version</div>
                    <div class="qt-meta-v">{{proposal_version}}</div>
                </td>
            </tr>
        </table>

        <table class="qt-parties" width="100%" cellpadding="0" cellspacing="0">
            <tr>
                <td class="qt-client" width="54%" valign="top">
                    <div class="qt-label">Bill To</div>
                    <div class="qt-customer">{{customer_name}}</div>
                    <div class="qt-client-line">{{customer_company}}</div>
                    <div class="qt-client-line">{{customer_address}}</div>
                    <div class="qt-client-line">Email: {{customer_email}}</div>
                    <div class="qt-client-line">Phone: {{customer_phone}}</div>
                    <div class="qt-client-line">{{customer_gst}}</div>
                </td>
                <td width="3%">&nbsp;</td>
                <td class="qt-contact" width="43%" valign="top">
                    <div class="qt-label">Company</div>
                    <div class="qt-client-line">{{company_address}}</div>
                    <div class="qt-client-line">{{company_gst}}</div>
                    <div class="qt-client-line">{{company_website}}</div>
                    <div class="qt-pay-label">Payment Terms</div>
                    <div class="qt-pay-text">{{payment_terms}}</div>
                </td>
            </tr>
        </table>

        {{quote_summary_block}}

        <div class="qt-items-wrap">{{items}}</div>

        <table class="qt-bottom" width="100%" cellpadding="0" cellspacing="0">
            <tr>
                <td class="qt-words" width="52%" valign="top">
                    <div class="qt-label">Amount in Words</div>
                    <div class="qt-words-text">{{amount_in_words}}</div>
                </td>
                <td width="3%">&nbsp;</td>
                <td class="qt-totals-wrap" width="45%" valign="top">
                    <table class="qt-totals-box" width="100%" cellpadding="0" cellspacing="0">
                        <tr>
                            <td>Subtotal</td>
                            <td class="qt-right">{{subtotal}}</td>
                        </tr>
                        <tr>
                            <td>Discount</td>
                            <td class="qt-right">{{discount}}</td>
                        </tr>
                        {{tax_row}}
                        {{shipping_row}}
                        <tr class="qt-grand">
                            <td>Grand Total</td>
                            <td class="qt-right">{{grand_total}}</td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

        {{notes_block}}
        {{bank_details_block}}
        {{terms_block}}

        {{closing_block}}
    </div>

    <div class="qt-page-footer">
        <table class="qt-footer" width="100%" cellpadding="0" cellspacing="0">
            <tr>
                <td class="qt-footer-left" width="70%" valign="middle" style="text-align:left;">
                    {{footer_left}}
                </td>
                <td class="qt-footer-right" width="30%" valign="middle">
                    Page {PAGENO} of {nbpg}
                </td>
            </tr>
        </table>
    </div>
</div>
HTML;
    }

    public static function professionalCss(): string
    {
        return <<<'CSS'
@page { size: A4; margin: 0; }
* { box-sizing: border-box; }
html, body {
    margin: 0;
    padding: 0;
    background: #e6ebf2;
    color: #1a2332;
    font-family: DejaVu Sans, Helvetica, Arial, sans-serif;
    font-size: 11px;
    line-height: 1.45;
}
.qt-page {
    width: 210mm;
    min-height: 297mm;
    margin: 0 auto;
    padding: 0;
    background: #ffffff;
    box-shadow: none;
    display: flex;
    flex-direction: column;
}
.qt-page-header,
.qt-page-footer,
.qt-page-body {
    width: 100%;
}
.qt-page-header { flex: 0 0 auto; }
.qt-page-body { flex: 1 1 auto; }
.qt-page-footer { flex: 0 0 auto; margin-top: auto; }
.qt-hero,
.qt-meta-strip,
.qt-parties,
.qt-bottom,
.qt-notes-row,
.qt-sign,
.qt-footer,
.qt-items-table,
.qt-totals-box {
    width: 100%;
    border-collapse: collapse;
}
.qt-hero {
    background: #0f2c59;
    color: #ffffff;
}
.qt-hero-left {
    padding: 10px 14px;
    vertical-align: middle;
}
.qt-hero-right {
    padding: 10px 14px;
    text-align: right;
    vertical-align: middle;
    border-left: 1px solid #2a4a7a;
}
.qt-logo-cell {
    vertical-align: middle;
    padding-right: 10px;
}
.qt-logo-cell img {
    width: 120px;
    height: 46px;
    max-width: 120px;
    max-height: 46px;
    background: #ffffff;
    padding: 3px;
}
.qt-brand-cell { vertical-align: middle; }
.qt-company-name {
    font-size: 12px;
    font-weight: bold;
    color: #ffffff;
    text-transform: uppercase;
    letter-spacing: 0.3px;
    margin: 0 0 3px 0;
}
.qt-muted {
    color: #c9d6e8;
    font-size: 8px;
    line-height: 1.35;
}
.qt-doc-badge {
    font-size: 9px;
    font-weight: bold;
    letter-spacing: 1.2px;
    color: #0f2c59;
    background: #ffffff;
    padding: 3px 8px;
    margin-bottom: 5px;
    display: inline-block;
}
.qt-doc-no {
    font-size: 13px;
    font-weight: bold;
    color: #ffffff;
}
.qt-page-body {
    padding: 8px 0 6px 0;
}
.qt-subject-banner {
    text-align: center;
    border-top: 1px solid #1e4785;
    border-bottom: 1px solid #1e4785;
    padding: 16px 12px;
    margin: 4px 0 12px 0;
    width: 100%;
}
.qt-subject-banner-text {
    color: #1e4785;
    font-size: 15px;
    font-weight: bold;
    line-height: 1.45;
}
.qt-meta-strip {
    background: #f0f4f9;
    border: 1px solid #d5dee9;
    margin: 0 0 12px 0;
}
.qt-meta-cell {
    padding: 8px 10px;
    border-right: 1px solid #d5dee9;
    vertical-align: top;
}
.qt-meta-last { border-right: 0; }
.qt-meta-k {
    font-size: 8px;
    font-weight: bold;
    letter-spacing: 0.6px;
    text-transform: uppercase;
    color: #1b6ca8;
    margin: 0 0 2px 0;
}
.qt-meta-v {
    font-size: 10px;
    font-weight: bold;
    color: #1a2332;
}
.qt-parties {
    margin: 0 0 10px 0;
}
.qt-client,
.qt-contact {
    padding: 10px 12px;
    vertical-align: top;
}
.qt-client {
    border-left: 4px solid #1b6ca8;
    background: #f7fafc;
}
.qt-contact {
    background: #ffffff;
    border: 1px solid #e2e8f0;
}
.qt-label {
    font-size: 8px;
    font-weight: bold;
    letter-spacing: 0.8px;
    text-transform: uppercase;
    color: #1b6ca8;
    margin: 0 0 5px 0;
}
.qt-customer {
    font-size: 12px;
    font-weight: bold;
    color: #0f2c59;
    margin: 0 0 3px 0;
}
.qt-client-line {
    font-size: 9px;
    color: #3d4a5c;
    line-height: 1.4;
}
.qt-pay-label {
    margin-top: 8px;
    font-size: 8px;
    font-weight: bold;
    letter-spacing: 0.6px;
    text-transform: uppercase;
    color: #1b6ca8;
}
.qt-pay-text {
    font-size: 9px;
    color: #1a2332;
    margin-top: 2px;
}
.qt-intro {
    margin: 0 0 10px 0;
    padding: 8px 10px;
    background: #eef5fb;
    border-left: 3px solid #1b6ca8;
    color: #334155;
    font-size: 10px;
}
.qt-quote-summary { text-align: center; margin: 14px 0 12px 0; }
.qt-quote-summary-title { color: #2F6FED; font-size: 16px; font-weight: bold; margin: 0 0 8px 0; line-height: 1.35; }
.qt-quote-summary-intro { text-align: left; color: #222222; font-size: 11px; margin: 0 0 10px 0; line-height: 1.45; }
.qt-quote-summary-heading { color: #111111; font-size: 13px; font-weight: bold; margin: 0 0 8px 0; }
.qt-items-wrap {
    margin: 0 0 12px 0;
}
.qt-items-table {
    table-layout: fixed;
    font-size: 9px;
    border-collapse: collapse;
}
.qt-items-table th {
    background: #0f2c59;
    color: #ffffff;
    padding: 7px 5px;
    border: 1px solid #0f2c59;
    font-weight: bold;
}
.qt-items-table td {
    padding: 7px 5px;
    border-bottom: 1px solid #e2e8f0;
    vertical-align: top;
    color: #1a2332;
}
.qt-item-name {
    font-weight: bold;
    color: #0f2c59;
}
.qt-item-meta {
    color: #64748b;
    font-size: 8px;
    margin-top: 2px;
}
.qt-c { text-align: center; }
.qt-l { text-align: left; }
.qt-r, .qt-right { text-align: right; }
.qt-center { text-align: center; }
.qt-bottom {
    margin: 0 0 12px 0;
}
.qt-words {
    background: #f0f4f9;
    border: 1px solid #d5dee9;
    padding: 10px;
    vertical-align: top;
}
.qt-words-text {
    font-size: 10px;
    font-weight: bold;
    color: #0f2c59;
    line-height: 1.45;
}
.qt-totals-wrap { vertical-align: top; }
.qt-totals-box {
    border: 1px solid #0f2c59;
    background: #ffffff;
}
.qt-totals-box td {
    padding: 6px 10px;
    border-bottom: 1px solid #e2e8f0;
    font-size: 10px;
    color: #334155;
}
.qt-totals-box tr:last-child td {
    border-bottom: 0;
}
.qt-grand td {
    background: #0f2c59;
    color: #ffffff !important;
    font-size: 12px;
    font-weight: bold;
    padding-top: 8px;
    padding-bottom: 8px;
    border-bottom: 0;
}
.qt-notes-row {
    margin: 0 0 8px 0;
}
.qt-notes-col {
    vertical-align: top;
}
.qt-section {
    margin: 0;
    padding: 8px 10px;
    border: 1px solid #e2e8f0;
    background: #ffffff;
}
.qt-section .qt-label {
    margin-bottom: 4px;
}
.qt-section-body,
.qt-section div:not(.qt-label) {
    color: #475569;
    font-size: 9px;
    line-height: 1.45;
}
.qt-sign {
    margin-top: 22px;
}
.qt-thanks {
    color: #334155;
    font-size: 10px;
}
.qt-sign-block { text-align: center; }
.qt-sign-for {
    font-size: 10px;
    font-weight: bold;
    color: #0f2c59;
    margin-bottom: 4px;
}
.qt-sign-line {
    border-bottom: 1px solid #0f2c59;
    height: 36px;
    margin: 0 16px 6px 16px;
}
.qt-sign-role {
    font-size: 8px;
    color: #64748b;
}
.qt-sign-name {
    font-size: 10px;
    color: #1a2332;
    margin-top: 2px;
}
.qt-footer {
    background: #0f2c59;
    width: 100%;
}
.qt-footer-left,
.qt-footer-right {
    padding: 8px 12px;
    color: #c9d6e8;
    font-size: 8px;
}
.qt-footer-right {
    text-align: right;
    font-weight: bold;
    color: #ffffff;
}
@media print {
    @page { size: A4; margin: 8mm; }
    html, body {
        margin: 0 !important;
        padding: 0 !important;
        background: #ffffff !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }
    .qt-page {
        width: 100% !important;
        min-height: auto !important;
        margin: 0 !important;
        padding: 0 !important;
        box-shadow: none !important;
        display: block !important;
    }
    .qt-print-sheet { width: 100% !important; border-collapse: collapse; }
    .qt-print-sheet > thead { display: table-header-group !important; }
    .qt-print-sheet > tfoot { display: table-footer-group !important; }
    .qt-print-sheet > tbody { display: table-row-group !important; }
    .qt-sign, .qt-notes-row, .qt-bottom {
        page-break-inside: avoid;
    }
    * {
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
        box-shadow: none !important;
    }
}
CSS;
    }

    /**
     * Modern Slate layout — teal accent, clean invoice-style structure.
     * Distinct from green Standard and navy Executive Proposal.
     */
    public static function modernSlateHtml(): string
    {
        return <<<'HTML'
<div class="qt-page">
    <div class="qt-page-header">
        <table class="ms-accent" width="100%" cellpadding="0" cellspacing="0">
            <tr><td>&nbsp;</td></tr>
        </table>
        <table class="ms-top" width="100%" cellpadding="0" cellspacing="0">
            <tr>
                <td class="ms-logo" width="26%" valign="middle">{{company_logo}}</td>
                <td class="ms-brand" width="42%" valign="middle">
                    <div class="ms-company">{{company_name}}</div>
                    <div class="ms-muted">{{company_address}}</div>
                    <div class="ms-muted">{{company_gst}}</div>
                </td>
                <td class="ms-contact-box" width="32%" valign="middle">
                    <div class="ms-contact-line">{{company_email}}</div>
                    <div class="ms-contact-line">{{company_phone}}</div>
                    <div class="ms-contact-line">{{company_website}}</div>
                </td>
            </tr>
        </table>
    </div>

    <div class="qt-page-body">
        {{subject_block}}
        <table class="ms-titlebar" width="100%" cellpadding="0" cellspacing="0">
            <tr>
                <td width="100%" valign="bottom" class="ms-right">
                    <span class="ms-k">Valid until</span>
                    <strong>{{quotation_expiry_date}}</strong>
                </td>
            </tr>
        </table>

        <table class="ms-meta" width="100%" cellpadding="0" cellspacing="0">
            <tr>
                <td class="ms-meta-cell" width="25%">
                    <div class="ms-k">Date</div>
                    <div class="ms-v">{{quotation_date}}</div>
                </td>
                <td class="ms-meta-cell" width="25%">
                    <div class="ms-k">Prepared By</div>
                    <div class="ms-v">{{sales_person}}</div>
                </td>
                <td class="ms-meta-cell" width="25%">
                    <div class="ms-k">Reference</div>
                    <div class="ms-v">{{quotation_reference}}</div>
                </td>
                <td class="ms-meta-cell ms-meta-last" width="25%">
                    <div class="ms-k">Proposal Version</div>
                    <div class="ms-v">{{proposal_version}}</div>
                </td>
            </tr>
        </table>

        <table class="ms-parties" width="100%" cellpadding="0" cellspacing="0">
            <tr>
                <td class="ms-bill" width="55%" valign="top">
                    <div class="ms-label">Bill To</div>
                    <div class="ms-customer">{{customer_name}}</div>
                    <div class="ms-line">{{customer_company}}</div>
                    <div class="ms-line">{{customer_address}}</div>
                    <div class="ms-line">Email: {{customer_email}}</div>
                    <div class="ms-line">Phone: {{customer_phone}}</div>
                    <div class="ms-line">{{customer_gst}}</div>
                </td>
                <td width="3%">&nbsp;</td>
                <td class="ms-quote-panel" width="42%" valign="top">
                    <div class="ms-label">Quotation Number</div>
                    <div class="ms-quote-no">{{quotation_number}}</div>
                    <div class="ms-quote-status">Status: {{quotation_status}}</div>
                </td>
            </tr>
        </table>

        {{project_detail_block}}

        {{quote_summary_block}}

        <div class="qt-items-wrap">{{items}}</div>

        <table class="ms-bottom" width="100%" cellpadding="0" cellspacing="0">
            <tr>
                <td class="ms-words" width="52%" valign="top">
                    <div class="ms-label">Amount in Words</div>
                    <div class="ms-words-text">{{amount_in_words}}</div>
                </td>
                <td width="3%">&nbsp;</td>
                <td class="ms-totals-wrap" width="45%" valign="top">
                    <table class="ms-totals" width="100%" cellpadding="0" cellspacing="0">
                        <tr>
                            <td>Subtotal</td>
                            <td class="ms-right">{{subtotal}}</td>
                        </tr>
                        <tr>
                            <td>Discount</td>
                            <td class="ms-right">{{discount}}</td>
                        </tr>
                        {{tax_row}}
                        {{shipping_row}}
                        <tr class="ms-grand">
                            <td>Grand Total</td>
                            <td class="ms-right">{{grand_total}}</td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

        {{notes_block}}
        {{bank_details_block}}
        {{terms_block}}

        {{closing_block}}
    </div>

    <div class="qt-page-footer">
        <table class="ms-footer" width="100%" cellpadding="0" cellspacing="0">
            <tr>
                <td class="ms-footer-left" width="70%" valign="middle" style="text-align:left;">
                    {{footer_left}}
                </td>
                <td class="ms-footer-right" width="30%" valign="middle">
                    Page {PAGENO} of {nbpg}
                </td>
            </tr>
        </table>
    </div>
</div>
HTML;
    }

    public static function modernSlateCss(): string
    {
        return <<<'CSS'
@page { size: A4; margin: 0; }
* { box-sizing: border-box; }
html, body {
    margin: 0;
    padding: 0;
    background: #e8eef2;
    color: #1e293b;
    font-family: DejaVu Sans, Helvetica, Arial, sans-serif;
    font-size: 11px;
    line-height: 1.45;
}
.qt-page {
    width: 210mm;
    min-height: 297mm;
    margin: 0 auto;
    padding: 0;
    background: #ffffff;
    box-shadow: none;
}
.qt-page-header,
.qt-page-body,
.qt-page-footer { width: 100%; }
.ms-accent,
.ms-top,
.ms-titlebar,
.ms-meta,
.ms-parties,
.ms-bottom,
.ms-notes,
.ms-sign,
.ms-footer,
.qt-items-table,
.ms-totals {
    width: 100%;
    border-collapse: collapse;
}
.ms-accent td {
    height: 5px;
    line-height: 5px;
    font-size: 1px;
    background: #0d9488;
}
.ms-top { padding: 0; }
.ms-logo {
    padding: 10px 12px 8px 0;
    vertical-align: middle;
}
.ms-logo img {
    width: 120px;
    height: 46px;
    max-width: 120px;
    max-height: 46px;
}
.ms-brand {
    padding: 10px 8px 8px 0;
    vertical-align: middle;
}
.ms-company {
    font-size: 12px;
    font-weight: bold;
    color: #1e293b;
    text-transform: uppercase;
    margin: 0 0 3px 0;
}
.ms-muted {
    color: #64748b;
    font-size: 8px;
    line-height: 1.35;
}
.ms-contact-box {
    padding: 10px 12px;
    vertical-align: middle;
    text-align: right;
    background: #f0fdfa;
    border: 1px solid #99f6e4;
}
.ms-contact-line {
    font-size: 9px;
    color: #334155;
    line-height: 1.45;
    margin: 0;
}
.ms-quote-no {
    font-size: 14px;
    font-weight: bold;
    color: #1e293b;
    margin: 0 0 4px 0;
}
.ms-quote-status {
    font-size: 9px;
    color: #64748b;
    margin-top: 2px;
}
.qt-page-body { padding: 10px 0 8px 0; }
.ms-titlebar { margin: 0 0 10px 0; border-bottom: 2px solid #0d9488; }
.qt-subject-banner {
    text-align: center;
    border-top: 1px solid #1e4785;
    border-bottom: 1px solid #1e4785;
    padding: 16px 12px;
    margin: 4px 0 12px 0;
    width: 100%;
}
.qt-subject-banner-text {
    color: #1e4785;
    font-size: 15px;
    font-weight: bold;
    line-height: 1.45;
}
.ms-doc-title {
    font-size: 20px;
    font-weight: bold;
    letter-spacing: 1px;
    color: #1e293b;
    padding-bottom: 6px;
}
.ms-right { text-align: right; font-size: 10px; color: #334155; padding-bottom: 6px; }
.ms-k {
    font-size: 8px;
    font-weight: bold;
    letter-spacing: 0.6px;
    text-transform: uppercase;
    color: #0d9488;
    margin: 0 0 2px 0;
}
.ms-v {
    font-size: 10px;
    font-weight: bold;
    color: #1e293b;
}
.ms-meta {
    margin: 0 0 12px 0;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
}
.ms-meta-cell {
    padding: 8px 10px;
    border-right: 1px solid #e2e8f0;
    vertical-align: top;
}
.ms-meta-last { border-right: 0; }
.ms-parties { margin: 0 0 10px 0; }
.ms-bill,
.ms-quote-panel {
    padding: 10px 12px;
    vertical-align: top;
}
.ms-bill {
    background: #f8fafc;
    border-left: 4px solid #0d9488;
}
.ms-quote-panel {
    background: #f0fdfa;
    border: 1px solid #99f6e4;
}
.ms-label {
    font-size: 8px;
    font-weight: bold;
    letter-spacing: 0.8px;
    text-transform: uppercase;
    color: #0d9488;
    margin: 0 0 5px 0;
}
.ms-customer {
    font-size: 12px;
    font-weight: bold;
    color: #1e293b;
    margin: 0 0 3px 0;
}
.ms-line {
    font-size: 9px;
    color: #475569;
    line-height: 1.4;
}
.ms-intro {
    margin: 0 0 10px 0;
    padding: 8px 10px;
    background: #f0fdfa;
    border-left: 3px solid #0d9488;
    color: #334155;
    font-size: 10px;
}
.qt-quote-summary { text-align: center; margin: 14px 0 12px 0; }
.qt-quote-summary-title { color: #2F6FED; font-size: 16px; font-weight: bold; margin: 0 0 8px 0; line-height: 1.35; }
.qt-quote-summary-intro { text-align: left; color: #222222; font-size: 11px; margin: 0 0 10px 0; line-height: 1.45; }
.qt-quote-summary-heading { color: #111111; font-size: 13px; font-weight: bold; margin: 0 0 8px 0; }
.qt-items-wrap { margin: 0 0 12px 0; }
.qt-items-table {
    table-layout: fixed;
    font-size: 9px;
    border-collapse: collapse;
}
.qt-items-table th {
    background: #1e293b;
    color: #ffffff;
    padding: 8px 5px;
    border: 1px solid #1e293b;
    font-weight: bold;
}
.qt-items-table td {
    padding: 7px 5px;
    border-bottom: 1px solid #e2e8f0;
    vertical-align: top;
    color: #1e293b;
}
.qt-item-name { font-weight: bold; color: #1e293b; }
.qt-item-meta { color: #64748b; font-size: 8px; margin-top: 2px; }
.qt-c, .ms-c { text-align: center; }
.qt-l, .ms-l { text-align: left; }
.qt-r, .qt-right, .ms-right { text-align: right; }
.ms-bottom { margin: 0 0 12px 0; }
.ms-words {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    padding: 10px;
    vertical-align: top;
}
.ms-words-text {
    font-size: 10px;
    font-weight: bold;
    color: #1e293b;
    line-height: 1.45;
}
.ms-totals {
    border: 1px solid #1e293b;
    background: #ffffff;
}
.ms-totals td {
    padding: 6px 10px;
    border-bottom: 1px solid #e2e8f0;
    font-size: 10px;
    color: #334155;
}
.ms-totals tr:last-child td { border-bottom: 0; }
.ms-grand td {
    background: #0d9488;
    color: #ffffff !important;
    font-size: 12px;
    font-weight: bold;
    padding-top: 9px;
    padding-bottom: 9px;
    border-bottom: 0;
}
.ms-notes { margin: 0 0 12px 0; width: 100%; }
.ms-notes-col { vertical-align: top; width: 100%; }
.ms-notes-col .qt-section,
.ms-notes-col .ms-block,
.qt-section,
.ms-block,
.ms-block-wrap {
    margin: 0 0 10px 0;
    padding: 0;
    border: 0;
    background: transparent;
    border-collapse: collapse;
    width: 100% !important;
    max-width: 100% !important;
    page-break-inside: auto;
}
.ms-block-title-table {
    width: 100%;
    border-collapse: collapse;
    page-break-inside: avoid;
}
.qt-section .qt-label,
.qt-section .ms-label,
.ms-notes .qt-label,
.ms-block-title {
    margin: 0;
    padding: 8px 10px;
    font-size: 11px;
    font-weight: bold;
    letter-spacing: 0.5px;
    text-transform: uppercase;
    color: #ffffff;
    text-decoration: none !important;
    border: 0 !important;
    border-bottom: none !important;
    background: #1e293b;
    width: 100% !important;
    page-break-inside: avoid;
}
.qt-section-body,
.ms-block-body,
.qt-section td.ms-block-body {
    color: #475569;
    font-size: 9px;
    line-height: 1.45;
    padding: 8px 10px;
    width: 100% !important;
    page-break-inside: auto;
}
.ms-block-body > p:first-child,
.ms-block-body > h1:first-child,
.ms-block-body > h2:first-child,
.ms-block-body > h3:first-child,
.ms-block-body > ul:first-child,
.ms-block-body > ol:first-child {
    margin-top: 0;
}
.ms-block-body *,
.qt-section-body * {
    page-break-inside: auto;
}
.ms-block-body table,
.ms-block-body td,
.ms-block-body th {
    max-width: 100% !important;
}
.ms-block-body p {
    width: auto !important;
    max-width: 100% !important;
}
.ms-sign,
.qt-closing,
.ms-closing {
    margin-top: 14px;
    page-break-inside: auto;
    page-break-before: auto;
    clear: both;
}
.qt-closing,
.ms-closing {
    width: 100%;
    font-family: DejaVu Sans, Helvetica, Arial, sans-serif;
    font-size: 10px;
    color: #1e293b;
    line-height: 1.45;
}
.qt-closing-line,
.ms-closing-line {
    margin: 0 0 2px 0;
}
.qt-closing-sincerely,
.ms-closing-sincerely {
    margin: 0 0 12px 0;
}
.qt-closing-name,
.ms-closing-name {
    margin: 0;
    font-weight: bold;
}
.ms-thanks { color: #334155; font-size: 10px; }
.ms-sign-block { text-align: center; page-break-inside: auto; }
.ms-sign-for {
    font-size: 10px;
    font-weight: bold;
    color: #1e293b;
    margin-bottom: 4px;
}
.ms-sign-line {
    border-bottom: 1px solid #1e293b;
    height: 36px;
    margin: 0 16px 6px 16px;
}
.ms-sign-role { font-size: 8px; color: #64748b; }
.ms-sign-name { font-size: 10px; color: #1e293b; margin-top: 2px; }
.ms-footer { background: #1e293b; width: 100%; }
.ms-footer-left,
.ms-footer-right {
    padding: 9px 12px;
    color: #94a3b8;
    font-size: 8px;
}
.ms-footer-right {
    text-align: right;
    font-weight: bold;
    color: #ffffff;
}
@media print {
    @page { size: A4; margin: 8mm; }
    html, body {
        margin: 0 !important;
        padding: 0 !important;
        background: #ffffff !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }
    .qt-page {
        width: 100% !important;
        min-height: auto !important;
        margin: 0 !important;
        padding: 0 !important;
        box-shadow: none !important;
    }
    .qt-print-sheet > thead { display: table-header-group !important; }
    .qt-print-sheet > tfoot { display: table-footer-group !important; }
    * {
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
        box-shadow: none !important;
    }
}
CSS;
    }

    /**
     * Template 3 — charcoal/emerald professional layout with tight alignment.
     */
    public static function templateThreeHtml(): string
    {
        return <<<'HTML'
<div class="qt-page">
    <div class="qt-page-header">
        <table class="t3-accent" width="100%" cellpadding="0" cellspacing="0">
            <tr><td>&nbsp;</td></tr>
        </table>
        <table class="t3-shell" width="100%" cellpadding="0" cellspacing="0">
            <tr>
                <td class="t3-shell-pad">
                    <table class="t3-top" width="100%" cellpadding="0" cellspacing="0">
                        <tr>
                            <td class="t3-logo" width="22%" valign="middle">{{company_logo}}</td>
                            <td class="t3-brand" width="48%" valign="middle">
                                <div class="t3-company">{{company_name}}</div>
                                <div class="t3-muted">{{company_address}}</div>
                                <div class="t3-muted">{{company_gst}}</div>
                            </td>
                            <td class="t3-contact" width="30%" valign="middle">
                                <div class="t3-contact-line">{{company_email}}</div>
                                <div class="t3-contact-line">{{company_phone}}</div>
                                <div class="t3-contact-line">{{company_website}}</div>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
        <table class="t3-rule" width="100%" cellpadding="0" cellspacing="0">
            <tr><td bgcolor="#059669" style="font-size:2px;line-height:2px;height:2px;">&nbsp;</td></tr>
        </table>
    </div>

    <div class="qt-page-body">
        <table class="t3-shell" width="100%" cellpadding="0" cellspacing="0">
            <tr>
                <td class="t3-shell-pad">
                    {{subject_block}}

                    <table class="t3-titlebar" width="100%" cellpadding="0" cellspacing="0">
                        <tr>
                            <td width="100%" valign="bottom" class="t3-right">
                                <span class="t3-k">Quotation No.</span>
                                <strong>{{quotation_number}}</strong>
                            </td>
                        </tr>
                    </table>

                    <table class="t3-parties" width="100%" cellpadding="0" cellspacing="0">
                        <tr>
                            <td width="48%" valign="top">
                                <table class="t3-panel" width="100%" cellpadding="0" cellspacing="0">
                                    <tr>
                                        <td class="t3-panel-inner">
                                            <div class="t3-label">Bill To</div>
                                            <div class="t3-customer">{{customer_name}}</div>
                                            <div class="t3-line">{{customer_company}}</div>
                                            <div class="t3-line">{{customer_address}}</div>
                                            <div class="t3-line">Email: {{customer_email}}</div>
                                            <div class="t3-line">Phone: {{customer_phone}}</div>
                                            <div class="t3-line">{{customer_gst}}</div>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                            <td width="4%">&nbsp;</td>
                            <td width="48%" valign="top">
                                <table class="t3-panel" width="100%" cellpadding="0" cellspacing="0">
                                    <tr>
                                        <td class="t3-panel-inner">
                                            <table class="t3-meta" width="100%" cellpadding="0" cellspacing="0">
                                                <tr>
                                                    <td class="t3-meta-k">Quotation Date</td>
                                                    <td class="t3-meta-v">{{quotation_date}}</td>
                                                </tr>
                                                <tr>
                                                    <td class="t3-meta-k">Valid Until</td>
                                                    <td class="t3-meta-v">{{quotation_expiry_date}}</td>
                                                </tr>
                                                <tr>
                                                    <td class="t3-meta-k">Prepared By</td>
                                                    <td class="t3-meta-v">{{sales_person}}</td>
                                                </tr>
                                                <tr>
                                                    <td class="t3-meta-k">Proposal Version</td>
                                                    <td class="t3-meta-v">{{proposal_version}}</td>
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    </table>

                    <div class="t3-section">{{project_detail_block}}</div>
                    <div class="t3-section">{{quote_summary_block}}</div>

                    <div class="qt-items-wrap">{{items}}</div>

                    <table class="t3-bottom" width="100%" cellpadding="0" cellspacing="0">
                        <tr>
                            <td width="48%" valign="top">
                                <table class="t3-panel" width="100%" cellpadding="0" cellspacing="0">
                                    <tr>
                                        <td class="t3-panel-inner">
                                            <div class="t3-label">Amount in Words</div>
                                            <div class="t3-words-text">{{amount_in_words}}</div>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                            <td width="4%">&nbsp;</td>
                            <td width="48%" valign="top">
                                <table class="t3-totals" width="100%" cellpadding="0" cellspacing="0">
                                    <tr>
                                        <td>Subtotal</td>
                                        <td class="t3-right">{{subtotal}}</td>
                                    </tr>
                                    <tr>
                                        <td>Discount</td>
                                        <td class="t3-right">{{discount}}</td>
                                    </tr>
                                    {{tax_row}}
                                    {{shipping_row}}
                                    <tr class="t3-grand">
                                        <td><strong>Grand Total</strong></td>
                                        <td class="t3-right"><strong>{{grand_total}}</strong></td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    </table>

                    <div class="t3-section">{{notes_block}}</div>
                    <div class="t3-section">{{bank_details_block}}</div>
                    <div class="t3-section">{{terms_block}}</div>
                    <div class="t3-section">{{closing_block}}</div>
                </td>
            </tr>
        </table>
    </div>

    <div class="qt-page-footer">
        <table class="t3-shell" width="100%" cellpadding="0" cellspacing="0">
            <tr>
                <td class="t3-shell-pad">
                    <table class="t3-footer" width="100%" cellpadding="0" cellspacing="0">
                        <tr>
                            <td class="t3-footer-left" width="70%" valign="middle" style="text-align:left;">
                                {{footer_left}}
                            </td>
                            <td class="t3-footer-right" width="30%" valign="middle">
                                Page {PAGENO} of {nbpg}
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </div>
</div>
HTML;
    }

    public static function templateThreeCss(): string
    {
        return <<<'CSS'
@page { size: A4; margin: 0; }
* { box-sizing: border-box; }
html, body {
    margin: 0;
    padding: 0;
    background: #eef2f0;
    color: #1e293b;
    font-family: DejaVu Sans, Helvetica, Arial, sans-serif;
    font-size: 12px;
    line-height: 1.55;
}
.qt-page {
    width: 210mm;
    min-height: 297mm;
    margin: 0 auto;
    padding: 0;
    background: #ffffff;
    box-shadow: none;
    display: block;
    position: relative;
    overflow: visible;
}
.qt-page-header,
.qt-page-footer,
.qt-page-body {
    width: 100%;
    display: block;
    position: static;
    clear: both;
    float: none;
}
.qt-page-body { padding: 8px 0 10px 0; }

.t3-shell { width: 100%; border-collapse: collapse; }
.t3-shell-pad { padding: 0 12px; }

.t3-accent { width: 100%; background: #0f766e; border-collapse: collapse; }
.t3-accent td { height: 5px; font-size: 5px; line-height: 5px; padding: 0; }
.t3-top { width: 100%; border-collapse: collapse; margin: 0; }
.t3-logo { padding: 10px 8px 8px 0; vertical-align: middle; }
.t3-logo img { max-width: 130px; max-height: 48px; }
.t3-brand { padding: 10px 8px 8px 8px; vertical-align: middle; }
.t3-company {
    font-size: 15px;
    font-weight: bold;
    color: #0f172a;
    margin: 0 0 3px 0;
}
.t3-muted { color: #64748b; font-size: 10px; line-height: 1.45; margin: 0; }
.t3-contact {
    padding: 10px 0 8px 8px;
    vertical-align: middle;
    text-align: right;
    border-left: 2px solid #d1fae5;
}
.t3-contact-line {
    color: #334155;
    font-size: 10px;
    line-height: 1.55;
    margin: 0;
}
.t3-rule { margin: 0; width: 100%; border-collapse: collapse; }

.t3-titlebar {
    width: 100%;
    border-collapse: collapse;
    margin: 0 0 10px 0;
    border-bottom: none;
}
.t3-right { text-align: right; }
.t3-titlebar .t3-right {
    font-size: 11px;
    color: #334155;
    padding: 4px 0 6px 0;
    text-decoration: none;
    border-bottom: none;
}
.t3-titlebar td,
.t3-titlebar strong {
    text-decoration: none !important;
    border-bottom: none !important;
}
.t3-k {
    display: inline;
    color: #64748b;
    font-size: 9px;
    text-transform: uppercase;
    letter-spacing: 0.4px;
    margin-right: 6px;
    text-decoration: none !important;
    border-bottom: none !important;
}

.t3-parties,
.t3-bottom {
    width: 100%;
    border-collapse: collapse;
    margin: 0 0 12px 0;
    clear: both;
}
.t3-panel {
    width: 100%;
    border-collapse: collapse;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
}
.t3-panel-inner {
    padding: 10px 12px;
    vertical-align: top;
}
.t3-label {
    font-size: 10px;
    font-weight: bold;
    letter-spacing: 0.6px;
    text-transform: uppercase;
    color: #0f766e;
    margin: 0 0 6px 0;
}
.t3-customer {
    font-size: 13px;
    font-weight: bold;
    color: #0f172a;
    margin: 0 0 3px 0;
}
.t3-line {
    font-size: 11px;
    color: #475569;
    line-height: 1.55;
    margin: 0;
}
.t3-meta { width: 100%; border-collapse: collapse; }
.t3-meta td {
    padding: 6px 0;
    border-bottom: 1px solid #e2e8f0;
    font-size: 11px;
    vertical-align: middle;
}
.t3-meta tr:last-child td { border-bottom: 0; }
.t3-meta-k { width: 48%; color: #64748b; }
.t3-meta-v { width: 52%; color: #0f172a; text-align: right; font-weight: bold; }

.t3-section {
    width: 100%;
    clear: both;
    float: none;
    position: static;
    display: block;
    margin: 0 0 12px 0;
    overflow: visible;
}

.qt-subject-banner {
    text-align: center;
    border-top: 0;
    border-bottom: 0;
    padding: 10px 8px;
    margin: 0 0 10px 0;
    width: 100%;
    clear: both;
}
.qt-subject-banner-text {
    color: #1e4785;
    font-size: 16px;
    font-weight: bold;
    line-height: 1.4;
    text-decoration: none;
    border: 0;
}
.qt-quote-summary { text-align: center; margin: 0 0 10px 0; clear: both; }
.qt-quote-summary-title { color: #1e4785; font-size: 16px; font-weight: bold; margin: 0 0 8px 0; line-height: 1.35; text-decoration: none; }
.qt-quote-summary-intro { text-align: left; color: #222222; font-size: 12px; margin: 0 0 8px 0; line-height: 1.5; text-decoration: none; }
.qt-quote-summary-heading { color: #111111; font-size: 13px; font-weight: bold; margin: 0 0 8px 0; text-decoration: none; }

.qt-items-wrap { margin: 0 0 12px 0; clear: both; width: 100%; }
.qt-items-table {
    width: 100%;
    table-layout: fixed;
    border-collapse: collapse;
    font-size: 11px;
}
.qt-items-table th {
    background: #0f766e;
    color: #ffffff;
    padding: 8px 5px;
    border: 1px solid #0f766e;
    font-weight: bold;
    font-size: 11px;
}
.qt-items-table td {
    padding: 8px 5px;
    border-bottom: 1px solid #e2e8f0;
    vertical-align: top;
    color: #1e293b;
    word-wrap: break-word;
    font-size: 11px;
}
.qt-item-name { font-weight: bold; color: #0f172a; }
.qt-item-meta { color: #64748b; font-size: 9px; margin-top: 2px; }
.qt-c, .t3-c { text-align: center; }
.qt-l, .t3-l { text-align: left; }
.qt-r, .qt-right, .t3-right { text-align: right; }

.t3-words-text {
    font-size: 11px;
    font-weight: bold;
    color: #0f172a;
    line-height: 1.5;
}
.t3-totals {
    width: 100%;
    border-collapse: collapse;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
}
.t3-totals td {
    padding: 7px 10px;
    border-bottom: 1px solid #e2e8f0;
    font-size: 11px;
    color: #475569;
}
.t3-totals tr:last-child td { border-bottom: 0; }
.t3-grand td {
    background: #ecfdf5;
    color: #065f46;
    font-size: 13px;
    padding-top: 8px;
    padding-bottom: 8px;
}

.t3-footer {
    width: 100%;
    border-collapse: collapse;
    background: #0f172a;
    margin-top: 4px;
}
.t3-footer-left,
.t3-footer-right {
    padding: 8px 10px;
    color: #94a3b8;
    font-size: 8px;
}
.t3-footer-right {
    text-align: right;
    font-weight: bold;
    color: #ffffff;
}

.qt-section,
.ms-block {
    width: 100%;
    clear: both;
    float: none;
    position: static;
    display: block;
    margin: 0 0 10px 0;
}
.qt-section-body,
.ms-block-body {
    font-size: 12px;
    line-height: 1.55;
    color: #334155;
}
.qt-closing {
    width: 100%;
    font-size: 11px;
    color: #1e293b;
    line-height: 1.5;
    margin-top: 10px;
    clear: both;
}
.qt-closing-sincerely { margin: 0 0 12px 0; }
.qt-closing-name { margin: 0; font-weight: bold; }
.qt-closing-line { margin: 0 0 2px 0; }

@media print {
    html, body { background: #ffffff !important; }
    .qt-page {
        width: 100% !important;
        min-height: auto !important;
        box-shadow: none !important;
        display: block !important;
    }
}
CSS;
    }

    /**
     * Studio Quote — clean professional layout (sapphire accent, charcoal type).
     * Distinct from green Standard, navy Executive, and teal Template 3 / Modern Slate.
     */
    public static function studioQuoteHtml(): string
    {
        return <<<'HTML'
<div class="qt-page">
    <div class="qt-page-header">
        <table class="sq-accent" width="100%" cellpadding="0" cellspacing="0">
            <tr><td>&nbsp;</td></tr>
        </table>
        <table class="sq-top" width="100%" cellpadding="0" cellspacing="0">
            <tr>
                <td class="sq-logo" width="24%" valign="middle">{{company_logo}}</td>
                <td class="sq-brand" width="46%" valign="middle">
                    <div class="sq-company">{{company_name}}</div>
                    <div class="sq-muted">{{company_address}}</div>
                    <div class="sq-muted">{{company_gst}}</div>
                </td>
                <td class="sq-contact" width="30%" valign="middle">
                    <div class="sq-contact-line">{{company_email}}</div>
                    <div class="sq-contact-line">{{company_phone}}</div>
                    <div class="sq-contact-line">{{company_website}}</div>
                </td>
            </tr>
        </table>
        <table class="sq-rule" width="100%" cellpadding="0" cellspacing="0">
            <tr><td bgcolor="#1d4ed8" style="font-size:2px;line-height:2px;height:2px;">&nbsp;</td></tr>
        </table>
    </div>

    <div class="qt-page-body">
        {{subject_block}}

        <table class="sq-titlebar" width="100%" cellpadding="0" cellspacing="0">
            <tr>
                <td width="100%" valign="bottom" class="sq-right">
                    <span class="sq-doc-label">Quotation No.</span>
                    <strong class="sq-quote-no">{{quotation_number}}</strong>
                </td>
            </tr>
        </table>

        <table class="sq-parties" width="100%" cellpadding="0" cellspacing="0">
            <tr>
                <td class="sq-bill" width="54%" valign="top">
                    <div class="sq-label">Bill To</div>
                    <div class="sq-customer">{{customer_name}}</div>
                    <div class="sq-line">{{customer_company}}</div>
                    <div class="sq-line">{{customer_address}}</div>
                    <div class="sq-line">Email: {{customer_email}}</div>
                    <div class="sq-line">Phone: {{customer_phone}}</div>
                    <div class="sq-line">{{customer_gst}}</div>
                </td>
                <td width="4%">&nbsp;</td>
                <td class="sq-meta-panel" width="42%" valign="top">
                    <table class="sq-meta" width="100%" cellpadding="0" cellspacing="0">
                        <tr>
                            <td class="sq-meta-k">Date</td>
                            <td class="sq-meta-v">{{quotation_date}}</td>
                        </tr>
                        <tr>
                            <td class="sq-meta-k">Valid Until</td>
                            <td class="sq-meta-v">{{quotation_expiry_date}}</td>
                        </tr>
                        <tr>
                            <td class="sq-meta-k">Prepared By</td>
                            <td class="sq-meta-v">{{sales_person}}</td>
                        </tr>
                        <tr>
                            <td class="sq-meta-k">Proposal Version</td>
                            <td class="sq-meta-v">{{proposal_version}}</td>
                        </tr>
                        <tr>
                            <td class="sq-meta-k">Status</td>
                            <td class="sq-meta-v">{{quotation_status}}</td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

        <div class="sq-section">{{project_detail_block}}</div>
        <div class="sq-section">{{quote_summary_block}}</div>

        <div class="qt-items-wrap">{{items}}</div>

        <table class="sq-bottom" width="100%" cellpadding="0" cellspacing="0">
            <tr>
                <td class="sq-words" width="50%" valign="top">
                    <div class="sq-label">Amount in Words</div>
                    <div class="sq-words-text">{{amount_in_words}}</div>
                </td>
                <td width="4%">&nbsp;</td>
                <td class="sq-totals-wrap" width="46%" valign="top">
                    <table class="sq-totals" width="100%" cellpadding="0" cellspacing="0">
                        <tr>
                            <td>Subtotal</td>
                            <td class="sq-right">{{subtotal}}</td>
                        </tr>
                        <tr>
                            <td>Discount</td>
                            <td class="sq-right">{{discount}}</td>
                        </tr>
                        {{tax_row}}
                        {{shipping_row}}
                        <tr class="sq-grand">
                            <td><strong>Grand Total</strong></td>
                            <td class="sq-right"><strong>{{grand_total}}</strong></td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

        <div class="sq-section">{{notes_block}}</div>
        <div class="sq-section">{{bank_details_block}}</div>
        <div class="sq-section">{{terms_block}}</div>
        <div class="sq-section">{{closing_block}}</div>
    </div>

    <div class="qt-page-footer">
        <table class="sq-footer" width="100%" cellpadding="0" cellspacing="0">
            <tr>
                <td class="sq-footer-left" width="70%" valign="middle" style="text-align:left;">
                    {{footer_left}}
                </td>
                <td class="sq-footer-right" width="30%" valign="middle">
                    Page {PAGENO} of {nbpg}
                </td>
            </tr>
        </table>
    </div>
</div>
HTML;
    }

    public static function studioQuoteCss(): string
    {
        return <<<'CSS'
@page { size: A4; margin: 0; }
* { box-sizing: border-box; }
html, body {
    margin: 0;
    padding: 0;
    background: #f1f5f9;
    color: #1e293b;
    font-family: DejaVu Sans, Helvetica, Arial, sans-serif;
    font-size: 12px;
    line-height: 1.55;
}
.qt-page {
    width: 210mm;
    min-height: 297mm;
    margin: 0 auto;
    padding: 0;
    background: #ffffff;
    box-shadow: none;
    display: block;
    position: relative;
    overflow: visible;
}
.qt-page-header,
.qt-page-footer,
.qt-page-body {
    width: 100%;
    display: block;
    position: static;
    clear: both;
    float: none;
}
.qt-page-body { padding: 10px 14px 14px 14px; }

.sq-accent { width: 100%; background: #1d4ed8; border-collapse: collapse; }
.sq-accent td { height: 6px; font-size: 6px; line-height: 6px; padding: 0; }

.sq-top { width: 100%; border-collapse: collapse; margin: 0; }
.sq-logo { padding: 12px 10px 10px 14px; vertical-align: middle; }
.sq-logo img { max-width: 130px; max-height: 50px; }
.sq-brand { padding: 12px 10px 10px 8px; vertical-align: middle; }
.sq-company {
    font-size: 15px;
    font-weight: bold;
    color: #0f172a;
    margin: 0 0 3px 0;
    letter-spacing: 0.2px;
}
.sq-muted { color: #64748b; font-size: 10px; line-height: 1.45; margin: 0; }
.sq-contact {
    padding: 12px 14px 10px 10px;
    vertical-align: middle;
    text-align: right;
}
.sq-contact-line {
    color: #334155;
    font-size: 10px;
    line-height: 1.55;
    margin: 0;
}
.sq-rule { margin: 0; width: 100%; border-collapse: collapse; }

.sq-titlebar {
    width: 100%;
    border-collapse: collapse;
    margin: 0 0 12px 0;
}
.sq-doc-label {
    font-size: 11px;
    font-weight: bold;
    letter-spacing: 1.2px;
    text-transform: uppercase;
    color: #1d4ed8;
    margin-right: 8px;
}
.sq-right { text-align: right; }
.sq-k {
    display: inline;
    color: #64748b;
    font-size: 9px;
    text-transform: uppercase;
    letter-spacing: 0.4px;
    margin-right: 6px;
}
.sq-quote-no {
    font-size: 12px;
    color: #0f172a;
    letter-spacing: 0.2px;
}

.sq-parties,
.sq-bottom {
    width: 100%;
    border-collapse: collapse;
    margin: 0 0 14px 0;
    clear: both;
}
.sq-bill {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-left: 4px solid #1d4ed8;
    padding: 12px 14px;
    vertical-align: top;
}
.sq-meta-panel {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    padding: 10px 12px;
    vertical-align: top;
}
.sq-label {
    font-size: 10px;
    font-weight: bold;
    letter-spacing: 0.8px;
    text-transform: uppercase;
    color: #1d4ed8;
    margin: 0 0 7px 0;
}
.sq-customer {
    font-size: 13px;
    font-weight: bold;
    color: #0f172a;
    margin: 0 0 4px 0;
}
.sq-line {
    font-size: 11px;
    color: #475569;
    line-height: 1.5;
    margin: 0;
}
.sq-meta { width: 100%; border-collapse: collapse; }
.sq-meta td {
    padding: 6px 0;
    border-bottom: 1px solid #eef2f7;
    font-size: 11px;
    vertical-align: middle;
}
.sq-meta tr:last-child td { border-bottom: 0; }
.sq-meta-k { width: 48%; color: #64748b; }
.sq-meta-v { width: 52%; color: #0f172a; text-align: right; font-weight: bold; }

.sq-section {
    width: 100%;
    clear: both;
    float: none;
    position: static;
    display: block;
    margin: 0 0 12px 0;
    overflow: visible;
}

.qt-subject-banner {
    text-align: center;
    border: 0;
    padding: 8px 6px 4px 6px;
    margin: 0 0 8px 0;
    width: 100%;
    clear: both;
}
.qt-subject-banner-text {
    color: #1d4ed8;
    font-size: 17px;
    font-weight: bold;
    line-height: 1.35;
    text-decoration: none;
    border: 0;
}
.qt-quote-summary { text-align: center; margin: 0 0 10px 0; clear: both; }
.qt-quote-summary-title { color: #1d4ed8; font-size: 16px; font-weight: bold; margin: 0 0 8px 0; line-height: 1.35; text-decoration: none; }
.qt-quote-summary-intro { text-align: left; color: #222222; font-size: 12px; margin: 0 0 8px 0; line-height: 1.55; text-decoration: none; }
.qt-quote-summary-heading { color: #111111; font-size: 13px; font-weight: bold; margin: 0 0 8px 0; text-decoration: none; }

.qt-items-wrap { margin: 0 0 14px 0; clear: both; width: 100%; }
.qt-items-table {
    width: 100%;
    table-layout: fixed;
    border-collapse: collapse;
    font-size: 11px;
}
.qt-items-table th {
    background: #1d4ed8;
    color: #ffffff;
    padding: 9px 6px;
    border: 1px solid #1d4ed8;
    font-weight: bold;
    font-size: 11px;
}
.qt-items-table td {
    padding: 8px 6px;
    border-bottom: 1px solid #e2e8f0;
    vertical-align: top;
    color: #1e293b;
    word-wrap: break-word;
    font-size: 11px;
}
.qt-items-table tbody tr:nth-child(even) td { background: #f8fafc; }
.qt-item-name { font-weight: bold; color: #0f172a; }
.qt-item-meta { color: #64748b; font-size: 9px; margin-top: 2px; }
.qt-c, .sq-c { text-align: center; }
.qt-l, .sq-l { text-align: left; }
.qt-r, .qt-right, .sq-right { text-align: right; }

.sq-words {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    padding: 12px 14px;
    vertical-align: top;
}
.sq-words-text {
    font-size: 11px;
    font-weight: bold;
    color: #0f172a;
    line-height: 1.5;
}
.sq-totals {
    width: 100%;
    border-collapse: collapse;
    background: #ffffff;
    border: 1px solid #e2e8f0;
}
.sq-totals td {
    padding: 8px 12px;
    border-bottom: 1px solid #eef2f7;
    font-size: 11px;
    color: #475569;
}
.sq-totals tr:last-child td { border-bottom: 0; }
.sq-grand td {
    background: #1d4ed8;
    color: #ffffff;
    font-size: 13px;
    padding-top: 10px;
    padding-bottom: 10px;
}

.sq-footer {
    width: 100%;
    border-collapse: collapse;
    background: #0f172a;
    margin-top: 4px;
}
.sq-footer-left,
.sq-footer-right {
    padding: 9px 14px;
    color: #94a3b8;
    font-size: 9px;
}
.sq-footer-right {
    text-align: right;
    font-weight: bold;
    color: #ffffff;
}

.qt-section,
.ms-block {
    width: 100%;
    clear: both;
    float: none;
    position: static;
    display: block;
    margin: 0 0 10px 0;
}
.qt-section-body,
.ms-block-body {
    font-size: 12px;
    line-height: 1.55;
    color: #334155;
}
.qt-closing {
    width: 100%;
    font-size: 11px;
    color: #1e293b;
    line-height: 1.5;
    margin-top: 12px;
    clear: both;
}
.qt-closing-sincerely { margin: 0 0 12px 0; }
.qt-closing-name { font-weight: bold; }
.qt-closing-role,
.qt-closing-company { color: #64748b; }

@media print {
    html, body { background: #ffffff !important; }
    .qt-page {
        width: 100% !important;
        min-height: auto !important;
        box-shadow: none !important;
        display: block !important;
    }
}
CSS;
    }

    /**
     * Standard Quotation - 2 — geometric orange/charcoal header, white logo panel.
     */
    public static function standardQuotationTwoHtml(): string
    {
        return <<<'HTML'
<div class="qt-page">
    <div class="qt-page-header">
        <table class="sq2-banner" width="100%" cellpadding="0" cellspacing="0">
            <tr>
                <td class="sq2-left" width="34%" valign="middle" bgcolor="#ffffff">
                    <div class="sq2-logo-wrap">{{company_logo}}</div>
                    <div class="sq2-brand">{{company_name}}</div>
                    <div class="sq2-tagline">{{company_gst}}</div>
                </td>
                <td class="sq2-sash" width="10%" valign="middle" bgcolor="#f36c21">
                    {{sq2_sash}}
                </td>
                <td class="sq2-right" width="56%" valign="middle" bgcolor="#2f3237">
                    <div class="sq2-doc-title">QUOTATION</div>
                    <div class="sq2-doc-no">No. <strong>{{quotation_number}}</strong></div>
                </td>
            </tr>
        </table>

        <table class="sq2-subhead" width="100%" cellpadding="0" cellspacing="0">
            <tr>
                <td class="sq2-sub-left" width="58%" valign="top">
                    <div class="sq2-muted">{{company_address}}</div>
                </td>
                <td class="sq2-sub-right" width="42%" valign="top">
                    {{company_email_line}}
                    {{company_phone_line}}
                    {{company_website_line}}
                </td>
            </tr>
        </table>
    </div>

    <div class="qt-page-body">
        {{subject_block}}

        <table class="qt-info" width="100%" cellpadding="0" cellspacing="0">
            <tr>
                <td class="qt-box" width="52%" valign="top">
                    <div class="qt-label">BILL TO</div>
                    <div class="qt-customer">{{customer_name}}</div>
                    <div>{{customer_company}}</div>
                    <div>{{customer_address}}</div>
                    <div>Email: {{customer_email}}</div>
                    <div>Phone: {{customer_phone}}</div>
                    <div>{{customer_gst}}</div>
                </td>
                <td width="4%">&nbsp;</td>
                <td class="qt-box" width="44%" valign="top">
                    <table class="qt-meta" width="100%" cellpadding="0" cellspacing="0">
                        <tr>
                            <td>Quotation Date</td>
                            <td class="qt-right"><strong>{{quotation_date}}</strong></td>
                        </tr>
                        <tr>
                            <td>Valid Until</td>
                            <td class="qt-right"><strong>{{quotation_expiry_date}}</strong></td>
                        </tr>
                        <tr>
                            <td>Prepared By</td>
                            <td class="qt-right"><strong>{{sales_person}}</strong></td>
                        </tr>
                        <tr>
                            <td>Proposal Version</td>
                            <td class="qt-right"><strong>{{proposal_version}}</strong></td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

        {{project_detail_block}}
        {{quote_summary_block}}

        <div class="qt-items-wrap">{{items}}</div>

        <table class="qt-bottom" width="100%" cellpadding="0" cellspacing="0">
            <tr>
                <td class="qt-box qt-words" width="50%" valign="top">
                    <div class="qt-label">AMOUNT IN WORDS</div>
                    <div class="qt-words-text">{{amount_in_words}}</div>
                </td>
                <td width="4%">&nbsp;</td>
                <td width="46%" valign="top">
                    <table class="qt-totals-box" width="100%" cellpadding="0" cellspacing="0">
                        <tr>
                            <td>Subtotal</td>
                            <td class="qt-right">{{subtotal}}</td>
                        </tr>
                        <tr>
                            <td>Discount</td>
                            <td class="qt-right">{{discount}}</td>
                        </tr>
                        {{tax_row}}
                        {{shipping_row}}
                        <tr class="qt-grand">
                            <td><strong>Grand Total</strong></td>
                            <td class="qt-right"><strong>{{grand_total}}</strong></td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

        {{notes_block}}
        {{bank_details_block}}
        {{terms_block}}
        {{closing_block}}
    </div>

    <div class="qt-page-footer">
        <table class="qt-footer" width="100%" cellpadding="0" cellspacing="0">
            <tr>
                <td class="qt-footer-left" width="70%" valign="middle" style="text-align:left;">
                    {{footer_left}}
                </td>
                <td class="qt-footer-right" width="30%" valign="middle" style="text-align:right;">
                    Page {PAGENO} of {nbpg}
                </td>
            </tr>
        </table>
    </div>
</div>
HTML;
    }

    public static function standardQuotationTwoCss(): string
    {
        return <<<'CSS'
@page { size: A4; margin: 0; }
* { box-sizing: border-box; }
html, body {
    margin: 0;
    padding: 0;
    background: #ececec;
    color: #222222;
    font-family: DejaVu Sans, Helvetica, Arial, sans-serif;
    font-size: 11px;
    line-height: 1.45;
}
.qt-page {
    width: 210mm;
    min-height: 297mm;
    margin: 0 auto;
    padding: 0;
    background: #ffffff;
    box-shadow: none;
    display: block;
}
.qt-page-header,
.qt-page-body,
.qt-page-footer {
    width: 100%;
    display: block;
    clear: both;
}
.qt-page-body { padding: 12px 14px 14px 14px; }

.sq2-banner {
    width: 100%;
    border-collapse: collapse;
    margin: 0;
}
.sq2-left {
    background: #ffffff !important;
    padding: 16px 14px 14px 16px;
    vertical-align: middle;
    border: 0;
}
.sq2-logo-wrap { margin: 0 0 6px 0; }
.sq2-logo-wrap img { max-width: 140px; max-height: 52px; }
.sq2-brand {
    font-size: 13px;
    font-weight: bold;
    color: #f36c21;
    text-transform: uppercase;
    letter-spacing: 0.4px;
    margin: 0 0 2px 0;
}
.sq2-tagline {
    font-size: 9px;
    color: #666666;
    margin: 0;
}
.sq2-sash {
    background: #f36c21 !important;
    padding: 0;
    text-align: center;
    vertical-align: middle;
    overflow: hidden;
}
.sq2-sash img,
.sq2-sash-img {
    display: block;
    width: 100%;
    max-width: 110px;
    height: 120px;
    margin: 0 auto;
}
.sq2-right {
    background: #2f3237 !important;
    padding: 22px 24px;
    vertical-align: middle;
    text-align: right;
}
.sq2-doc-title {
    font-size: 28px;
    font-weight: bold;
    color: #ffffff;
    letter-spacing: 2px;
    text-transform: uppercase;
    margin: 0 0 4px 0;
    line-height: 1.1;
}
.sq2-doc-no {
    font-size: 11px;
    color: #f3f4f6;
}
.sq2-doc-no strong { color: #ffffff; }

.sq2-subhead {
    width: 100%;
    border-collapse: collapse;
    margin: 0 0 8px 0;
    border-bottom: 2px solid #f36c21;
}
.sq2-sub-left {
    padding: 8px 14px 10px 16px;
    vertical-align: top;
}
.sq2-sub-right {
    padding: 8px 16px 10px 8px;
    vertical-align: top;
    text-align: right;
}
.sq2-muted {
    color: #666666;
    font-size: 10px;
    line-height: 1.45;
}
.qt-contact-line { width: auto; border-collapse: collapse; margin: 0 0 5px auto; }
.qt-contact-icon-cell { width: 20px; padding: 1px 8px 1px 0; vertical-align: middle; line-height: 12px; }
.qt-contact-icon { display: block; width: 12px; height: 12px; margin: 0; padding: 0; border: 0; }
.qt-contact-text { vertical-align: middle; text-align: left; font-size: 10px; color: #444444; line-height: 1.35; padding: 1px 0; }
.qt-contact-text { vertical-align: middle; font-size: 10px; color: #444444; }

.qt-info, .qt-bottom, .qt-meta, .qt-totals-box, .qt-footer { width: 100%; border-collapse: collapse; }
.qt-info { margin: 0 0 12px 0; }
.qt-box {
    border: 1px solid #d9d9d9;
    padding: 11px;
    vertical-align: top;
    background: #ffffff;
}
.qt-label {
    font-size: 9px;
    font-weight: bold;
    letter-spacing: 0.8px;
    color: #f36c21;
    margin: 0 0 6px 0;
    text-transform: uppercase;
}
.qt-customer { font-size: 13px; font-weight: bold; color: #111111; margin: 0 0 3px 0; }
.qt-meta td {
    padding: 5px 0;
    border-bottom: 1px solid #ececec;
    font-size: 10px;
    vertical-align: middle;
}
.qt-meta tr:last-child td { border-bottom: 0; }
.qt-right { text-align: right; }

.qt-subject-banner { text-align: center; margin: 0 0 10px 0; border: 0; padding: 6px 0; }
.qt-subject-banner-text { color: #2f3237; font-size: 16px; font-weight: bold; line-height: 1.35; }
.qt-quote-summary-title { color: #f36c21; font-size: 16px; font-weight: bold; margin: 0 0 8px 0; }
.qt-quote-summary-intro { text-align: left; color: #222222; font-size: 11px; margin: 0 0 10px 0; line-height: 1.45; }
.qt-quote-summary-heading { color: #111111; font-size: 13px; font-weight: bold; margin: 0 0 8px 0; }

.qt-items-wrap { margin: 0 0 12px 0; }
.qt-items-table { width: 100%; table-layout: fixed; border-collapse: collapse; font-size: 10px; }
.qt-items-table th {
    background: #2f3237;
    color: #ffffff;
    padding: 8px 6px;
    border: 1px solid #2f3237;
    font-weight: bold;
}
.qt-items-table td {
    padding: 8px 6px;
    border-bottom: 1px solid #e0e0e0;
    vertical-align: top;
    color: #222222;
}
.qt-item-meta { color: #777777; font-size: 9px; margin-top: 2px; }
.qt-c { text-align: center; }
.qt-l { text-align: left; }
.qt-r, .qt-right { text-align: right; }

.qt-words-text { font-size: 11px; font-weight: bold; color: #111111; line-height: 1.5; }
.qt-totals-box td {
    padding: 5px 0;
    border-bottom: 1px solid #ececec;
    font-size: 10px;
    color: #444444;
}
.qt-totals-box tr:last-child td { border-bottom: 0; }
.qt-grand td {
    border-bottom: 0;
    padding-top: 8px;
    color: #f36c21;
    font-size: 13px;
    font-weight: bold;
}

.qt-section-body, .ms-block-body { color: #444444; font-size: 11px; line-height: 1.5; }
.qt-closing { width: 100%; font-size: 10px; color: #1e293b; line-height: 1.45; margin-top: 12px; }

.qt-footer {
    width: 100%;
    border-collapse: collapse;
    border-top: 2px solid #f36c21;
    margin-top: 4px;
}
.qt-footer td,
.qt-footer-left,
.qt-footer-right {
    padding: 8px 14px;
    color: #777777;
    font-size: 8px;
}
.qt-footer-left { text-align: left; }
.qt-footer-right { text-align: right; white-space: nowrap; }

@media print {
    html, body { background: #ffffff !important; }
    .qt-page { width: 100% !important; min-height: auto !important; box-shadow: none !important; }
}
CSS;
    }

    /**
     * Ensure only one default active template.
     */
    public function setAsDefault(QuotationTemplate $template): void
    {
        DB::transaction(function () use ($template) {
            QuotationTemplate::query()
                ->where('id', '!=', $template->id)
                ->where('is_default', true)
                ->update(['is_default' => false]);

            $template->is_default = true;
            if ($template->status !== 'active') {
                $template->status = 'active';
            }
            $template->updated_by = auth()->id();
            $template->save();
        });
    }

    public function createVersion(QuotationTemplate $template, ?int $userId = null): QuotationTemplateVersion
    {
        return QuotationTemplateVersion::create([
            'quotation_template_id' => $template->id,
            'version' => (int) $template->version,
            'html_content' => $template->html_content,
            'css_content' => $template->css_content,
            'created_by' => $userId ?? auth()->id(),
            'created_at' => now(),
        ]);
    }

    /**
     * Sanitize HTML: strip scripts and event handlers. No PHP/server code.
     */
    public function sanitizeHtml(?string $html): string
    {
        $html = (string) ($html ?? '');
        $html = preg_replace('#<\s*(script|iframe|object|embed|form|link|meta|base)[^>]*>.*?<\s*/\s*\1\s*>#is', '', $html) ?? $html;
        $html = preg_replace('#<\s*(script|iframe|object|embed|form|link|meta|base)[^>]*/?\s*>#is', '', $html) ?? $html;
        $html = preg_replace('/\son\w+\s*=\s*(".*?"|\'.*?\'|[^\s>]+)/i', '', $html) ?? $html;
        $html = preg_replace('/(href|src)\s*=\s*([\'"])\s*javascript:[^\'"]*\2/i', '$1="#"', $html) ?? $html;
        $html = str_replace(['<?', '?>', '@php', 'Blade::'], ['&lt;?', '?&gt;', '&#64;php', 'Blade&#58;&#58;'], $html);

        return $html;
    }

    /**
     * Sanitize rich-editor HTML for storage (TinyMCE / legacy Quill).
     * Keeps formatting compatible with template preview + mPDF.
     */
    public function sanitizeRichEditorHtml(?string $html): string
    {
        $html = $this->sanitizeHtml($html);
        if (trim($html) === '') {
            return '';
        }

        $html = preg_replace('/\sdata-mce-[a-z0-9_-]+=(["\'])[^"\']*\1/i', '', $html) ?? $html;
        $html = preg_replace('/\scontenteditable=(["\'])[^"\']*\1/i', '', $html) ?? $html;
        $html = preg_replace('#<span[^>]*\bql-ui\b[^>]*>.*?</span>#is', '', $html) ?? $html;

        $allowed = '<p><br><strong><b><em><i><u><s><strike><sub><sup><span><a>'
            . '<ul><ol><li><blockquote><hr>'
            . '<h1><h2><h3><h4><h5><h6>'
            . '<table><thead><tbody><tfoot><tr><th><td><caption><colgroup><col>'
            . '<img><div>';
        $html = strip_tags($html, $allowed);

        $html = preg_replace_callback('/<([a-z1-6]+)\b([^>]*)>/i', function (array $m) {
            $tag = strtolower($m[1]);
            $attrs = $m[2];
            if (preg_match('/\sbgcolor=(["\']?)([^"\'\s>]+)\1/i', $attrs, $bm)) {
                $bg = trim($bm[2]);
                if ($bg !== '' && !preg_match('/background(-color)?\s*:/i', $attrs)) {
                    if (preg_match('/\sstyle=(["\'])(.*?)\1/i', $attrs, $sm)) {
                        $newStyle = rtrim($sm[2], ';') . ';background-color:' . $bg . ';';
                        $attrs = preg_replace('/\sstyle=(["\'])(.*?)\1/i', ' style="' . $newStyle . '"', $attrs);
                    } else {
                        $attrs .= ' style="background-color:' . $bg . ';"';
                    }
                }
            }

            return '<' . $tag . $attrs . '>';
        }, $html) ?? $html;

        $html = preg_replace_callback('/\sstyle=(["\'])(.*?)\1/is', function (array $m) {
            $style = $m[2];
            if (preg_match('/expression|javascript|behavior|import/i', $style)) {
                return '';
            }
            $keep = [];
            foreach (explode(';', $style) as $decl) {
                $decl = trim($decl);
                if ($decl === '' || !str_contains($decl, ':')) {
                    continue;
                }
                [$prop, $val] = array_map('trim', explode(':', $decl, 2));
                $propLower = strtolower($prop);
                if (in_array($propLower, [
                    'color', 'background', 'background-color',
                    'font-family', 'font-size', 'font-weight', 'font-style', 'font-variant',
                    'line-height', 'letter-spacing', 'word-spacing',
                    'text-align', 'text-decoration', 'text-indent', 'text-transform',
                    'vertical-align', 'white-space',
                    'margin', 'margin-top', 'margin-right', 'margin-bottom', 'margin-left',
                    'padding', 'padding-top', 'padding-right', 'padding-bottom', 'padding-left',
                    'width', 'height', 'max-width', 'min-width', 'max-height', 'min-height',
                    'border', 'border-top', 'border-right', 'border-bottom', 'border-left',
                    'border-color', 'border-width', 'border-style', 'border-collapse', 'border-radius',
                    'list-style', 'list-style-type', 'list-style-position',
                    'display', 'float', 'clear',
                ], true)) {
                    $keep[] = $propLower . ':' . $val;
                }
            }

            return $keep === [] ? '' : ' style="' . e(implode(';', $keep)) . '"';
        }, $html) ?? $html;

        return trim($html);
    }

    public function sanitizeCss(?string $css): string
    {
        $css = (string) ($css ?? '');
        $css = preg_replace('/expression\s*\(/i', 'invalid(', $css) ?? $css;
        $css = preg_replace('/javascript\s*:/i', 'invalid:', $css) ?? $css;
        $css = preg_replace('/@import\b/i', '/*@import*/', $css) ?? $css;
        $css = preg_replace('/behavior\s*:/i', 'invalid:', $css) ?? $css;

        return $css;
    }

    public function getDefaultActiveTemplate(): ?QuotationTemplate
    {
        return QuotationTemplate::query()
            ->active()
            ->where('is_default', true)
            ->first()
            ?: QuotationTemplate::query()->active()->orderBy('id')->first();
    }

    /**
     * Resolve template + version for a new quotation.
     *
     * @return array{template: QuotationTemplate, version: QuotationTemplateVersion}|null
     */
    public function resolveTemplateForQuotation(?int $templateId): ?array
    {
        $template = null;
        if ($templateId) {
            $template = QuotationTemplate::query()->active()->find($templateId);
        }
        if (!$template) {
            $template = $this->getDefaultActiveTemplate();
        }
        if (!$template) {
            return null;
        }

        $version = $template->versions()->where('version', $template->version)->first()
            ?: $this->createVersion($template);

        return ['template' => $template, 'version' => $version];
    }

    public function buildSampleData(): array
    {
        $company = Company::first();
        $logoHtml = $this->companyLogoHtml($company, 56);

        $currency = optional($company)->currency_symbol ?? '₹';
        $sampleRows = [
            [
                'sku' => 'SKU-001',
                'name' => 'CRM Professional License',
                'description' => 'Annual subscription',
                'qty' => 2,
                'price' => 4000,
                'discount' => 0,
                'discount_percent' => 0,
                'line_amount' => 8000,
                'tax' => 18,
                'amount' => 8000,
            ],
            [
                'sku' => 'SKU-002',
                'name' => 'Implementation Support',
                'description' => 'Onboarding package',
                'qty' => 1,
                'price' => 2000,
                'discount' => 200,
                'discount_percent' => 10,
                'line_amount' => 2000,
                'tax' => 18,
                'amount' => 2124,
            ],
        ];
        $notes = 'Thank you for considering our proposal.';
        $terms = 'This quotation is valid until the expiry date. 50% advance is required to start work; balance is due on completion.';

        return $this->aliasPlaceholders([
            'company_name' => $company->name ?? 'Isarva Infotech Private Limited',
            'company_logo' => $logoHtml,
            'company_address' => trim(collect([
                $company->address ?? 'Near HP Petrol Pump, Main Road, Bajpe',
                $company->district ?? null,
                $company->city ?? 'Mangalore',
                $company->state ?? null,
                $company->pincode ?? '574142',
                $company->country ?? 'India',
            ])->filter()->implode(', ')),
            'company_phone' => $company->phone ?? $company->mobile ?? '+91 98806 06087',
            'company_email' => $company->email ?? 'info@isarvait.com',
            'company_website' => $this->formatWebsiteForDisplay($company->website ?? 'www.isarvait.com'),
            'company_gst' => 'GSTIN: ' . ($company->gstin ?? '29AAGCI3459Q1ZQ'),
            'customer_name' => 'Rahul Sharma',
            'customer_company' => 'ABC Technologies',
            'customer_address' => '45 Tech Hub, Mumbai 400001',
            'customer_phone' => '+91 91234 56789',
            'customer_email' => 'rahul@abctech.com',
            'customer_gst' => 'GSTIN: 27BBBBB0000B1Z5',
            'quotation_number' => 'QT-0001',
            'quotation_date' => '24-08-2026',
            'quotation_expiry_date' => '24-09-2026',
            'quotation_reference' => 'DEAL-1042',
            'quotation_status' => 'Draft',
            'proposal_version' => 'v1',
            'sales_person' => 'Priya Nair',
            'sales_person_email' => 'priya@isarva.in',
            'sales_person_phone' => '+91 99887 76655',
            'subtotal' => $currency . ' 10,000.00',
            'discount' => $currency . ' 200 (2%)',
            'tax' => $currency . ' 1,800.00',
            'shipping' => $currency . ' 0.00',
            'grand_total' => $currency . ' 11,800.00',
            'amount_in_words' => 'Rupees Eleven Thousand Eight Hundred Only',
            'terms_conditions' => $terms,
            'notes' => $notes,
            'project_detail' => 'Website redesign and CMS setup for the client portal.',
            'payment_terms' => '50% advance, 50% on delivery.',
            'signature' => 'Priya Nair',
            'designation' => 'Sales Manager',
            'closing_block' => $this->renderClosingSignoffHtml([
                'signature' => 'Priya Nair',
                'designation' => 'Sales Manager',
                'company_name' => $company->name ?? 'Isarva Infotech Private Limited',
            ]),
            'bank_details_block' => $this->renderBankDetailsBlock($company, true),
            'quote_summary_block' => $this->renderQuoteSummaryBlock(
                'Website Re-Design',
                'ABC Technologies',
                'Quotation for Website Re-Design'
            ),
            'subject' => 'ABC Technologies Website Re-Design Proposal',
            'subject_block' => $this->renderSubjectBannerBlock('ABC Technologies Website Re-Design Proposal'),
            'quotation_title' => 'Quotation for Website Re-Design',
            'items' => $this->renderItemsTable($sampleRows, false, $currency),
            'item_rows' => $this->renderItemRows($sampleRows, false, $currency),
            'notes_block' => $this->labeledBlock('REMARKS', $notes),
            'terms_block' => $this->labeledBlock('TERMS &amp; CONDITIONS', $terms),
            'project_detail_block' => $this->labeledBlock('PROJECT DETAIL', 'Website redesign and CMS setup for the client portal.'),
        ]);
    }

    public function buildDataFromQuotation(Quotation $quotation, ?Company $company = null): array
    {
        $company = $company ?: Company::first();
        $quotation->loadMissing(['people', 'organization', 'owner', 'items.product', 'addresses', 'deal']);

        $billing = $quotation->addresses->firstWhere('address_type', 'billing');
        $customerAddress = $billing
            ? trim(collect([
                $billing->flat_no,
                $billing->street,
                $billing->city,
                $billing->state,
                $billing->country,
                $billing->zip,
            ])->filter()->implode(', '))
            : (optional($quotation->organization)->address ?? '');

        $logoHtml = $this->companyLogoHtml($company, 56);

        $person = $quotation->people;
        $customerName = trim(optional($person)->first_name . ' ' . optional($person)->last_name) ?: 'Customer';
        $owner = $quotation->owner;
        $currency = $quotation->currency_symbol ?: ($company->currency_symbol ?? '₹');
        $currencyCode = $quotation->currency_code ?: ($company->currency_code ?? 'INR');

        $fmt = function ($n) use ($currency) {
            $currency = trim((string) $currency);
            $formatted = format_smart_number($n);
            return $currency !== '' ? $currency . ' ' . $formatted : $formatted;
        };

        $itemRows = [];
        $showItemTaxColumn = false;
        foreach ($quotation->items as $item) {
            $product = $item->product;
            $isTaxable = (bool) optional($product)->tax_status;
            $itemTax = (float) ($item->tax ?? 0);
            if ($isTaxable || $itemTax > 0) {
                $showItemTaxColumn = true;
            }
            $itemRows[] = [
                'sku' => $product->product_code ?? '',
                'name' => $product->product_name ?? 'Item',
                'description' => $product->product_description ?? '',
                'qty' => $item->quantity,
                'price' => (float) $item->price,
                'discount' => (float) ($item->discount ?? 0),
                'discount_percent' => (float) ($item->discount_percent ?? 0),
                'line_amount' => (float) ($item->amount ?? (((float) ($item->quantity ?? 0)) * ((float) ($item->price ?? 0)))),
                'tax' => $itemTax,
                'is_taxable' => $isTaxable,
                'amount' => (float) ($item->total ?? $item->amount ?? 0),
            ];
        }

        $overallTax = (float) ($quotation->tax ?? 0);
        $adjustment = (float) ($quotation->adjustment ?? 0);
        $overallDiscount = (float) ($quotation->discount ?? 0);
        $overallDiscountPct = $quotation->discount_percent !== null
            ? (float) $quotation->discount_percent
            : ((float) ($quotation->subtotal ?? 0) > 0 && $overallDiscount > 0
                ? round(($overallDiscount / (float) $quotation->subtotal) * 100, 2)
                : 0);
        $showTaxRow = $overallTax > 0;
        $showShippingRow = $adjustment != 0.0;
        $taxRowHtml = $showTaxRow
            ? '<tr><td>Tax</td><td class="qt-right ms-right t3-right" style="text-align:right;">' . $fmt($overallTax) . '</td></tr>'
            : '';
        $shippingRowHtml = $showShippingRow
            ? '<tr><td>Shipping / Adj.</td><td class="qt-right ms-right t3-right" style="text-align:right;">' . $fmt($adjustment) . '</td></tr>'
            : '';


        $notes = $this->richTextForPdf((string) ($quotation->description ?? ''));
        $terms = $this->richTextForPdf((string) ($quotation->terms_conditions ?? ''));
        $projectDetail = $this->richTextForPdf((string) ($quotation->project_detail ?? ''));
        $termsPlain = trim(preg_replace('/\s+/', ' ', strip_tags((string) ($quotation->terms_conditions ?? ''))));
        $paymentTerms = $termsPlain !== ''
            ? \Illuminate\Support\Str::limit($termsPlain, 90)
            : '';
        $dealTitle = trim((string) (optional($quotation->deal)->title ?? ($quotation->subject ?? '')));
        $organizationName = trim((string) (optional($quotation->organization)->name ?? ''));
        $subject = trim((string) ($quotation->subject ?? ''));
        $quotationTitle = trim((string) ($quotation->quotation_title ?? ''));
        $subjectBannerText = $this->subjectBannerText($subject, $organizationName, $dealTitle);
        $gst = '';
        if ($company) {
            foreach (['gstin', 'gst_no', 'gst'] as $field) {
                $value = $company->getAttribute($field);
                if (!empty($value)) {
                    $gst = trim((string) $value);
                    break;
                }
            }
        }

        return $this->aliasPlaceholders([
            'company_name' => $company->name ?? config('app.name', 'CRM'),
            'company_logo' => $logoHtml,
            'company_address' => trim(collect([
                $company->address ?? null,
                $company->district ?? null,
                $company->city ?? null,
                $company->state ?? null,
                $company->pincode ?? null,
                $company->country ?? null,
            ])->filter()->implode(', ')),
            'company_phone' => $company->phone ?? $company->mobile ?? '',
            'company_email' => $company->email ?? '',
            'company_website' => $this->formatWebsiteForDisplay($company->website ?? ''),
            'company_gst' => $gst !== '' ? 'GSTIN: ' . $gst : '',
            'customer_name' => $customerName,
            'customer_company' => $organizationName,
            'customer_address' => $customerAddress,
            'customer_phone' => optional($person)->phone ?? optional($person)->mobile ?? '',
            'customer_email' => optional($person)->email ?? '',
            'customer_gst' => '',
            'quotation_number' => $quotation->quote_no ?? '',
            'quotation_date' => optional($quotation->created_at)->format('d M Y') ?? date('d M Y'),
            'quotation_expiry_date' => $quotation->valid_until
                ? \Carbon\Carbon::parse($quotation->valid_until)->format('d M Y')
                : '',
            'quotation_reference' => $dealTitle !== '' ? $dealTitle : ($quotation->subject ?? ''),
            'quotation_status' => $quotation->status ?? '',
            'proposal_version' => 'v' . max(1, (int) ($quotation->proposal_version ?? 1)),
            'sales_person' => optional($owner)->name ?? '',
            'sales_person_email' => optional($owner)->email ?? '',
            'sales_person_phone' => optional($owner)->phone ?? optional($owner)->mobile ?? '',
            'subtotal' => $fmt($quotation->subtotal ?? 0),
            'discount' => $this->discountLabel($overallDiscount, $overallDiscountPct, $currency),
            'tax' => $fmt($overallTax),
            'shipping' => $fmt($adjustment),
            'tax_row' => $taxRowHtml,
            'shipping_row' => $shippingRowHtml,
            'show_tax_row' => $showTaxRow ? '1' : '',
            'show_shipping_row' => $showShippingRow ? '1' : '',
            'footer_left' => 'Copyright © Isarva Infotech Pvt Ltd, All Rights Reserved',
            'grand_total' => $fmt($quotation->grand_total ?? 0),
            'amount_in_words' => $this->amountInWords((float) ($quotation->grand_total ?? 0), $company, $currencyCode),
            'terms_conditions' => $terms,
            'notes' => $notes,
            'project_detail' => $projectDetail,
            'payment_terms' => $paymentTerms,
            'signature' => optional($owner)->name ?? ($company->name ?? ''),
            'designation' => $this->designationForUser($owner),
            'closing_block' => $this->renderClosingSignoffHtml([
                'signature' => optional($owner)->name ?? ($company->name ?? ''),
                'designation' => $this->designationForUser($owner),
                'company_name' => $company->name ?? config('app.name', 'CRM'),
            ]),
            'bank_details_block' => $this->renderBankDetailsBlock(
                $company,
                (bool) ($quotation->show_bank_details ?? false)
            ),
            'quote_summary_block' => (($quotation->quotation_product_type ?? 'default') === 'product'
                ? $this->renderQuoteSummaryBlock($dealTitle, $organizationName, $quotationTitle)
                    . $this->renderProductPricingCard($itemRows[0] ?? [], $currency)
                : $this->renderQuoteSummaryBlock($dealTitle, $organizationName, $quotationTitle)),
            'subject' => $subjectBannerText,
            'subject_block' => $this->renderSubjectBannerBlock($subjectBannerText),
            'quotation_title' => $quotationTitle !== ''
                ? $quotationTitle
                : ($dealTitle !== '' ? 'Quotation for ' . $dealTitle : ''),
            'items' => ($quotation->quotation_product_type ?? 'default') === 'product'
                ? $this->renderProductItemsTable($itemRows, $currency, (float) ($quotation->grand_total ?? 0), true, $currencyCode)
                : $this->renderItemsTable($itemRows, true, $currency, $showItemTaxColumn),
            'item_rows' => ($quotation->quotation_product_type ?? 'default') === 'product'
                ? $this->renderProductItemRows($itemRows, $currency)
                : $this->renderItemRows($itemRows, true, $currency, $showItemTaxColumn),
            'notes_block' => $this->labeledBlock('REMARKS', $notes),
            'terms_block' => $this->labeledBlock('TERMS &amp; CONDITIONS', $terms),
            'project_detail_block' => $this->labeledBlock('PROJECT DETAIL', $projectDetail),
        ]);
    }

    /**
     * Build template placeholder data from quotation create/edit wizard form values.
     *
     * @param  array<string, mixed>  $payload
     */
    public function buildDataFromWizardDraft(array $payload, ?Company $company = null): array
    {
        $company = $company ?: Company::first();
        $currency = !empty($payload['currency_symbol']) ? $payload['currency_symbol'] : ($company->currency_symbol ?? '₹');
        $currencyCode = !empty($payload['currency_code']) ? $payload['currency_code'] : ($company->currency_code ?? 'INR');
        $fmt = function ($n) use ($currency) {
            $currency = trim((string) $currency);
            $formatted = format_smart_number($n);
            return $currency !== '' ? $currency . ' ' . $formatted : $formatted;
        };

        $owner = null;
        $ownerId = (int) ($payload['quotation_owner'] ?? 0);
        if ($ownerId > 0) {
            $owner = \App\Models\User::find($ownerId);
        }

        $person = null;
        $personId = (int) ($payload['person_id'] ?? 0);
        if ($personId > 0) {
            $person = \App\Models\Person::find($personId);
        }

        $customerName = $person
            ? trim(($person->first_name ?? '') . ' ' . ($person->last_name ?? ''))
            : trim((string) ($payload['people_id'] ?? 'Customer'));
        if ($customerName === '') {
            $customerName = 'Customer';
        }

        $organizationName = trim((string) ($payload['organization_id'] ?? ''));
        $dealTitle = trim((string) ($payload['deal_id'] ?? ''));
        $subject = trim((string) ($payload['subject'] ?? ''));
        $quotationTitle = trim((string) ($payload['quotation_title'] ?? ''));
        $productType = (string) ($payload['quotation_product_type'] ?? 'default');
        $subjectBannerText = $this->subjectBannerText($subject, $organizationName, $dealTitle);

        $customerAddress = trim(collect([
            $payload['billing_flat'] ?? null,
            $payload['billing_street'] ?? null,
            $payload['billing_city'] ?? null,
            $payload['billing_state'] ?? null,
            $payload['billing_country'] ?? null,
            $payload['billing_zip'] ?? null,
        ])->filter()->implode(', '));

        $itemRows = [];
        $showItemTaxColumn = false;
        $items = $payload['items'] ?? [];
        if (is_array($items)) {
            foreach ($items as $item) {
                if (!is_array($item)) {
                    continue;
                }
                $productId = (int) ($item['product_id'] ?? 0);
                if ($productId < 1) {
                    continue;
                }
                $product = \App\Models\Product::find($productId);
                $qty = (float) ($item['quantity'] ?? 0);
                $price = (float) ($item['price'] ?? 0);
                $discount = (float) ($item['discount'] ?? 0);
                $discountPct = (float) ($item['discount_percent'] ?? 0);
                $tax = (float) ($item['tax'] ?? 0);
                $amount = (float) ($item['amount'] ?? ($qty * $price));
                $total = (float) ($item['total'] ?? $amount);
                $isTaxable = (bool) optional($product)->tax_status;
                if ($isTaxable || $tax > 0) {
                    $showItemTaxColumn = true;
                }
                $itemRows[] = [
                    'sku' => $product->product_code ?? '',
                    'name' => $product->product_name ?? 'Item',
                    'description' => $product->product_description ?? '',
                    'qty' => $qty,
                    'price' => $price,
                    'discount' => $discount,
                    'discount_percent' => $discountPct,
                    'line_amount' => $amount,
                    'tax' => $tax,
                    'is_taxable' => $isTaxable,
                    'amount' => $total,
                ];
            }
        }

        $subtotal = (float) ($payload['subtotal'] ?? 0);
        $overallDiscount = (float) ($payload['overall_discount'] ?? 0);
        $overallDiscountPct = (float) ($payload['overall_discount_percent'] ?? 0);
        $overallTax = (float) ($payload['overall_tax'] ?? 0);
        $adjustment = (float) ($payload['adjustment'] ?? 0);
        $grandTotal = (float) ($payload['grand_total'] ?? 0);

        $showTaxRow = $overallTax > 0;
        $showShippingRow = $adjustment != 0.0;
        $taxRowHtml = $showTaxRow
            ? '<tr><td>Tax</td><td class="qt-right ms-right t3-right" style="text-align:right;">' . $fmt($overallTax) . '</td></tr>'
            : '';
        $shippingRowHtml = $showShippingRow
            ? '<tr><td>Shipping / Adj.</td><td class="qt-right ms-right t3-right" style="text-align:right;">' . $fmt($adjustment) . '</td></tr>'
            : '';

        $notes = $this->richTextForPdf((string) ($payload['description'] ?? ''));
        $terms = $this->richTextForPdf((string) ($payload['terms_conditions'] ?? ''));
        $projectDetail = $this->richTextForPdf((string) ($payload['project_detail'] ?? ''));
        $termsPlain = trim(preg_replace('/\s+/', ' ', strip_tags((string) ($payload['terms_conditions'] ?? ''))));
        $paymentTerms = $termsPlain !== '' ? \Illuminate\Support\Str::limit($termsPlain, 90) : '';

        $validUntil = trim((string) ($payload['valid_until'] ?? ''));
        try {
            $validUntilFormatted = $validUntil !== ''
                ? \Carbon\Carbon::parse($validUntil)->format('d M Y')
                : '';
        } catch (\Throwable $e) {
            $validUntilFormatted = $validUntil;
        }

        $proposalVersion = max(1, (int) ($payload['proposal_version'] ?? 1));
        $showBank = !empty($payload['show_bank_details']) && (string) $payload['show_bank_details'] !== '0';

        $gst = '';
        if ($company) {
            foreach (['gstin', 'gst_no', 'gst'] as $field) {
                $value = $company->getAttribute($field);
                if (!empty($value)) {
                    $gst = trim((string) $value);
                    break;
                }
            }
        }

        $logoHtml = $this->companyLogoHtml($company, 56);

        return $this->aliasPlaceholders([
            'company_name' => $company->name ?? config('app.name', 'CRM'),
            'company_logo' => $logoHtml,
            'company_address' => trim(collect([
                $company->address ?? null,
                $company->district ?? null,
                $company->city ?? null,
                $company->state ?? null,
                $company->pincode ?? null,
                $company->country ?? null,
            ])->filter()->implode(', ')),
            'company_phone' => $company->phone ?? $company->mobile ?? '',
            'company_email' => $company->email ?? '',
            'company_website' => $this->formatWebsiteForDisplay($company->website ?? ''),
            'company_gst' => $gst !== '' ? 'GSTIN: ' . $gst : '',
            'customer_name' => $customerName,
            'customer_company' => $organizationName,
            'customer_address' => $customerAddress,
            'customer_phone' => optional($person)->phone ?? optional($person)->mobile ?? '',
            'customer_email' => optional($person)->email ?? '',
            'customer_gst' => '',
            'quotation_number' => 'DRAFT',
            'quotation_date' => now()->format('d M Y'),
            'quotation_expiry_date' => $validUntilFormatted,
            'quotation_reference' => $dealTitle !== '' ? $dealTitle : $subject,
            'quotation_status' => trim((string) ($payload['status'] ?? 'Draft')),
            'proposal_version' => 'v' . $proposalVersion,
            'sales_person' => optional($owner)->name ?? '',
            'sales_person_email' => optional($owner)->email ?? '',
            'sales_person_phone' => optional($owner)->phone ?? optional($owner)->mobile ?? '',
            'subtotal' => $fmt($subtotal),
            'discount' => $this->discountLabel($overallDiscount, $overallDiscountPct, $currency),
            'tax' => $fmt($overallTax),
            'shipping' => $fmt($adjustment),
            'tax_row' => $taxRowHtml,
            'shipping_row' => $shippingRowHtml,
            'show_tax_row' => $showTaxRow ? '1' : '',
            'show_shipping_row' => $showShippingRow ? '1' : '',
            'footer_left' => 'Copyright © Isarva Infotech Pvt Ltd, All Rights Reserved',
            'grand_total' => $fmt($grandTotal),
            'amount_in_words' => $this->amountInWords($grandTotal, $company, $currencyCode),
            'terms_conditions' => $terms,
            'notes' => $notes,
            'project_detail' => $projectDetail,
            'payment_terms' => $paymentTerms,
            'signature' => optional($owner)->name ?? ($company->name ?? ''),
            'designation' => $this->designationForUser($owner),
            'closing_block' => $this->renderClosingSignoffHtml([
                'signature' => optional($owner)->name ?? ($company->name ?? ''),
                'designation' => $this->designationForUser($owner),
                'company_name' => $company->name ?? config('app.name', 'CRM'),
            ]),
            'bank_details_block' => $this->renderBankDetailsBlock($company, $showBank),
            'quote_summary_block' => ($productType === 'product'
                ? $this->renderQuoteSummaryBlock($dealTitle, $organizationName, $quotationTitle)
                    . $this->renderProductPricingCard($itemRows[0] ?? [], $currency)
                : $this->renderQuoteSummaryBlock($dealTitle, $organizationName, $quotationTitle)),
            'subject' => $subjectBannerText,
            'subject_block' => $this->renderSubjectBannerBlock($subjectBannerText),
            'quotation_title' => $quotationTitle !== ''
                ? $quotationTitle
                : ($dealTitle !== '' ? 'Quotation for ' . $dealTitle : ''),
            'items' => $productType === 'product'
                ? $this->renderProductItemsTable($itemRows, $currency, $grandTotal, true, $currencyCode)
                : $this->renderItemsTable($itemRows, true, $currency, $showItemTaxColumn),
            'item_rows' => $productType === 'product'
                ? $this->renderProductItemRows($itemRows, $currency)
                : $this->renderItemRows($itemRows, true, $currency, $showItemTaxColumn),
            'notes_block' => $this->labeledBlock('REMARKS', $notes),
            'terms_block' => $this->labeledBlock('TERMS &amp; CONDITIONS', $terms),
            'project_detail_block' => $this->labeledBlock('PROJECT DETAIL', $projectDetail),
        ]);
    }

    /**
     * Prepare description/terms/project HTML for PDF (TinyMCE + legacy Quill).
     */
    protected function richTextForPdf(string $html): string
    {
        $html = trim($html);
        if ($html === '') {
            return '';
        }

        if ($html === strip_tags($html)) {
            return nl2br(e($html));
        }

        $html = $this->sanitizeRichEditorHtml($html);
        $html = $this->normalizeEditorHtmlForPdf($html);

        if (trim(strip_tags($html, '<img>')) === '') {
            return '';
        }

        return $html;
    }

    /**
     * Normalize editor HTML into mPDF-friendly markup.
     */
    protected function normalizeEditorHtmlForPdf(string $html): string
    {
        $html = preg_replace('#<span[^>]*\bql-ui\b[^>]*>.*?</span>#is', '', $html) ?? $html;
        $html = preg_replace('/\scontenteditable=(["\'])[^"\']*\1/i', '', $html) ?? $html;
        $html = preg_replace('/\sclass=(["\'])[^"\']*\bql-[^"\']*\1/i', '', $html) ?? $html;
        $html = preg_replace('/\sdata-mce-[a-z0-9_-]+=(["\'])[^"\']*\1/i', '', $html) ?? $html;

        if (str_contains($html, 'data-list=')) {
            $html = $this->normalizeQuillListsForPdf($html);
        }

        $html = preg_replace('#<p[^>]*>\s*(?:<br\s*/?>)?\s*</p>#i', '', $html) ?? $html;

        $html = preg_replace_callback('#<p([^>]*)>(.*?)</p>#is', function (array $m): string {
            // Keep heading substitutes created below (or already present).
            if (preg_match('/\bms-rt-h\b/i', $m[1])) {
                return '<p' . $m[1] . '>' . $m[2] . '</p>';
            }
            $inner = trim($m[2]);
            if ($inner === '' || strcasecmp($inner, '<br>') === 0 || strcasecmp($inner, '<br/>') === 0) {
                return '';
            }
            $inner = strip_tags($inner, '<strong><b><em><i><s><br><span><img>');
            // Never keep pasted underline/link wrappers in PDF body copy.
            $inner = preg_replace('/<\/?(?:a|u|ins)\b[^>]*>/i', '', $inner) ?? $inner;
            $align = '';
            if (preg_match('/text-align\s*:\s*(left|center|right|justify)/i', $m[1], $am)) {
                $align = 'text-align:' . strtolower($am[1]) . ';';
            }

            return '<p style="margin:0 0 7px 0;line-height:1.55;font-size:11px;color:#334155;text-decoration:none;border-bottom:none;' . $align . '">' . $inner . '</p>';
        }, $html) ?? $html;

        $html = preg_replace_callback('#<(h[1-6])([^>]*)>(.*?)</\1>#is', function (array $m) {
            $inner = trim(strip_tags($m[3], '<strong><b><em><i><br><span>'));
            if ($inner === '') {
                return '';
            }
            $align = '';
            if (preg_match('/text-align\s*:\s*(left|center|right|justify)/i', $m[2], $am)) {
                $align = 'text-align:' . strtolower($am[1]) . ';';
            }
            $level = (int) substr($m[1], 1);
            $size = [1 => '14px', 2 => '13px', 3 => '12px', 4 => '12px', 5 => '11px', 6 => '11px'][$level] ?? '12px';

            // Use <p>, not <h*>. mPDF default heading CSS uses page-break-after:avoid
            // which chains the whole section together and leaves a blank gap.
            return '<p class="ms-rt-h" style="font-size:' . $size . ';font-weight:bold;color:#1e293b;margin:10px 0 5px 0;line-height:1.4;page-break-inside:auto;text-decoration:none;border-bottom:none;' . $align . '">'
                . $inner
                . '</p>';
        }, $html) ?? $html;

        $html = preg_replace_callback('#<(ol|ul)([^>]*)>(.*?)</\1>#is', function (array $m) {
            $tag = strtolower($m[1]);
            $inner = preg_replace('/\sdata-list=(["\'])[^"\']*\1/i', '', $m[3]) ?? $m[3];

            return '<' . $tag . ' style="margin:0 0 8px 18px;padding:0;">' . $inner . '</' . $tag . '>';
        }, $html) ?? $html;

        $html = preg_replace_callback('#<li([^>]*)>(.*?)</li>#is', function (array $m) {
            $inner = trim(strip_tags($m[2], '<strong><b><em><i><s><br><span><ul><ol><li><img><p>'));
            $inner = preg_replace('/<\/?(?:a|u|ins)\b[^>]*>/i', '', $inner) ?? $inner;
            if ($inner === '') {
                return '';
            }
            // Flatten TinyMCE <li><p>...</p></li> so mPDF can split lists cleanly.
            $inner = preg_replace('#^<p[^>]*>#i', '', $inner) ?? $inner;
            $inner = preg_replace('#</p>$#i', '', $inner) ?? $inner;
            $inner = preg_replace('#</p>\s*<p[^>]*>#i', '<br>', $inner) ?? $inner;
            $inner = preg_replace('#</?p[^>]*>#i', '', $inner) ?? $inner;

            return '<li style="margin:0 0 6px 0;page-break-inside:auto;font-size:11px;line-height:1.55;color:#334155;text-decoration:none;border-bottom:none;">' . trim($inner) . '</li>';
        }, $html) ?? $html;

        $extractBgColor = static function (string $attrs): ?string {
            if (preg_match('/style\s*=\s*(["\'])(.*?)\1/is', $attrs, $sm)) {
                $style = $sm[2];
                if (preg_match('/background-color\s*:\s*([^;]+)/i', $style, $bm)) {
                    $bg = trim($bm[1]);
                    if ($bg !== '' && strtolower($bg) !== 'transparent' && strtolower($bg) !== 'inherit') {
                        return $bg;
                    }
                }
                if (preg_match('/background\s*:\s*([^;]+)/i', $style, $bm)) {
                    $bg = trim($bm[1]);
                    if ($bg !== '' && !preg_match('/url|none|transparent|inherit/i', $bg)) {
                        $parts = explode(' ', $bg);
                        foreach ($parts as $p) {
                            $p = trim($p);
                            if (preg_match('/^(#[0-9a-f]{3,8}|rgba?\(.*?\)|[a-z]+)$/i', $p) && !in_array(strtolower($p), ['no-repeat', 'repeat', 'scroll', 'fixed', 'top', 'bottom', 'left', 'right', 'center'], true)) {
                                return $p;
                            }
                        }
                    }
                }
            }
            if (preg_match('/bgcolor\s*=\s*(["\']?)([^"\'\s>]+)\1/i', $attrs, $bm)) {
                $bg = trim($bm[2]);
                if ($bg !== '' && strtolower($bg) !== 'transparent') {
                    return $bg;
                }
            }

            return null;
        };

        $html = preg_replace_callback('#<table([^>]*)>(.*?)</table>#is', function (array $m) {
            return '<table width="100%" cellpadding="4" cellspacing="0" border="1" style="width:100%;border-collapse:collapse;margin:0 0 8px 0;border:1px solid #cbd5e1;">'
                . $m[2]
                . '</table>';
        }, $html) ?? $html;

        $html = preg_replace_callback('#<tr([^>]*)>(.*?)</tr>#is', function (array $trMatch) use ($extractBgColor) {
            $trAttrs = $trMatch[1];
            $trInner = $trMatch[2];
            $trBgColor = $extractBgColor($trAttrs);

            $trStyle = '';
            if ($trBgColor !== null) {
                $trStyle = 'background-color:' . $trBgColor . ';';
            }

            $trInner = preg_replace_callback('#<(td|th)([^>]*)>(.*?)</\1>#is', function (array $m) use ($trBgColor, $extractBgColor) {
                $tag = strtolower($m[1]);
                $cellAttrs = $m[2];
                $inner = trim(strip_tags($m[3], '<strong><b><em><i><u><br><a><span><img><p><ul><ol><li>'));

                $cellBgColor = $extractBgColor($cellAttrs);
                $effectiveBg = $cellBgColor ?? $trBgColor;

                $styleParts = [
                    'border:1px solid #cbd5e1',
                    'padding:5px',
                    'vertical-align:top',
                ];

                if ($effectiveBg !== null) {
                    $styleParts[] = 'background-color:' . $effectiveBg;
                } elseif ($tag === 'th') {
                    $styleParts[] = 'background:#f1f5f9';
                }

                if ($tag === 'th') {
                    $styleParts[] = 'font-weight:bold';
                }

                if (preg_match('/text-align\s*:\s*(left|center|right|justify)/i', $cellAttrs, $am)) {
                    $styleParts[] = 'text-align:' . strtolower($am[1]);
                } elseif ($tag === 'th' && $effectiveBg === null) {
                    $styleParts[] = 'text-align:center';
                }

                return '<' . $tag . ' style="' . implode(';', $styleParts) . ';">'
                    . ($inner !== '' ? $inner : '&nbsp;')
                    . '</' . $tag . '>';
            }, $trInner) ?? $trInner;

            return '<tr' . ($trStyle !== '' ? ' style="' . $trStyle . '"' : '') . '>' . $trInner . '</tr>';
        }, $html) ?? $html;

        $html = preg_replace_callback('#<(td|th)([^>]*)>(.*?)</\1>#is', function (array $m) use ($extractBgColor) {
            if (str_contains($m[2], 'border:1px solid #cbd5e1')) {
                return $m[0];
            }
            $tag = strtolower($m[1]);
            $cellAttrs = $m[2];
            $inner = trim(strip_tags($m[3], '<strong><b><em><i><u><br><a><span><img><p><ul><ol><li>'));
            $cellBgColor = $extractBgColor($cellAttrs);

            $styleParts = [
                'border:1px solid #cbd5e1',
                'padding:5px',
                'vertical-align:top',
            ];
            if ($cellBgColor !== null) {
                $styleParts[] = 'background-color:' . $cellBgColor;
            } elseif ($tag === 'th') {
                $styleParts[] = 'background:#f1f5f9';
            }
            if ($tag === 'th') {
                $styleParts[] = 'font-weight:bold';
            }
            if (preg_match('/text-align\s*:\s*(left|center|right|justify)/i', $cellAttrs, $am)) {
                $styleParts[] = 'text-align:' . strtolower($am[1]);
            }

            return '<' . $tag . ' style="' . implode(';', $styleParts) . ';">'
                . ($inner !== '' ? $inner : '&nbsp;')
                . '</' . $tag . '>';
        }, $html) ?? $html;

        $html = preg_replace_callback('#<img\b([^>]*)\s*/?>#i', function (array $m) {
            $attrs = $m[1];
            if (!preg_match('/\ssrc=(["\'])(.*?)\1/i', $attrs, $sm)) {
                return '';
            }
            $src = html_entity_decode($sm[2], ENT_QUOTES, 'UTF-8');
            if ($src === '' || preg_match('/^\s*javascript:/i', $src)) {
                return '';
            }
            $width = 150;
            $height = 80;
            if (preg_match('/\swidth=(["\']?)(\d+)/i', $attrs, $wm)) {
                $width = max(20, min(600, (int) $wm[2]));
            } elseif (preg_match('/width\s*:\s*(\d+)px/i', $attrs, $wm)) {
                $width = max(20, min(600, (int) $wm[1]));
            }
            if (preg_match('/\sheight=(["\']?)(\d+)/i', $attrs, $hm)) {
                $height = max(20, min(600, (int) $hm[2]));
            } elseif (preg_match('/height\s*:\s*(\d+)px/i', $attrs, $hm)) {
                $height = max(20, min(600, (int) $hm[1]));
            }
            $alt = 'Image';
            if (preg_match('/\salt=(["\'])(.*?)\1/i', $attrs, $am)) {
                $alt = $am[2];
            }

            return '<img src="' . e($src) . '" width="' . $width . '" height="' . $height
                . '" alt="' . e($alt) . '" style="width:' . $width . 'px;height:' . $height . 'px;max-width:100%;">';
        }, $html) ?? $html;

        $html = preg_replace('/(href\s*=\s*)(["\'])\s*javascript:[^"\']*\2/i', '$1$2#$2', $html) ?? $html;

        return trim($html);
    }

    /**
     * Convert Quill data-list markup into standard lists.
     */
    protected function normalizeQuillListsForPdf(string $html): string
    {
        return preg_replace_callback(
            '#<(ol|ul)([^>]*)>(.*?)</\1>#is',
            function (array $m): string {
                $inner = $m[3];
                $ordered = [];
                $bullets = [];

                if (!preg_match_all('#<li([^>]*)>(.*?)</li>#is', $inner, $items, PREG_SET_ORDER)) {
                    return $m[0];
                }

                foreach ($items as $item) {
                    $attrs = $item[1];
                    $content = preg_replace('#<span[^>]*\bql-ui\b[^>]*>.*?</span>#is', '', $item[2]) ?? $item[2];
                    $content = preg_replace('#</?span\b[^>]*>#i', '', $content) ?? $content;
                    $content = trim(strip_tags($content, '<strong><b><em><i><u><br><a>'));
                    if ($content === '') {
                        continue;
                    }
                    $listType = 'ordered';
                    if (preg_match('/data-list=(["\'])(bullet|ordered)\1/i', $attrs, $lm)) {
                        $listType = strtolower($lm[2]);
                    } elseif (strcasecmp($m[1], 'ul') === 0) {
                        $listType = 'bullet';
                    }
                    if ($listType === 'bullet') {
                        $bullets[] = $content;
                    } else {
                        $ordered[] = $content;
                    }
                }

                $out = '';
                if ($ordered !== []) {
                    $out .= '<ol style="margin:0 0 8px 18px;padding:0;">';
                    foreach ($ordered as $c) {
                        $out .= '<li style="margin:0 0 5px 0;">' . $c . '</li>';
                    }
                    $out .= '</ol>';
                }
                if ($bullets !== []) {
                    $out .= '<ul style="margin:0 0 8px 18px;padding:0;">';
                    foreach ($bullets as $c) {
                        $out .= '<li style="margin:0 0 5px 0;">' . $c . '</li>';
                    }
                    $out .= '</ul>';
                }

                return $out;
            },
            $html
        ) ?? $html;
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     */
    public function renderItemsTable(array $rows, bool $escape = false, string $currency = '', ?bool $showTaxColumn = null): string
    {
        if ($showTaxColumn === null) {
            $showTaxColumn = $this->itemsHaveTaxColumn($rows);
        }

        $html = '<table class="qt-items-table" width="100%" cellpadding="0" cellspacing="0"><thead><tr>'
            . '<th width="6%" class="qt-c">#</th>'
            . ($showTaxColumn
                ? '<th width="34%" class="qt-l">Description</th>'
                : '<th width="42%" class="qt-l">Description</th>')
            . '<th width="8%" class="qt-c">Qty</th>'
            . '<th width="14%" class="qt-r">Rate</th>'
            . '<th width="12%" class="qt-r">Discount</th>'
            . ($showTaxColumn ? '<th width="8%" class="qt-c">Tax</th>' : '')
            . '<th width="14%" class="qt-r">Amount</th>'
            . '</tr></thead><tbody>';

        $colspan = $showTaxColumn ? 7 : 6;
        if (empty($rows)) {
            $html .= '<tr><td colspan="' . $colspan . '" class="qt-c">No items</td></tr>';
        } else {
            $html .= $this->renderItemRows($rows, $escape, $currency, $showTaxColumn);
        }

        $html .= '</tbody></table>';

        return $html;
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     */
    public function renderItemRows(array $rows, bool $escape = false, string $currency = '', ?bool $showTaxColumn = null): string
    {
        if ($showTaxColumn === null) {
            $showTaxColumn = $this->itemsHaveTaxColumn($rows);
        }

        $html = '';
        $n = 1;
        foreach ($rows as $row) {
            $sku = (string) ($row['sku'] ?? '');
            $name = (string) ($row['name'] ?? '');
            $desc = (string) ($row['description'] ?? '');
            if ($escape) {
                $sku = e($sku);
                $name = e($name);
                $desc = e($desc);
            }

            $qty = $row['qty'] ?? 0;
            $qtyLabel = is_numeric($qty) && (float) $qty == (int) $qty ? (string) (int) $qty : (string) $qty;
            $price = $this->moneyLabel($row['price'] ?? 0, $currency);
            $discountAmt = (float) ($row['discount'] ?? 0);
            $discountPct = (float) ($row['discount_percent'] ?? 0);
            $lineAmount = (float) ($row['line_amount'] ?? 0);
            if ($discountPct <= 0 && $discountAmt > 0 && $lineAmount > 0) {
                $discountPct = round(($discountAmt / $lineAmount) * 100, 2);
            }
            $discount = $this->discountLabel($discountAmt, $discountPct, $currency);
            $tax = $this->taxLabel($row['tax'] ?? 0);
            $amount = $this->moneyLabel($row['amount'] ?? 0, $currency);

            $detail = '<div class="qt-item-name">' . ($name !== '' ? $name : 'Item') . '</div>';
            if ($sku !== '') {
                $detail .= '<div class="qt-item-meta">SKU: ' . $sku . '</div>';
            }
            if ($desc !== '') {
                $detail .= '<div class="qt-item-meta">' . $desc . '</div>';
            }

            $html .= '<tr>'
                . '<td class="qt-c">' . $n . '</td>'
                . '<td class="qt-l">' . $detail . '</td>'
                . '<td class="qt-c">' . e((string) $qtyLabel) . '</td>'
                . '<td class="qt-r">' . $price . '</td>'
                . '<td class="qt-r">' . $discount . '</td>'
                . ($showTaxColumn ? '<td class="qt-c">' . $tax . '</td>' : '')
                . '<td class="qt-r">' . $amount . '</td>'
                . '</tr>';
            $n++;
        }

        return $html;
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     */
    protected function itemsHaveTaxColumn(array $rows): bool
    {
        foreach ($rows as $row) {
            if (!empty($row['is_taxable']) || (float) ($row['tax'] ?? 0) > 0) {
                return true;
            }
        }

        return false;
    }

    protected function moneyLabel($value, string $currency): string
    {
        $formatted = format_smart_number($value);
        $currency = trim($currency);

        return $currency !== '' ? $currency . ' ' . $formatted : $formatted;
    }

    /**
     * Discount as amount with percentage in brackets, e.g. "₹ 2,000 (5%)".
     */
    protected function discountLabel($amount, $percent, string $currency): string
    {
        $amount = (float) $amount;
        $percent = (float) $percent;
        $label = $this->moneyLabel($amount, $currency);

        if ($amount > 0 && $percent > 0) {
            $label .= ' (' . format_smart_number($percent) . '%)';
        }

        return $label;
    }

    protected function taxLabel($value): string
    {
        $tax = (float) $value;
        if ($tax <= 0) {
            return '—';
        }
        if ($tax <= 100) {
            return format_smart_number($tax) . '%';
        }

        return format_smart_number($tax);
    }

    protected function labeledBlock(string $title, string $html): string
    {
        if (trim(strip_tags($html, '<img>')) === '') {
            return '';
        }

        $body = $this->flattenRichBlocksForPdf($html);
        $body = $this->stripUnderlineMarkup($body);

        // Title bar + flowing body wrapper so PDF CSS can set readable font size
        // and suppress paste underlines that otherwise appear on every line.
        return '<table class="ms-block-title-table" width="100%" cellpadding="0" cellspacing="0" border="0" style="width:100%;border-collapse:collapse;margin:10px 0 4px 0;">'
            . '<tr>'
            . '<td class="qt-label ms-block-title" style="width:100%;font-family:dejavusans;font-size:12px;font-weight:bold;color:#ffffff;text-transform:uppercase;text-decoration:none;border:0;padding:8px 10px;letter-spacing:0.5px;background:#1e293b;">'
            . $title
            . '</td>'
            . '</tr>'
            . '</table>'
            . '<div class="ms-block-body qt-section-body" style="width:100%;font-family:dejavusans;font-size:11px;line-height:1.55;color:#334155;text-decoration:none;margin:0 0 12px 0;">'
            . $body
            . '</div>';
    }

    /**
     * Centered quotation title + intro shown immediately before the items table.
     */
    public function renderQuoteSummaryBlock(string $dealTitle, string $organizationName, string $quotationTitle = ''): string
    {
        $dealTitle = trim($dealTitle);
        $organizationName = trim($organizationName);
        $quotationTitle = trim($quotationTitle);

        if ($quotationTitle === '' && $dealTitle === '' && $organizationName === '') {
            return '';
        }

        if ($quotationTitle !== '') {
            $titleText = e($quotationTitle);
            $serviceLabel = preg_replace('/^quotation\s+for\s+/i', '', $quotationTitle) ?: $quotationTitle;
            $serviceLabel = e(trim($serviceLabel));
        } elseif ($dealTitle !== '') {
            $titleText = 'Quotation for ' . e($dealTitle);
            $serviceLabel = e($dealTitle);
        } else {
            $titleText = 'Quotation';
            $serviceLabel = '';
        }

        $intro = 'We are pleased to present our ';
        if ($serviceLabel !== '') {
            $intro .= 'Quotation for ' . $serviceLabel . ' Services';
        } else {
            $intro .= 'Quotation';
        }
        if ($organizationName !== '') {
            $intro .= ' for ' . e($organizationName);
        }

        return '<div class="qt-quote-summary" style="text-align:center;margin:14px 0 12px 0;width:100%;">'
            . '<div class="qt-quote-summary-title" style="color:#1e4785;font-size:16px;font-weight:bold;margin:0 0 8px 0;line-height:1.35;text-decoration:none;">'
            . $titleText
            . '</div>'
            . '<div class="qt-quote-summary-intro" style="text-align:left;color:#222222;font-size:11px;margin:0 0 10px 0;line-height:1.45;text-decoration:none;">'
            . $intro
            . '</div>'
            . '<div class="qt-quote-summary-heading" style="color:#111111;font-size:13px;font-weight:bold;margin:0 0 8px 0;text-decoration:none;">'
            . 'Quote Summary'
            . '</div>'
            . '</div>';
    }

    /**
     * Product-type pricing card (teal header + actual / discounted per-user prices).
     *
     * @param  array<string, mixed>  $row
     */
    public function renderProductPricingCard(array $row, string $currency = '', bool $escape = true): string
    {
        $name = (string) ($row['name'] ?? 'Product');
        if ($escape) {
            $name = e($name);
        }

        $qty = (float) ($row['qty'] ?? 0);
        $lineAmount = (float) ($row['line_amount'] ?? 0);
        $discountedUnit = (float) ($row['price'] ?? 0);
        $discountPct = (float) ($row['discount_percent'] ?? 0);
        $discountAmt = (float) ($row['discount'] ?? 0);

        $actualUnit = $qty > 0 && $lineAmount > 0
            ? $lineAmount / $qty
            : $discountedUnit;
        if ($discountPct <= 0 && $discountAmt > 0 && $lineAmount > 0) {
            $discountPct = round(($discountAmt / $lineAmount) * 100, 2);
        }
        if ($discountPct <= 0 && $actualUnit > $discountedUnit && $actualUnit > 0) {
            $discountPct = round((($actualUnit - $discountedUnit) / $actualUnit) * 100, 2);
        }

        $actualLabel = $this->moneyLabel($actualUnit, $currency);
        $discountedLabel = $this->moneyLabel($discountedUnit, $currency);
        $pctLabel = format_smart_number($discountPct);

        return '<div class="qt-product-quote-summary" style="margin:0 0 0 0;width:100%;">'
            . '<table class="qt-pricing-card" width="100%" cellpadding="0" cellspacing="0" style="width:100%;border-collapse:collapse;border:1px solid #9cc3c3;margin:0 0 0 0;">'
            . '<tr class="qt-pricing-head">'
            . '<td style="border:1px solid #9cc3c3;padding:10px 12px;background:#2aa3a3;color:#ffffff;font-weight:bold;font-size:12px;">' . ($name !== '' ? $name : 'Product') . '</td>'
            . '<td style="width:220px;border:1px solid #9cc3c3;padding:10px 12px;background:#2aa3a3;color:#ffffff;font-weight:bold;font-size:12px;text-align:center;">Pricing</td>'
            . '</tr>'
            . '<tr>'
            . '<td style="border:1px solid #9cc3c3;padding:10px 12px;color:#222222;font-size:11px;">Actual Price Employee - 1 User</td>'
            . '<td style="border:1px solid #9cc3c3;padding:10px 12px;color:#222222;font-size:11px;text-align:center;">' . $actualLabel . ' Per User</td>'
            . '</tr>'
            . '<tr class="qt-pricing-discount">'
            . '<td style="border:1px solid #9cc3c3;padding:10px 12px;color:#dc3545;font-weight:bold;font-size:11px;">Discounted Price - 1 User (' . $pctLabel . '% Discount)</td>'
            . '<td style="border:1px solid #9cc3c3;padding:10px 12px;color:#dc3545;font-weight:bold;font-size:11px;text-align:center;">' . $discountedLabel . ' Per User</td>'
            . '</tr>'
            . '</table>'
            . '</div>';
    }

    /**
     * Product-type items table (Sl No | Description | Price | No of Employee | Total).
     *
     * @param  array<int, array<string, mixed>>  $rows
     */
    public function renderProductItemsTable(array $rows, string $currency = '', float $grandTotal = 0, bool $escape = true, ?string $currencyCode = null): string
    {
        $productHead = 'border:1px solid #2E7D32;padding:8px 10px;background:#2E7D32;color:#ffffff;font-weight:bold;text-align:center;font-size:11px;';
        $codeLabel = !empty($currencyCode) ? trim($currencyCode) : ($currency !== '' ? trim($currency) : 'INR');

        $html = '<table class="qt-product-items-table" width="100%" cellpadding="0" cellspacing="0" style="width:100%;border-collapse:collapse;border:1px solid #9cc3c3;margin-top:-1px;">'
            . '<thead><tr>'
            . '<th style="width:70px;' . $productHead . '">Sl. No</th>'
            . '<th style="' . $productHead . '">Description</th>'
            . '<th style="width:120px;' . $productHead . '">Price</th>'
            . '<th style="width:140px;' . $productHead . '">No of Employee</th>'
            . '<th style="width:150px;' . $productHead . '">Total (' . e($codeLabel) . ')</th>'
            . '</tr></thead><tbody>';

        if (empty($rows)) {
            $html .= '<tr><td colspan="5" style="border:1px solid #9cc3c3;padding:8px 10px;text-align:center;font-size:11px;">No items</td></tr>';
        } else {
            $html .= $this->renderProductItemRows($rows, $currency, $escape);
        }

        if ($grandTotal <= 0) {
            foreach ($rows as $row) {
                $grandTotal += (float) ($row['amount'] ?? 0);
            }
        }

        $html .= '</tbody><tfoot><tr>'
            . '<td colspan="4" style="border:1px solid #9cc3c3;padding:8px 10px;font-weight:bold;background:#f8fafc;text-align:right;font-size:11px;">Grand Total</td>'
            . '<td style="border:1px solid #9cc3c3;padding:8px 10px;font-weight:bold;background:#f8fafc;text-align:center;font-size:11px;">' . $this->moneyLabel($grandTotal, $currency) . '</td>'
            . '</tr></tfoot></table>';

        return $html;
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     */
    public function renderProductItemRows(array $rows, string $currency = '', bool $escape = true): string
    {
        $html = '';
        $n = 1;
        foreach ($rows as $row) {
            $name = (string) ($row['name'] ?? 'Item');
            if ($escape) {
                $name = e($name);
            }

            $qty = $row['qty'] ?? 0;
            $qtyLabel = is_numeric($qty) && (float) $qty == (int) $qty ? (string) (int) $qty : (string) $qty;
            $price = $this->moneyLabel($row['price'] ?? 0, $currency);
            $lineTotal = (float) ($row['amount'] ?? 0);
            if ($lineTotal <= 0) {
                $lineAmount = (float) ($row['line_amount'] ?? 0);
                $discountAmt = (float) ($row['discount'] ?? 0);
                $lineTotal = $lineAmount > 0 ? $lineAmount - $discountAmt : 0;
            }
            $total = $this->moneyLabel($lineTotal, $currency);

            $html .= '<tr>'
                . '<td style="border:1px solid #9cc3c3;padding:8px 10px;text-align:center;font-size:11px;">' . $n . '</td>'
                . '<td style="border:1px solid #9cc3c3;padding:8px 10px;font-size:11px;">' . ($name !== '' ? $name : 'Item') . '</td>'
                . '<td style="border:1px solid #9cc3c3;padding:8px 10px;text-align:center;font-size:11px;">' . $price . '</td>'
                . '<td style="border:1px solid #9cc3c3;padding:8px 10px;text-align:center;font-size:11px;">' . e((string) $qtyLabel) . '</td>'
                . '<td style="border:1px solid #9cc3c3;padding:8px 10px;text-align:center;font-size:11px;">' . $total . '</td>'
                . '</tr>';
            $n++;
        }

        return $html;
    }

    /**
     * Subject line between horizontal rules, placed under the page header.
     */
    public function renderSubjectBannerBlock(string $subjectText): string
    {
        $subjectText = trim($subjectText);
        if ($subjectText === '') {
            return '';
        }

        return '<div class="qt-subject-banner" style="text-align:center;border:0;padding:12px 8px;margin:4px 0 12px 0;width:100%;">'
            . '<div class="qt-subject-banner-text" style="color:#1e4785;font-size:16px;font-weight:bold;line-height:1.4;text-decoration:none;border:0;">'
            . e($subjectText)
            . '</div>'
            . '</div>';
    }

    /**
     * Prefer quotation subject; otherwise build "{org} {deal} Proposal".
     */
    protected function subjectBannerText(string $subject, string $organizationName, string $dealTitle): string
    {
        $subject = trim($subject);
        if ($subject !== '') {
            return $subject;
        }

        $parts = array_values(array_filter([trim($organizationName), trim($dealTitle)], static fn ($v) => $v !== ''));
        if ($parts === []) {
            return '';
        }

        $text = implode(' ', $parts);
        if (!preg_match('/\bproposal\b/i', $text)) {
            $text .= ' Proposal';
        }

        return $text;
    }

    /**
     * Soften nested editor markup so mPDF can page-break mid-section.
     */
    protected function flattenRichBlocksForPdf(string $html): string
    {
        $html = $this->stripUnderlineMarkup($html);

        $html = preg_replace_callback(
            '#<(p|h1|h2|h3|h4|h5|h6|ul|ol|table|div|span|li)(\s[^>]*)?>#i',
            static function (array $m): string {
                $tag = strtolower($m[1]);
                $attrs = $m[2] ?? '';
                // Keep table/div borders intact; only scrub underline on text tags.
                $stripBottom = !in_array($tag, ['table', 'div', 'ul', 'ol'], true);
                $extra = $stripBottom
                    ? 'page-break-inside:auto;text-decoration:none;border-bottom:none;'
                    : 'page-break-inside:auto;text-decoration:none;';

                if (stripos($attrs, 'page-break-inside') !== false) {
                    return '<' . $tag . $attrs . '>';
                }
                if (preg_match('/\sstyle=(["\'])(.*?)\1/i', $attrs, $sm)) {
                    $style = rtrim($sm[2], ';') . ';' . $extra;
                    $attrs = preg_replace('/\sstyle=(["\'])(.*?)\1/i', ' style="' . $style . '"', $attrs, 1);

                    return '<' . $tag . $attrs . '>';
                }

                return '<' . $tag . $attrs . ' style="' . $extra . '">';
            },
            $html
        ) ?? $html;

        return $html;
    }

    /**
     * Strip fixed widths/floats from pasted HTML so notes/terms fill the page width in PDF.
     */
    protected function forceFullWidthHtml(string $html): string
    {
        $html = preg_replace('/\b(?:max-)?width\s*:\s*[^;\"\']+;?/i', '', $html) ?? $html;
        $html = preg_replace('/\bfloat\s*:\s*[^;\"\']+;?/i', '', $html) ?? $html;
        $html = preg_replace('/\bwidth\s*=\s*([\"\'])[^\"\']*\1/i', 'width="100%"', $html) ?? $html;
        $html = preg_replace('/\bwidth\s*=\s*\d+(?:\.\d+)?(?:px|%)?/i', 'width="100%"', $html) ?? $html;
        $html = preg_replace(
            '/<table\b([^>]*)>/i',
            '<table$1 width="100%" style="width:100% !important;max-width:100% !important;table-layout:fixed !important;border-collapse:collapse;">',
            $html
        ) ?? $html;

        return $html;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function aliasPlaceholders(array $data): array
    {
        $data['valid_until'] = $data['quotation_expiry_date'] ?? '';
        $data['prepared_by'] = $data['sales_person'] ?? '';
        $data['reference'] = $data['quotation_reference'] ?? '';
        $data['gst_amount'] = $data['tax'] ?? '';
        $data['taxable_amount'] = $data['subtotal'] ?? '';
        $data['remarks'] = $data['notes'] ?? '';
        $data['website'] = $data['company_website'] ?? '';
        $data['email'] = $data['company_email'] ?? '';
        $data['phone'] = $data['company_phone'] ?? '';
        $data['company_email_line'] = $this->headerContactLineHtml('email', (string) ($data['company_email'] ?? ''));
        $data['company_phone_line'] = $this->headerContactLineHtml('phone', (string) ($data['company_phone'] ?? ''));
        $data['company_website_line'] = $this->headerContactLineHtml('website', (string) ($data['company_website'] ?? ''));
        $data['company_contact_block'] = $this->headerContactBlockHtml(
            (string) ($data['company_email'] ?? ''),
            (string) ($data['company_phone'] ?? ''),
            (string) ($data['company_website'] ?? '')
        );
        $data['sq2_sash'] = $this->sq2SashHtml();
        $data['customer_gstin'] = $data['customer_gst'] ?? '';
        $data['quotation_items'] = $data['item_rows'] ?? '';
        $data['customer_city'] = '';
        $data['customer_state'] = '';
        $data['customer_pincode'] = '';
        if (!isset($data['proposal_version']) || $data['proposal_version'] === '') {
            $data['proposal_version'] = 'v1';
        }

        return $data;
    }

    /**
     * Display website without protocol (https://) or trailing slash.
     */
    protected function formatWebsiteForDisplay(?string $website): string
    {
        $website = trim((string) $website);
        if ($website === '') {
            return '';
        }

        $website = preg_replace('#^https?://#i', '', $website) ?? $website;
        $website = rtrim($website, '/');

        return $website;
    }

    protected function amountInWords(float $amount, $company = null, ?string $currencyCode = null): string
    {
        $code = strtoupper((string) ($currencyCode ?: ($company->currency_code ?? 'INR')));
        $currencyName = $code === 'INR' || $code === '' ? 'Rupees' : $code;
        $fractionName = $code === 'INR' || $code === '' ? 'Paise' : 'Cents';
        $amount = round(max(0, $amount), 2);
        $whole = (int) floor($amount);
        $fraction = (int) round(($amount - $whole) * 100);
        $words = $this->indianNumberToWords($whole);
        if ($words === '') {
            $words = 'Zero';
        }
        $out = $currencyName . ' ' . $words;
        if ($fraction > 0) {
            $out .= ' and ' . $this->indianNumberToWords($fraction) . ' ' . $fractionName;
        }

        return $out . ' Only';
    }

    protected function indianNumberToWords(int $number): string
    {
        if ($number === 0) {
            return '';
        }

        $ones = ['', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine', 'Ten',
            'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen', 'Seventeen', 'Eighteen', 'Nineteen'];
        $tens = ['', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety'];

        $twoDigits = function (int $n) use ($ones, $tens): string {
            if ($n < 20) {
                return $ones[$n];
            }
            $t = intdiv($n, 10);
            $o = $n % 10;

            return trim($tens[$t] . ($o ? ' ' . $ones[$o] : ''));
        };

        $parts = [];
        $crore = intdiv($number, 10000000);
        $number %= 10000000;
        $lakh = intdiv($number, 100000);
        $number %= 100000;
        $thousand = intdiv($number, 1000);
        $number %= 1000;
        $hundred = intdiv($number, 100);
        $rest = $number % 100;

        if ($crore) {
            $parts[] = $this->indianNumberToWords($crore) . ' Crore';
        }
        if ($lakh) {
            $parts[] = $twoDigits($lakh) . ' Lakh';
        }
        if ($thousand) {
            $parts[] = $twoDigits($thousand) . ' Thousand';
        }
        if ($hundred) {
            $parts[] = $ones[$hundred] . ' Hundred';
        }
        if ($rest) {
            $parts[] = $twoDigits($rest);
        }

        return implode(' ', $parts);
    }

    /**
     * Replace placeholders. Values that are already HTML (logo, items, notes) are inserted as-is.
     * Scalar values should already be escaped by the data builder.
     */
    public function replacePlaceholders(string $html, array $data): string
    {
        $htmlTrusted = [
            'company_logo', 'items', 'item_rows', 'quotation_items',
            'terms_conditions', 'notes', 'payment_terms', 'remarks',
            'notes_block', 'terms_block', 'project_detail', 'project_detail_block',
            'closing_block', 'bank_details_block', 'quote_summary_block', 'subject_block',
            'tax_row', 'shipping_row', 'footer_left',
            'company_email_line', 'company_phone_line', 'company_website_line',
            'company_contact_block',
            'icon_email', 'icon_phone', 'icon_website',
            'sq2_sash',
        ];

        foreach ($data as $key => $value) {
            $placeholder = '{{' . $key . '}}';
            if (!in_array($key, $htmlTrusted, true) && is_string($value) && !str_contains((string) $value, '<')) {
                $value = e($value);
            }
            $html = str_replace($placeholder, (string) $value, $html);
        }

        // Clear any remaining unknown placeholders safely
        $html = preg_replace('/\{\{[a-z0-9_]+\}\}/i', '', $html) ?? $html;

        return $html;
    }

    /**
     * Normalize old template snapshots: hide unused totals rows, remove QUOTATION badge,
     * and force copyright footer on the left.
     *
     * @param  array<string, mixed>  $data
     */
    protected function normalizeQuotationTemplateRuntime(string $html, array $data): string
    {
        $showTax = !empty($data['show_tax_row']);
        $showShipping = !empty($data['show_shipping_row']);
        $copyright = (string) ($data['footer_left'] ?? 'Copyright © Isarva Infotech Pvt Ltd, All Rights Reserved');

        if (!$showTax) {
            $html = preg_replace(
                '/<tr[^>]*>\s*<td[^>]*>\s*Tax\s*<\/td>\s*<td[^>]*>[\s\S]*?<\/td>\s*<\/tr>/i',
                '',
                $html
            ) ?? $html;
        }

        if (!$showShipping) {
            $html = preg_replace(
                '/<tr[^>]*>\s*<td[^>]*>\s*Shipping\s*\/\s*Adj\.?\s*<\/td>\s*<td[^>]*>[\s\S]*?<\/td>\s*<\/tr>/i',
                '',
                $html
            ) ?? $html;
        }

        // Remove legacy QUOTATION badge / title labels.
        $html = preg_replace(
            '/<div[^>]*class="[^"]*(?:qt-doc-title|qt-doc-badge|ms-doc-title|t3-doc-title)[^"]*"[^>]*>\s*QUOTATION\s*<\/div>/i',
            '',
            $html
        ) ?? $html;

        // Force left footer copyright for common footer cells (old snapshots / custom templates).
        $html = preg_replace_callback(
            '/(<td[^>]*(?:footer-left|qt-footer-left|ms-footer-left|t3-footer-left|sq-footer-left)[^>]*>)([\s\S]*?)(<\/td>)/i',
            function ($m) use ($copyright) {
                return $m[1] . e($copyright) . $m[3];
            },
            $html
        ) ?? $html;

        // Standard footer first cell that still has website | email | phone after placeholders.
        $html = preg_replace_callback(
            '/(<table[^>]*class="[^"]*qt-footer[^"]*"[^>]*>\s*<tr>\s*<td[^>]*>)([\s\S]*?)(<\/td>)/i',
            function ($m) use ($copyright) {
                $inner = trim(strip_tags($m[2]));
                if ($inner === '' || stripos($inner, 'Copyright') !== false) {
                    return $m[0];
                }
                if (
                    stripos($inner, '@') !== false
                    || stripos($inner, 'www.') !== false
                    || preg_match('/\+?\d[\d\s\-]{6,}/', $inner)
                    || str_contains($inner, '|')
                    || str_contains($inner, '·')
                ) {
                    return $m[1] . e($copyright) . $m[3];
                }

                return $m[0];
            },
            $html
        ) ?? $html;

        return $html;
    }

    /**
     * Build a standalone HTML document from the template HTML + CSS fields only.
     * Preview uses the CSS as entered. PDF keeps the same CSS and only maps fonts / CSS variables
     * so mPDF can render it.
     */
    public function composeDocument(string $html, string $css, array $data, bool $forPdf = false): string
    {
        $parts = $this->extractDocumentParts($html);
        $safeHtml = $this->sanitizeHtml($parts['body']);
        $templateCss = trim(implode("\n", array_filter([
            $parts['css'],
            $css,
        ], fn ($value) => trim((string) $value) !== '')));
        $safeCss = $this->sanitizeCss($templateCss);
        // Keep existing header/body/footer when already structured. Blind rebuild
        // mis-detects template-3 (t3-*) and pushes Bill To + summary into the PDF header,
        // which drops the footer mid-page.
        if ($this->hasCompletePageChrome($safeHtml)) {
            $body = $this->ensureTitlebarNotInPageHeader($safeHtml);
        } else {
            $body = $this->rebuildRepeatingPageChrome($safeHtml);
        }
        $body = $this->ensurePageWrapper($body);
        // Old snapshots: Reference → Proposal Version for New Design / template 3 / Executive.
        $body = $this->replaceReferenceMetaWithProposalVersion($body);
        $body = $this->replacePlaceholders($body, $data);
        // Subject banner below header / above QUOTATION title (all templates + custom).
        $body = $this->ensureSubjectBlockPresent($body, $data);
        // Old snapshots may lack {{project_detail_block}}; inject before items when needed.
        $body = $this->ensureProjectDetailBlockPresent($body, $data);
        // Deal + organization intro before the quotation items table.
        $body = $this->ensureQuoteSummaryBlockPresent($body, $data);
        $hasProjectDetail = trim(strip_tags((string) ($data['project_detail'] ?? ''), '<img>')) !== '';
        $body = $this->applyProjectDetailPageBreak($body, $hasProjectDetail);
        $body = $this->ensureBankDetailsBlockPresent($body, $data);
        // Replace Authorized Signatory / ensure compact Sincerely closing after terms.
        $body = $this->ensureClosingSignoffPresent($body, $data);
        // Old snapshots: hide unused tax/adj rows, remove QUOTATION badge, normalize footer.
        $body = $this->normalizeQuotationTemplateRuntime($body, $data);
        // Remove Payment snippet from quotation panel (old snapshots still contain it).
        $body = preg_replace(
            '/<div\b[^>]*class=(["\'])[^"\']*\bms-quote-pay\b[^"\']*\1[^>]*>.*?<\/div>/is',
            '',
            $body
        ) ?? $body;
        // Standard Quotation: right-align header contact (email / phone / website).
        $body = $this->ensureStandardQuotationHeaderLayout($body, $data);

        if ($forPdf) {
            $isTemplate3 = $this->isTemplate3Stylesheet($safeCss, $body);
            $safeCss = $this->prepareCssForPdf($safeCss);
            $body = $this->adaptHtmlForPdf($body);
            $body = $this->sanitizePdfTextStyles($body);
            if ($isTemplate3) {
                // Re-apply after shared PDF CSS so Template 3 wins; bump inline sizes for mPDF.
                $safeCss = $this->applyTemplate3PdfFontScale($safeCss);
                $body = $this->applyTemplate3PdfHtmlFontScale($body);
            }
            $body = $this->promotePageChromeForPdf($body);
        } else {
            $safeCss = $this->prepareCssForPreview($safeCss);
            $body = $this->promotePageChromeForPrint($body);
            // Browser print cannot resolve mPDF page tokens.
            $body = str_replace(['{PAGENO}', '{nbpg}', '{nb}'], ['1', '1', '1'], $body);
        }

        $styleTag = $safeCss !== ''
            ? '<style type="text/css">' . $safeCss . '</style>'
            : '';

        return '<!DOCTYPE html><html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8">'
            . $styleTag
            . '</head><body>'
            . $body
            . '</body></html>';
    }

    protected function companyLogoHtml($company, int $height = 56): string
    {
        foreach ($this->companyLogoPaths($company) as $path) {
            $tag = TcpdfSafeImage::htmlImgTag($path, $height, 'Logo');
            if ($tag !== '') {
                return $tag;
            }
        }

        return '';
    }

    /**
     * Small header icons for email / phone / website (Standard Quotation contact column).
     */
    protected function contactIconHtml(string $type): string
    {
        $file = match ($type) {
            'email' => 'email.png',
            'phone' => 'phone.png',
            'website' => 'website.png',
            default => '',
        };
        if ($file === '') {
            return '';
        }

        $path = public_path('assets/quotation-icons/' . $file);
        $uri = TcpdfSafeImage::dataUri($path);
        if (!$uri) {
            return '';
        }

        // Block image inside a fixed-width cell for reliable mPDF alignment.
        return '<img class="qt-contact-icon" src="' . htmlspecialchars($uri, ENT_QUOTES, 'UTF-8')
            . '" width="12" height="12" alt=""'
            . ' style="display:block;width:12px;height:12px;border:0;margin:0;padding:0;">';
    }

    /**
     * Orange diagonal sash graphic for Standard Quotation - 2 header.
     */
    protected function sq2SashHtml(): string
    {
        $path = public_path('assets/quotation-icons/sq2-sash.png');
        $uri = TcpdfSafeImage::dataUri($path);
        if (!$uri) {
            return '&nbsp;';
        }

        return '<img class="sq2-sash-img" src="' . htmlspecialchars($uri, ENT_QUOTES, 'UTF-8')
            . '" width="90" height="120" alt=""'
            . ' style="width:90px;height:120px;display:block;margin:0 auto;">';
    }

    protected function headerContactLineHtml(string $type, ?string $value): string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return '';
        }

        // Keep individual lines as simple rows; prefer company_contact_block for Standard header.
        return $this->headerContactBlockHtml(
            $type === 'email' ? $value : '',
            $type === 'phone' ? $value : '',
            $type === 'website' ? $value : ''
        );
    }

    /**
     * Single right-aligned contact table (email / phone / website) for mPDF.
     */
    protected function headerContactBlockHtml(string $email, string $phone, string $website): string
    {
        $rows = '';
        foreach ([
            'email' => $email,
            'phone' => $phone,
            'website' => $website,
        ] as $type => $value) {
            $value = trim((string) $value);
            if ($value === '') {
                continue;
            }

            $icon = $this->contactIconHtml($type);
            $rows .= '<tr>'
                . '<td class="qt-contact-icon-cell" width="16" valign="middle"'
                . ' style="width:16px;padding:3px 10px 3px 0;vertical-align:middle;line-height:14px;">'
                . ($icon !== '' ? $icon : '&nbsp;')
                . '</td>'
                . '<td class="qt-contact-text" valign="middle"'
                . ' style="padding:3px 0;vertical-align:middle;text-align:left;font-size:10.5px;color:#444444;line-height:1.35;white-space:nowrap;">'
                . e($value)
                . '</td>'
                . '</tr>';
        }

        if ($rows === '') {
            return '';
        }

        return '<table class="qt-contact-block" cellpadding="0" cellspacing="0" border="0" align="right"'
            . ' style="border-collapse:collapse;margin:0 0 0 auto;padding:0;">'
            . $rows
            . '</table>';
    }

    /**
     * Move Standard Quotation email / phone / website to the top-right contact column.
     *
     * @param  array<string, mixed>  $data
     */
    protected function ensureStandardQuotationHeaderLayout(string $html, array $data): string
    {
        if (!preg_match('/<table\b[^>]*class=(["\'])[^"\']*\bqt-top\b[^"\']*\1[^>]*>(.*?)<\/table>/is', $html, $match)) {
            return $html;
        }

        $inner = $match[2];
        $logoCell = '';
        $companyInner = '';
        if (preg_match('/<td\b[^>]*class=(["\'])[^"\']*\bqt-logo-cell\b[^"\']*\1[^>]*>(.*?)<\/td>/is', $inner, $logoMatch)) {
            $logoCell = trim($logoMatch[2]);
        }
        $nameRowHtml = '';
        $detailsHtml = '';
        if (preg_match('/<td\b[^>]*class=(["\'])[^"\']*\bqt-company-name-row\b[^"\']*\1[^>]*>(.*?)<\/td>/is', $inner, $nameRowMatch)) {
            $nameRowHtml = trim($nameRowMatch[2]);
        }
        if (preg_match('/<td\b[^>]*class=(["\'])[^"\']*\bqt-company-cell\b[^"\']*\1[^>]*>(.*?)<\/td>/is', $inner, $companyMatch)) {
            $detailsHtml = trim($companyMatch[2]);
        }
        $companyInner = $this->stripContactLinesFromCompanyHtml(trim($nameRowHtml . $detailsHtml));

        $contactBlock = trim((string) ($data['company_contact_block'] ?? ''));
        if ($contactBlock === '') {
            $contactBlock = $this->headerContactBlockHtml(
                (string) ($data['company_email'] ?? ''),
                (string) ($data['company_phone'] ?? ''),
                (string) ($data['company_website'] ?? '')
            );
        }

        if ($logoCell === '' && $companyInner === '' && str_contains($inner, '{{')) {
            return $html;
        }

        $newTop = $this->buildQtTopTableHtml($logoCell, $companyInner, $contactBlock);
        $html = preg_replace(
            '/<table\b[^>]*class=(["\'])[^"\']*\bqt-top\b[^"\']*\1[^>]*>.*?<\/table>/is',
            $newTop,
            $html,
            1
        ) ?? $html;

        return $this->ensureStandardContactIcons($html);
    }

    /**
     * Upgrade saved qt-top header tables for Standard / New Quotation Design templates.
     */
    protected function upgradeQtTopHeaderTemplate(QuotationTemplate $template): void
    {
        if (!in_array($template->name, ['Standard Quotation', 'New Quotation Design'], true)) {
            return;
        }

        $html = (string) ($template->html_content ?? '');
        if ($html === '' || !preg_match('/\bqt-top\b/i', $html)) {
            return;
        }

        if ($template->name === 'Standard Quotation') {
            if (preg_match('/\bqt-header-frame\b/i', $html)) {
                $patched = preg_replace(
                    '/<table\b[^>]*class=(["\'])[^"\']*\bqt-top\b[^"\']*\1[^>]*>.*?<\/table>/is',
                    self::standardQuotationTopTableHtml(),
                    $html,
                    1
                );
            } else {
                $patched = preg_replace(
                    '/<table\b[^>]*class=(["\'])[^"\']*\bqt-top\b[^"\']*\1[^>]*>.*?<\/table>\s*(?:<table\b[^>]*class=(["\'])[^"\']*\bqt-rule\b[^"\']*\2[^>]*>.*?<\/table>)?/is',
                    self::standardQuotationHeaderHtml(),
                    $html,
                    1
                );
            }
        } else {
            $patched = preg_replace(
                '/<table\b[^>]*class=(["\'])[^"\']*\bqt-top\b[^"\']*\1[^>]*>.*?<\/table>/is',
                self::standardQuotationTopTableHtml(),
                $html,
                1
            );
        }

        if (!is_string($patched)) {
            return;
        }

        if ($template->name === 'Standard Quotation' && preg_match('/\bqt-header-frame\b/i', $patched)) {
            $patched = preg_replace(
                '/(<table\b[^>]*class=(["\'])[^"\']*\bqt-header-frame\b[^"\']*\2[^>]*>.*?<\/table>)\s*(?:<!--.*?-->\s*)?<table\b[^>]*class=(["\'])[^"\']*\bqt-rule\b[^"\']*\3[^>]*>.*?<\/table>/is',
                '$1',
                $patched,
                1
            ) ?? $patched;
        }

        $css = $this->appendQtTopHeaderCss((string) ($template->css_content ?? ''));
        $css = preg_replace('/\/\* qt-top-header-override \*\/[\s\S]*?\/\* end qt-top-header-override \*\//', '', $css) ?? $css;
        $css .= $this->qtTopHeaderOverrideCss();
        if ($patched === $html && $css === (string) ($template->css_content ?? '')) {
            return;
        }

        $template->forceFill([
            'html_content' => $patched,
            'css_content' => $css,
        ])->save();
    }

    protected function qtTopHeaderOverrideCss(): string
    {
        return <<<'CSS'

/* qt-top-header-override */
.qt-company-name-row { text-align: left !important; vertical-align: middle !important; padding: 0 12px 6px 0 !important; }
.qt-company-name { font-size: 12px !important; font-weight: bold !important; color: #2E7D32 !important; text-transform: uppercase !important; letter-spacing: 0.15px !important; line-height: 1.25 !important; white-space: nowrap !important; display: block !important; margin: 0 !important; }
.qt-top td.qt-logo-cell { width: 20% !important; padding: 0 28px 0 0 !important; vertical-align: middle !important; }
.qt-top td.qt-company-name-row { text-align: left !important; vertical-align: middle !important; padding: 0 12px 6px 0 !important; }
.qt-top td.qt-company-cell { width: auto !important; text-align: left !important; vertical-align: top !important; padding: 0 12px 0 0 !important; }
.qt-top td.qt-contact-cell { width: 1% !important; text-align: right !important; vertical-align: middle !important; padding: 4px 0 4px 16px !important; border-left: 1px solid #c8d6e5 !important; white-space: nowrap !important; }
.qt-contact-wrap { text-align: right !important; width: 100% !important; }
.qt-contact-block { border-collapse: collapse !important; margin: 0 0 0 auto !important; }
.qt-contact-cell { text-align: right !important; }
/* end qt-top-header-override */
CSS;
    }

    /**
     * Ensure qt-top header CSS includes mPDF-safe table layout rules.
     */
    protected function appendQtTopHeaderCss(string $css): string
    {
        $append = [
            '.qt-header-frame' => 'width:100% !important;border-collapse:collapse;margin:0 0 12px 0;',
            '.qt-header-bar' => 'height:4px !important;line-height:4px !important;font-size:1px !important;background:#2E7D32 !important;padding:0 !important;',
            '.qt-header-body' => 'padding:10px 0 !important;vertical-align:middle !important;',
            '.qt-top-spacer-cell' => 'width:100% !important;padding:0 !important;margin:0 !important;font-size:0;line-height:0;border:0;',
            '.qt-top td.qt-logo-cell' => 'width:20% !important;vertical-align:middle !important;padding:0 28px 0 0 !important;',
            '.qt-top td.qt-company-name-row' => 'text-align:left !important;vertical-align:middle !important;padding:0 12px 6px 0 !important;',
            '.qt-top td.qt-company-cell' => 'width:auto !important;text-align:left !important;vertical-align:top !important;padding:0 12px 0 0 !important;',
            '.qt-top td.qt-contact-cell' => 'width:1% !important;text-align:right !important;vertical-align:middle !important;padding:4px 0 4px 16px !important;border-left:1px solid #c8d6e5 !important;white-space:nowrap !important;',
            '.qt-company-name' => 'font-size:12px !important;font-weight:bold !important;color:#2E7D32 !important;text-transform:uppercase;letter-spacing:0.15px;line-height:1.25;white-space:nowrap !important;display:block !important;margin:0 !important;',
            '.qt-gst' => 'font-size:9px !important;font-weight:normal !important;color:#555555 !important;',
            '.qt-contact-wrap' => 'text-align:right !important;width:100% !important;',
            '.qt-contact-cell' => 'text-align:right !important;vertical-align:middle;padding:4px 0 4px 16px !important;width:1% !important;white-space:nowrap;border-left:1px solid #c8d6e5;',
            '.qt-contact-block' => 'border-collapse:collapse;margin:0 0 0 auto !important;',
            '.qt-contact-icon-cell' => 'width:16px;padding:2px 8px 2px 0;vertical-align:middle;',
            '.qt-contact-icon' => 'display:block;width:12px;height:12px;margin:0;padding:0;border:0;',
            '.qt-contact-text' => 'vertical-align:middle;text-align:left;font-size:9px;color:#555555;line-height:1.45;white-space:nowrap;',
        ];

        foreach ($append as $selector => $declarations) {
            if (!str_contains($css, $selector)) {
                $css .= "\n{$selector} { {$declarations} }\n";
            }
        }

        return $css;
    }

    /**
     * @deprecated Use upgradeQtTopHeaderTemplate()
     */
    protected function upgradeStandardQuotationHeaderTemplate(QuotationTemplate $template): void
    {
        $this->upgradeQtTopHeaderTemplate($template);
    }

    /**
     * Rebuild Standard Quotation right contact column as one right-aligned icon table.
     */
    protected function ensureStandardContactIcons(string $html): string
    {
        if (!preg_match('/\bqt-contact-cell\b/i', $html)) {
            return $html;
        }

        if (!preg_match('/<td\b[^>]*class=(["\'])[^"\']*\bqt-contact-cell\b[^"\']*\1[^>]*>/i', $html, $open, PREG_OFFSET_CAPTURE)) {
            return $html;
        }

        $openTag = $open[0][0];
        $start = $open[0][1];
        $pos = $start + strlen($openTag);
        $depth = 1;
        $len = strlen($html);

        while ($pos < $len && $depth > 0) {
            $nextOpen = stripos($html, '<td', $pos);
            $nextClose = stripos($html, '</td>', $pos);
            if ($nextClose === false) {
                return $html;
            }
            if ($nextOpen !== false && $nextOpen < $nextClose) {
                $depth++;
                $pos = $nextOpen + 3;
                continue;
            }
            $depth--;
            $pos = $nextClose + 5;
        }

        $inner = substr($html, $start + strlen($openTag), ($pos - 5) - ($start + strlen($openTag)));
        $before = substr($html, 0, $start);
        $after = substr($html, $pos);

        if (stripos($inner, '{{') !== false) {
            return $html;
        }

        $email = '';
        $phone = '';
        $website = '';
        $texts = [];
        if (preg_match_all('/qt-contact-text[^>]*>(.*?)<\/td>/is', $inner, $tm)) {
            foreach ($tm[1] as $part) {
                $text = trim(html_entity_decode(strip_tags($part), ENT_QUOTES, 'UTF-8'));
                if ($text !== '') {
                    $texts[] = $text;
                }
            }
        }
        if ($texts === []) {
            $plain = trim(html_entity_decode(strip_tags(str_replace(['<br>', '<br/>', '<br />', '</tr>'], "\n", $inner)), ENT_QUOTES, 'UTF-8'));
            foreach (preg_split('/\s*\n\s*/', $plain) ?: [] as $text) {
                $text = trim(preg_replace('/\s+/', ' ', $text) ?? $text);
                if ($text !== '') {
                    $texts[] = $text;
                }
            }
        }

        foreach ($texts as $text) {
            if ($email === '' && str_contains($text, '@')) {
                $email = $text;
            } elseif ($phone === '' && preg_match('/\+?\d[\d\s\-]{6,}/', $text)) {
                $phone = $text;
            } elseif ($website === '' && (stripos($text, 'www.') !== false || preg_match('/\.(com|in|net|org|co)\b/i', $text))) {
                $website = $text;
            }
        }

        // Prefer live company values from the already-built block when parsing fails.
        $block = $this->headerContactBlockHtml($email, $phone, $website);
        if ($block === '') {
            return $html;
        }

        $newOpen = '<td class="qt-contact-cell" width="1%" rowspan="2" valign="middle" align="right" style="width:1%;text-align:right;vertical-align:middle;padding:4px 0 4px 16px;border-left:1px solid #c8d6e5;white-space:nowrap;">';
        $wrapped = '<div class="qt-contact-wrap" style="text-align:right;width:100%;">' . $block . '</div>';

        return $before . $newOpen . $wrapped . '</td>' . $after;
    }

    /**
     * @return array<int, string>
     */
    protected function companyLogoPaths($company): array
    {
        $paths = [];
        $logo = is_object($company) ? (string) ($company->logo ?? '') : '';
        if ($logo !== '') {
            $paths[] = public_path('assets/company_image/' . ltrim($logo, '/'));
            $paths[] = public_path(ltrim($logo, '/'));
        }

        return array_values(array_filter($paths, 'is_file'));
    }

    protected function logoImgTag(string $path, int $height = 56): string
    {
        return TcpdfSafeImage::htmlImgTag($path, $height, 'Logo');
    }

    /**
     * True when template already has distinct repeating header / body / footer regions.
     */
    protected function hasCompletePageChrome(string $html): bool
    {
        return (bool) preg_match('/\bqt-page-header\b/i', $html)
            && (bool) preg_match('/\bqt-page-body\b/i', $html)
            && (bool) preg_match('/\bqt-page-footer\b/i', $html);
    }

    /**
     * Move title / quotation-no bars out of the repeating header (avoids Bill To overlap).
     */
    protected function ensureTitlebarNotInPageHeader(string $html): string
    {
        [$headerBlock, $rest] = $this->extractBlockByClass($html, 'qt-page-header');
        if ($headerBlock === '') {
            return $html;
        }

        $headerInner = $this->unwrapSingleDiv($headerBlock);
        if (!preg_match(
            '/<(table|div|section)\b[^>]*class=(["\'])[^"\']*\b(qt-titlebar|ms-titlebar|t3-titlebar|document-title)\b/i',
            $headerInner,
            $m,
            PREG_OFFSET_CAPTURE
        )) {
            return $html;
        }

        $keepHeader = trim(substr($headerInner, 0, $m[0][1]));
        $moved = trim(substr($headerInner, $m[0][1]));

        [$bodyBlock, $afterBody] = $this->extractBlockByClass($rest, 'qt-page-body');
        if ($bodyBlock === '') {
            return $html;
        }

        $bodyInner = $this->unwrapSingleDiv($bodyBlock);
        $newHeader = '<div class="qt-page-header">' . $keepHeader . '</div>';
        $newBody = '<div class="qt-page-body">' . $moved . $bodyInner . '</div>';

        return $newHeader . $newBody . $afterBody;
    }

    /**
     * Wrap template content into repeating .qt-page-header / .qt-page-body / .qt-page-footer
     * when those regions are missing (Standard, New Design, template 3, etc.).
     */
    public function ensureRepeatingPageChrome(string $html): string
    {
        if (preg_match('/\bqt-page-header\b/i', $html)) {
            return $html;
        }

        return $this->buildRepeatingPageChrome($html);
    }

    /**
     * Rebuild header/body/footer chrome even if already present (used when fixing layout).
     */
    public function rebuildRepeatingPageChrome(string $html): string
    {
        $html = $this->stripRepeatingPageChrome($html);

        return $this->buildRepeatingPageChrome($html);
    }

    /**
     * Flatten previously wrapped qt-page-header/body/footer back to a single page root.
     */
    protected function stripRepeatingPageChrome(string $html): string
    {
        if (!preg_match('/\bqt-page-header\b/i', $html)) {
            return $html;
        }

        [$header, $html] = $this->extractBlockByClass($html, 'qt-page-header');
        [$body, $html] = $this->extractBlockByClass($html, 'qt-page-body');
        [$footer, $html] = $this->extractBlockByClass($html, 'qt-page-footer');

        $headerInner = $this->unwrapSingleDiv($header);
        $bodyInner = $this->unwrapSingleDiv($body);
        $footerInner = $this->unwrapSingleDiv($footer);

        // Prefer existing page root if still present.
        foreach (['qt-page', 'quotation-page'] as $class) {
            [$block, $rest] = $this->extractBlockByClass($html, $class);
            if ($block !== '') {
                if (preg_match('/^(<div\b[^>]*>)(.*)(<\/div>)$/is', trim($block), $m)) {
                    return $rest . $m[1] . $headerInner . $bodyInner . $footerInner . $m[3];
                }
            }
        }

        return '<div class="qt-page">' . $headerInner . $bodyInner . $footerInner . '</div>';
    }

    protected function unwrapSingleDiv(string $html): string
    {
        $html = trim($html);
        if ($html === '') {
            return '';
        }
        if (preg_match('/^<div\b[^>]*>(.*)<\/div>$/is', $html, $m)) {
            return $m[1];
        }

        return $html;
    }

    protected function buildRepeatingPageChrome(string $html): string
    {
        $pageClass = null;
        $block = '';
        $rest = $html;
        foreach (['qt-page', 'quotation-page'] as $class) {
            [$block, $rest] = $this->extractBlockByClass($html, $class);
            if ($block !== '') {
                $pageClass = $class;
                break;
            }
        }

        if ($block === '') {
            $inner = trim($html);
            $openTag = '<div class="qt-page">';
            $pageClass = 'qt-page';
        } else {
            if (!preg_match('/^(<div\b[^>]*>)(.*)(<\/div>)$/is', trim($block), $m)) {
                return $html;
            }
            $openTag = $m[1];
            $inner = $m[2];
            // Ensure qt-page class exists for shared preview/print CSS.
            if ($pageClass === 'quotation-page' && !preg_match('/\bqt-page\b/i', $openTag)) {
                $openTag = preg_replace(
                    '/class=(["\'])([^"\']*)\1/i',
                    'class=$1$2 qt-page$1',
                    $openTag,
                    1
                ) ?? $openTag;
            }
        }

        [$footer, $inner] = $this->extractFooterChrome($inner);
        [$header, $body] = $this->extractHeaderChrome($inner);

        if ($header === '' && $footer === '') {
            return $html;
        }

        if ($footer === '') {
            $footer = '<table class="qt-footer" width="100%" cellpadding="0" cellspacing="0"><tr>'
                . '<td style="text-align:left;">{{footer_left}}</td>'
                . '<td class="qt-footer-right" style="text-align:right;">Page {PAGENO} of {nbpg}</td>'
                . '</tr></table>';
        } else {
            $footer = $this->ensureFooterPageNumbers($footer);
        }

        $wrapped = $openTag
            . '<div class="qt-page-header">' . trim($header) . '</div>'
            . '<div class="qt-page-body">' . trim($body) . '</div>'
            . '<div class="qt-page-footer">' . trim($footer) . '</div>'
            . '</div>';

        return $rest . $wrapped;
    }

    /**
     * @return array{0: string, 1: string} [footerHtml, remainingHtml]
     */
    protected function extractFooterChrome(string $html): array
    {
        if (preg_match('/<!--\s*=+\s*FOOTER[\s\S]*?-->\s*/i', $html, $cm, PREG_OFFSET_CAPTURE)) {
            $afterComment = $cm[0][1] + strlen($cm[0][0]);
            $slice = substr($html, $afterComment);
            if (preg_match('/^<(table|footer|div)\b[^>]*>[\s\S]*?<\/\1>/i', $slice, $tm)) {
                $footer = $tm[0];
                $fullStart = $cm[0][1];
                $fullLen = strlen($cm[0][0]) + strlen($footer);
                $afterFooter = substr($html, $fullStart + $fullLen);
                if (preg_match('/^\s*<!--[\s\S]*?END FOOTER[\s\S]*?-->/i', $afterFooter, $em)) {
                    $fullLen += strlen($em[0]);
                }
                $remaining = substr($html, 0, $fullStart) . substr($html, $fullStart + $fullLen);

                return [trim($footer), $remaining];
            }
        }

        if (preg_match(
            '/<(table|footer|div)\b[^>]*class=(["\'])[^"\']*\b(qt-footer|quotation-footer|ms-footer|t3-footer)\b[^"\']*\2[^>]*>[\s\S]*?<\/\1>/i',
            $html,
            $m,
            PREG_OFFSET_CAPTURE
        )) {
            $footer = $m[0][0];
            $start = $m[0][1];
            $remaining = substr($html, 0, $start) . substr($html, $start + strlen($footer));

            return [trim($footer), $remaining];
        }

        return ['', $html];
    }

    /**
     * @return array{0: string, 1: string} [headerHtml, bodyHtml]
     */
    protected function extractHeaderChrome(string $html): array
    {
        // Keep QUOTATION / titlebar in the body. Putting it in the repeating PDF header
        // causes BILL TO boxes to paint over the title when top margin is tight.
        if (preg_match(
            '/<(table|section|div)\b[^>]*class=(["\'])[^"\']*\b(qt-titlebar|document-title|ms-titlebar|t3-titlebar)\b/i',
            $html,
            $m,
            PREG_OFFSET_CAPTURE
        )) {
            return [trim(substr($html, 0, $m[0][1])), trim(substr($html, $m[0][1]))];
        }

        if (preg_match('/END HEADER[^\n]*-->/i', $html, $m, PREG_OFFSET_CAPTURE)) {
            $pos = $m[0][1] + strlen($m[0][0]);
            $header = substr($html, 0, $pos);
            $body = substr($html, $pos);
            $body = preg_replace('/<!--\s*=+\s*BODY CONTENT[\s\S]*?-->/i', '', $body) ?? $body;
            $body = preg_replace('/<!--\s*=+\s*END BODY[\s\S]*?-->/i', '', $body) ?? $body;

            return [trim($header), trim($body)];
        }

        // Stop before main content blocks.
        if (preg_match(
            '/<(table|section|div)\b[^>]*class=(["\'])[^"\']*\b(qt-info|info-grid|qt-intro|intro|items-section|qt-items-wrap|qt-parties|t3-parties|qt-meta-strip|ms-meta|ms-parties|ms-intro|t3-titlebar)\b/i',
            $html,
            $m,
            PREG_OFFSET_CAPTURE
        )) {
            return [trim(substr($html, 0, $m[0][1])), trim(substr($html, $m[0][1]))];
        }

        // Fallback — company/top band + optional rule/accent line only.
        if (preg_match(
            '/^([\s\S]*?<(?:header|div|section|table)\b[^>]*class=(["\'])[^"\']*\b(company-header|qt-top|ms-top|qt-hero|t3-top)\b[\s\S]*?<\/(?:header|div|section|table)>(?:\s*<div class="header-line"><\/div>)?(?:\s*<table\b[^>]*class=(["\'])[^"\']*\b(qt-rule|ms-accent|t3-rule|t3-accent)\b[\s\S]*?<\/table>)?)/i',
            $html,
            $m
        )) {
            $header = $m[1];
            $body = substr($html, strlen($header));

            return [trim($header), trim($body)];
        }

        return ['', trim($html)];
    }

    protected function ensureFooterPageNumbers(string $footer): string
    {
        if (str_contains($footer, '{PAGENO}')) {
            return $footer;
        }

        if (preg_match('/<tr\b[^>]*>\s*<td\b[^>]*>[\s\S]*?<\/td>\s*<\/tr>/i', $footer)) {
            $updated = preg_replace(
                '/(<tr\b[^>]*>\s*<td\b[^>]*>[\s\S]*?<\/td>)\s*(<\/tr>)/i',
                '$1<td class="qt-footer-right" style="text-align:right;white-space:nowrap;">Page {PAGENO} of {nbpg}</td>$2',
                $footer,
                1
            );
            if (is_string($updated) && str_contains($updated, '{PAGENO}')) {
                return $updated;
            }
        }

        if (preg_match('/<\/(footer|div)\s*>/i', $footer)) {
            $updated = preg_replace(
                '/<\/(footer|div)\s*>/i',
                '<span class="qt-footer-right" style="float:right;">Page {PAGENO} of {nbpg}</span></$1>',
                $footer,
                1
            );
            if (is_string($updated) && str_contains($updated, '{PAGENO}')) {
                return $updated;
            }
        }

        return $footer . '<div class="qt-footer-right" style="text-align:right;">Page {PAGENO} of {nbpg}</div>';
    }

    /**
     * Swap meta "Reference" fields for Proposal Version (built-in + custom templates).
     */
    protected function replaceReferenceMetaWithProposalVersion(string $html): string
    {
        // Label + value pairs that still use quotation_reference
        $patterns = [
            // <td class="qt-meta-label">Reference</td> ... {{quotation_reference}}
            [
                '/(<td\b[^>]*class=(["\'])[^"\']*\bqt-meta-label\b[^"\']*\2[^>]*>)\s*Reference\s*(<\/td>\s*<td\b[^>]*class=(["\'])[^"\']*\bqt-meta-value\b[^"\']*\3[^>]*>)\s*\{\{\s*quotation_reference\s*\}\}/is',
                '$1Proposal Version$2{{proposal_version}}',
            ],
            // template 3 style: <td>Reference</td><td class="qt-right"><strong>{{quotation_reference}}</strong>
            [
                '/(<td\b[^>]*>)\s*Reference\s*(<\/td>\s*<td\b[^>]*class=(["\'])[^"\']*\bqt-right\b[^"\']*\3[^>]*>\s*<strong>)\s*\{\{\s*quotation_reference\s*\}\}(\s*<\/strong>)/is',
                '$1Proposal Version$2{{proposal_version}}$4',
            ],
            // Executive: <div class="qt-meta-k">Reference</div><div class="qt-meta-v">{{quotation_reference}}
            [
                '/(<div\b[^>]*class=(["\'])[^"\']*\bqt-meta-k\b[^"\']*\2[^>]*>)\s*Reference\s*(<\/div>\s*<div\b[^>]*class=(["\'])[^"\']*\bqt-meta-v\b[^"\']*\3[^>]*>)\s*\{\{\s*quotation_reference\s*\}\}/is',
                '$1Proposal Version$2{{proposal_version}}',
            ],
        ];

        foreach ($patterns as [$pattern, $replacement]) {
            $updated = preg_replace($pattern, $replacement, $html);
            if (is_string($updated)) {
                $html = $updated;
            }
        }

        return $html;
    }

    /**
     * Keep subject banner under the header / above the QUOTATION title strip.
     *
     * @param  array<string, mixed>  $data
     */
    protected function ensureSubjectBlockPresent(string $html, array $data): string
    {
        $html = $this->removeBlocksByClass($html, ['qt-subject-banner']);

        $block = trim((string) ($data['subject_block'] ?? ''));
        if ($block === '') {
            return $html;
        }

        if (preg_match(
            '/<(table|div|section)\b[^>]*class=(["\'])[^"\']*\b(qt-titlebar|ms-titlebar|t3-titlebar|qt-meta-strip|qt-info|ms-meta|qt-parties|ms-parties|t3-parties|document-title)\b/i',
            $html,
            $m,
            PREG_OFFSET_CAPTURE
        )) {
            return substr($html, 0, $m[0][1]) . $block . substr($html, $m[0][1]);
        }

        if (preg_match(
            '/<(div|section)\b[^>]*class=(["\'])[^"\']*\b(qt-page-body|qt-print-body)\b[^"\']*\2[^>]*>/i',
            $html,
            $m,
            PREG_OFFSET_CAPTURE
        )) {
            $insertAt = $m[0][1] + strlen($m[0][0]);

            return substr($html, 0, $insertAt) . $block . substr($html, $insertAt);
        }

        return $block . $html;
    }

    /**
     * Insert PROJECT DETAIL block before items when the template snapshot omits the placeholder.
     */
    protected function ensureProjectDetailBlockPresent(string $html, array $data): string
    {
        $block = trim((string) ($data['project_detail_block'] ?? ''));
        if ($block === '') {
            return $html;
        }

        if (stripos($html, '>PROJECT DETAIL<') !== false || stripos($html, '{{project_detail_block}}') !== false) {
            return $html;
        }

        if (preg_match(
            '/<(div|table)\b[^>]*class=(["\'])[^"\']*\b(qt-items-wrap|qt-items-table|items-table)\b/i',
            $html,
            $m,
            PREG_OFFSET_CAPTURE
        )) {
            return substr($html, 0, $m[0][1]) . $block . substr($html, $m[0][1]);
        }

        return $html . $block;
    }

    /**
     * Keep deal/organization Quote Summary block immediately before the items table.
     *
     * @param  array<string, mixed>  $data
     */
    protected function ensureQuoteSummaryBlockPresent(string $html, array $data): string
    {
        $html = $this->removeBlocksByClass($html, ['qt-quote-summary', 'qt-product-quote-summary']);

        // Drop legacy "Dear …" intro once quote summary is used.
        $html = preg_replace(
            '/<(div|p)\b[^>]*class=(["\'])[^"\']*\b(qt-intro|ms-intro)\b[^"\']*\2[^>]*>.*?<\/\1>/is',
            '',
            $html
        ) ?? $html;

        $block = trim((string) ($data['quote_summary_block'] ?? ''));
        if ($block === '') {
            return $html;
        }

        if (preg_match(
            '/<(div|table)\b[^>]*class=(["\'])[^"\']*\b(qt-items-wrap|qt-items-table|items-table)\b/i',
            $html,
            $m,
            PREG_OFFSET_CAPTURE
        )) {
            return substr($html, 0, $m[0][1]) . $block . substr($html, $m[0][1]);
        }

        return $html . $block;
    }

    /**
     * Apply or strip forced page break before the Quotation Summary / Items section based on project details presence.
     */
    protected function applyProjectDetailPageBreak(string $html, bool $hasProjectDetail): string
    {
        if (!$hasProjectDetail) {
            return preg_replace_callback(
                '/<(div|section|table)\b([^>]*class=["\'][^"\']*\b(?:qt-quote-summary|qt-product-quote-summary|qt-items-wrap|qt-items-table|items-table)\b[^"\']*["\'][^>]*)>/i',
                function (array $m) {
                    $attrs = preg_replace('/page-break-before\s*:\s*always\s*;?/i', '', $m[2]) ?? $m[2];
                    $attrs = preg_replace('/break-before\s*:\s*page\s*;?/i', '', $attrs) ?? $attrs;

                    return '<' . $m[1] . $attrs . '>';
                },
                $html
            ) ?? $html;
        }

        if (preg_match('/<(div|section|table)\b[^>]*class=["\'][^"\']*\b(?:qt-quote-summary|qt-product-quote-summary)\b/i', $html)) {
            return preg_replace_callback(
                '/<(div|section|table)\b([^>]*class=["\'][^"\']*\b(?:qt-quote-summary|qt-product-quote-summary)\b[^"\']*["\'][^>]*)>/i',
                function (array $m) {
                    $openTag = $m[0];
                    if (str_contains($openTag, 'page-break-before')) {
                        return $openTag;
                    }
                    if (preg_match('/\sstyle=(["\'])(.*?)\1/i', $openTag, $sm)) {
                        $newStyle = rtrim($sm[2], ';') . ';page-break-before:always;break-before:page;';

                        return preg_replace('/\sstyle=(["\'])(.*?)\1/i', ' style="' . $newStyle . '"', $openTag);
                    }

                    return str_replace('>', ' style="page-break-before:always;break-before:page;">', $openTag);
                },
                $html,
                1
            ) ?? $html;
        }

        if (preg_match('/<(div|section|table)\b[^>]*class=["\'][^"\']*\b(?:qt-items-wrap|qt-items-table|items-table)\b/i', $html)) {
            return preg_replace_callback(
                '/<(div|section|table)\b([^>]*class=["\'][^"\']*\b(?:qt-items-wrap|qt-items-table|items-table)\b[^"\']*["\'][^>]*)>/i',
                function (array $m) {
                    $openTag = $m[0];
                    if (str_contains($openTag, 'page-break-before')) {
                        return $openTag;
                    }
                    if (preg_match('/\sstyle=(["\'])(.*?)\1/i', $openTag, $sm)) {
                        $newStyle = rtrim($sm[2], ';') . ';page-break-before:always;break-before:page;';

                        return preg_replace('/\sstyle=(["\'])(.*?)\1/i', ' style="' . $newStyle . '"', $openTag);
                    }

                    return str_replace('>', ' style="page-break-before:always;break-before:page;">', $openTag);
                },
                $html,
                1
            ) ?? $html;
        }

        return $html;
    }

    /**
     * Keep bank details directly after Remarks / immediately before Terms.
     *
     * @param  array<string, mixed>  $data
     */
    protected function ensureBankDetailsBlockPresent(string $html, array $data): string
    {
        // Remove existing bank blocks (balanced outer div) so placement can be corrected.
        $html = $this->removeBlocksByClass($html, ['qt-bank', 'ms-bank', 'bank-details']);

        $block = trim((string) ($data['bank_details_block'] ?? ''));
        if ($block === '') {
            return $html;
        }

        $insertAt = $this->findTermsSectionStart($html);
        if ($insertAt !== null) {
            return substr($html, 0, $insertAt) . $block . substr($html, $insertAt);
        }

        $afterRemarks = $this->findOffsetAfterRemarksSection($html);
        if ($afterRemarks !== null) {
            return substr($html, 0, $afterRemarks) . $block . substr($html, $afterRemarks);
        }

        if (preg_match('/(<div\b[^>]*class=(["\'])[^"\']*\b(?:qt-closing|ms-closing)\b)/i', $html, $m, PREG_OFFSET_CAPTURE)) {
            return substr($html, 0, $m[0][1]) . $block . substr($html, $m[0][1]);
        }

        return $html . $block;
    }

    /**
     * Start offset of the TERMS & CONDITIONS title table (not an earlier section).
     */
    protected function findTermsSectionStart(string $html): ?int
    {
        if (!preg_match_all(
            '/<table\b[^>]*class=(["\'])[^"\']*\bms-block-title-table\b[^"\']*\1[^>]*>/i',
            $html,
            $matches,
            PREG_OFFSET_CAPTURE
        )) {
            return null;
        }

        foreach ($matches[0] as $match) {
            $start = $match[1];
            $end = strpos($html, '</table>', $start);
            if ($end === false) {
                continue;
            }
            $chunk = substr($html, $start, ($end + 8) - $start);
            if (preg_match('/TERMS\s*(?:&amp;|&)\s*CONDITIONS/i', $chunk)) {
                return $start;
            }
        }

        return null;
    }

    /**
     * Offset immediately after the REMARKS title + following body content.
     */
    protected function findOffsetAfterRemarksSection(string $html): ?int
    {
        if (!preg_match_all(
            '/<table\b[^>]*class=(["\'])[^"\']*\bms-block-title-table\b[^"\']*\1[^>]*>/i',
            $html,
            $matches,
            PREG_OFFSET_CAPTURE
        )) {
            return null;
        }

        foreach ($matches[0] as $index => $match) {
            $start = $match[1];
            $end = strpos($html, '</table>', $start);
            if ($end === false) {
                continue;
            }
            $chunk = substr($html, $start, ($end + 8) - $start);
            if (!preg_match('/>\s*REMARKS\s*</i', $chunk)) {
                continue;
            }

            $afterTitle = $end + 8;
            $nextTitle = null;
            if (isset($matches[0][$index + 1])) {
                $nextTitle = $matches[0][$index + 1][1];
            }
            if ($nextTitle !== null && $nextTitle >= $afterTitle) {
                return $nextTitle;
            }

            return $afterTitle;
        }

        return null;
    }

    /**
     * Remove outer HTML blocks that include any of the given classes.
     *
     * @param  array<int, string>  $classes
     */
    protected function removeBlocksByClass(string $html, array $classes): string
    {
        foreach ($classes as $className) {
            while (true) {
                [$block, $remaining] = $this->extractBlockByClass($html, $className);
                if ($block === '') {
                    break;
                }
                $html = $remaining;
            }
        }

        return $html;
    }

    /**
     * Wrap fragment in .qt-page so A4 preview/PDF padding can apply without changing template HTML.
     */
    protected function ensurePageWrapper(string $html): string
    {
        if (preg_match('/class\s*=\s*["\'][^"\']*\bqt-page\b/i', $html)) {
            return $html;
        }

        return '<div class="qt-page">' . $html . '</div>';
    }

    /**
     * A4 canvas only. Template HTML/CSS keep their own layout, colors, and padding.
     * Print styles remove the on-screen mockup (shadow/gray) and pin header/footer.
     */
    protected function prepareCssForPreview(string $css): string
    {
        // Kill template mockup shadows so Print never shows a card shadow.
        $css = preg_replace('/box-shadow\s*:[^;}{]+;?/i', 'box-shadow: none;', $css) ?? $css;

        $css .= "\n"
            . "html, body { margin: 0; padding: 0; background: #ffffff; }\n"
            . ".qt-page, .quotation-page {\n"
            . "  width: 210mm;\n"
            . "  min-height: 297mm;\n"
            . "  max-width: 210mm;\n"
            . "  margin: 0 auto;\n"
            . "  background: #ffffff;\n"
            . "  box-shadow: none !important;\n"
            . "  box-sizing: border-box;\n"
            . "}\n"
            . ".qt-print-sheet { width: 100%; border-collapse: collapse; }\n"
            . ".qt-print-sheet > thead > tr > td,\n"
            . ".qt-print-sheet > tbody > tr > td,\n"
            . ".qt-print-sheet > tfoot > tr > td { padding: 0; vertical-align: top; }\n"
            . ".qt-page-header, .qt-print-header, .qt-page-footer, .qt-print-footer, .qt-page-body, .qt-print-body { width: 100%; }\n"
            . ".qt-footer, .quotation-footer { width: 100%; }\n"
            . ".qt-footer td,\n"
            . ".qt-footer td:first-child,\n"
            . ".qt-footer-left,\n"
            . ".ms-footer-left,\n"
            . ".t3-footer-left,\n"
            . ".sq-footer-left { text-align: left !important; }\n"
            . ".qt-footer-right,\n"
            . ".ms-footer-right,\n"
            . ".t3-footer-right,\n"
            . ".sq-footer-right,\n"
            . ".qt-footer td:last-child { text-align: right !important; white-space: nowrap; }\n"
            . ".qt-header-frame { width: 100% !important; border-collapse: collapse !important; margin: 0 0 12px 0 !important; }\n"
            . ".qt-header-bar { height: 4px !important; line-height: 4px !important; font-size: 1px !important; background: #2E7D32 !important; padding: 0 !important; }\n"
            . ".qt-header-body { padding: 10px 0 !important; vertical-align: middle !important; }\n"
            . ".qt-top { display: table !important; width: 100% !important; border-collapse: collapse !important; table-layout: auto !important; }\n"
            . ".qt-top tr { display: table-row !important; }\n"
            . ".qt-top td { display: table-cell !important; vertical-align: middle !important; }\n"
            . ".qt-top td.qt-top-spacer-cell { width: auto !important; padding: 0 !important; font-size: 0 !important; line-height: 0 !important; }\n"
            . ".qt-top td.qt-logo-cell { width: 20% !important; padding: 0 28px 0 0 !important; vertical-align: middle !important; }\n"
            . ".qt-top td.qt-company-name-row { text-align: left !important; vertical-align: middle !important; padding: 0 12px 6px 0 !important; }\n"
            . ".qt-top td.qt-company-cell { width: auto !important; text-align: left !important; padding: 0 12px 0 0 !important; vertical-align: top !important; }\n"
            . ".qt-top td.qt-contact-cell { width: 1% !important; text-align: right !important; vertical-align: middle !important; padding: 4px 0 4px 16px !important; border-left: 1px solid #c8d6e5 !important; white-space: nowrap !important; }\n"
            . ".qt-company-name { font-size: 12px !important; font-weight: bold !important; color: #2E7D32 !important; text-transform: uppercase !important; letter-spacing: 0.15px !important; line-height: 1.25 !important; white-space: nowrap !important; display: block !important; margin: 0 !important; }\n"
            . ".qt-gst { font-size: 9px !important; font-weight: normal !important; color: #555555 !important; }\n"
            . ".qt-contact-wrap { text-align: right !important; width: 100% !important; }\n"
            . ".qt-contact-cell { text-align: right !important; vertical-align: middle; padding: 4px 0 4px 16px !important; width: 1% !important; white-space: nowrap !important; border-left: 1px solid #c8d6e5 !important; }\n"
            . ".qt-contact-block { border-collapse: collapse; margin: 0 0 0 auto !important; float: none !important; }\n"
            . ".qt-contact-line { border-collapse: collapse; margin: 0; }\n"
            . ".qt-contact-icon-cell { width: 16px; padding: 3px 10px 3px 0; vertical-align: middle; }\n"
            . ".qt-contact-icon { display: block; width: 12px; height: 12px; margin: 0; padding: 0; border: 0; }\n"
            . ".qt-contact-text { vertical-align: middle; text-align: left; font-size: 10.5px; color: #444444; line-height: 1.35; padding: 3px 0; white-space: nowrap; }\n"
            . ".qt-titlebar,\n"
            . ".qt-titlebar td,\n"
            . ".qt-titlebar strong {\n"
            . "  border-bottom: none !important;\n"
            . "  text-decoration: none !important;\n"
            . "}\n"
            . ".qt-sign, .signature-section, .notes-grid, .qt-notes-row, .qt-bottom, .totals-wrapper {\n"
            . "  page-break-inside: avoid;\n"
            . "}\n"
            . ".ms-sign, .qt-sign-block, .ms-sign-block {\n"
            . "  page-break-inside: auto !important;\n"
            . "}\n"
            . "@media print {\n"
            . "  @page { size: A4; margin: 8mm; }\n"
            . "  html, body {\n"
            . "    margin: 0 !important;\n"
            . "    padding: 0 !important;\n"
            . "    background: #ffffff !important;\n"
            . "    -webkit-print-color-adjust: exact !important;\n"
            . "    print-color-adjust: exact !important;\n"
            . "  }\n"
            . "  .qt-page, .quotation-page {\n"
            . "    width: 100% !important;\n"
            . "    max-width: none !important;\n"
            . "    min-height: auto !important;\n"
            . "    margin: 0 !important;\n"
            . "    padding: 0 !important;\n"
            . "    box-shadow: none !important;\n"
            . "    background: #ffffff !important;\n"
            . "  }\n"
            . "  .qt-print-sheet { width: 100% !important; }\n"
            // Native print repeating header/footer (every page).
            . "  .qt-print-sheet > thead { display: table-header-group !important; }\n"
            . "  .qt-print-sheet > tfoot { display: table-footer-group !important; }\n"
            . "  .qt-print-sheet > tbody { display: table-row-group !important; }\n"
            . "  * {\n"
            . "    -webkit-print-color-adjust: exact !important;\n"
            . "    print-color-adjust: exact !important;\n"
            . "    box-shadow: none !important;\n"
            . "  }\n"
            . "}\n";

        return $css;
    }

    /**
     * Keep the template CSS. Force printable A4 sizing so mPDF does not emit blank pages.
     */
    protected function prepareCssForPdf(string $css): string
    {
        $css = $this->expandCssVariables($css);
        $css = preg_replace(
            '/font-family\s*:\s*(["\']?)(?:Arial|Helvetica|Segoe UI|Tahoma|Verdana|DejaVu Sans|sans-serif)\1([^;}]*)/i',
            'font-family: dejavusans',
            $css
        ) ?? $css;

        // Mobile @media often sets table/tr/td to display:block and breaks PDF tables.
        $css = $this->stripCssAtMediaBlocks($css);

        // Screen mockup rules make mPDF push content off-page / blank.
        $css = preg_replace('/@page\s*\{[^}]*\}/i', '', $css) ?? $css;
        $css = preg_replace('/\bwidth\s*:\s*210mm\s*;?/i', 'width:100%;', $css) ?? $css;
        $css = preg_replace('/\bmin-height\s*:\s*297mm\s*;?/i', 'min-height:0;', $css) ?? $css;
        $css = preg_replace('/box-shadow\s*:[^;}{]+;?/i', '', $css) ?? $css;
        $css = preg_replace('/\bmargin\s*:\s*20px\s+auto\s*;?/i', 'margin:0;', $css) ?? $css;
        $css = preg_replace('/\bdisplay\s*:\s*flex\s*;?/i', 'display:block;', $css) ?? $css;
        $css = preg_replace('/\bflex(?:-direction|-grow|-shrink|-basis)?\s*:[^;]+;?/i', '', $css) ?? $css;

        // Remove mockup page padding (15mm / 12mm) that creates large empty side gutters in PDF.
        $css = preg_replace(
            '/(\.(?:qt-page|quotation-page|qt-page-body|qt-print-body)\s*\{[^}]*?)\bpadding\s*:\s*[^;]+;?/i',
            '$1padding:0;',
            $css
        ) ?? $css;
        $css = preg_replace(
            '/(\.(?:qt-page|quotation-page|qt-page-body|qt-print-body)\s*\{[^}]*?)\bpadding-(?:left|right)\s*:\s*[^;]+;?/i',
            '$1',
            $css
        ) ?? $css;

        // Full-width content; mPDF page margins (8mm) are the only side gap.
        $css .= "\n"
            . "html, body { margin: 0; padding: 0; width: 100%; background: #ffffff !important; color: #222222 !important; font-family: dejavusans; font-size: 11px !important; }\n"
            . "body, p, li, td, th, span, div, strong, b, em, i, u, ins, a {\n"
            . "  text-decoration: none !important;\n"
            . "}\n"
            . "u, ins { border-bottom: none !important; }\n"
            . "a, a:link, a:visited { color: inherit !important; text-decoration: none !important; }\n"
            . ".qt-page, .quotation-page, .qt-page-body, .qt-print-body {\n"
            . "  display: block !important;\n"
            . "  flex: none !important;\n"
            . "}\n"
            . "h1, h2, h3, h4, h5, h6 {\n"
            . "  page-break-after: auto !important;\n"
            . "  page-break-inside: auto !important;\n"
            . "  page-break-before: auto !important;\n"
            . "}\n"
            . ".qt-page, .quotation-page {\n"
            . "  width: 100% !important;\n"
            . "  max-width: 100% !important;\n"
            . "  min-height: 0 !important;\n"
            . "  height: auto !important;\n"
            . "  margin: 0 !important;\n"
            . "  padding: 0 !important;\n"
            . "  box-shadow: none !important;\n"
            . "  background: #ffffff !important;\n"
            . "  color: #222222 !important;\n"
            . "}\n"
            . "table { width: 100%; border-collapse: collapse; }\n"
            . "img { max-width: 100%; }\n"
            . ".qt-page-header, .qt-page-footer, .qt-print-header, .qt-print-footer {\n"
            . "  width: 100% !important;\n"
            . "  margin: 0 !important;\n"
            . "  padding: 0 !important;\n"
            . "}\n"
            . ".qt-page-body, .qt-print-body {\n"
            . "  width: 100% !important;\n"
            . "  margin: 0 !important;\n"
            . "  padding: 14px 0 16px 0 !important;\n"
            . "}\n"
            . ".qt-top, .qt-titlebar, .qt-info, .qt-bottom, .qt-sign, .qt-footer,\n"
            . ".qt-items-table, .qt-meta, .qt-totals-box, .company-header, .info-grid,\n"
            . ".items-table, .totals-wrapper, .notes-grid, .signature-section, .quotation-footer {\n"
            . "  width: 100% !important;\n"
            . "}\n"
            . ".notes-grid, .qt-notes-row, .qt-bottom, .totals-wrapper,\n"
            . ".quotation-footer, .qt-footer {\n"
            . "  page-break-inside: avoid;\n"
            . "}\n"
            . ".ms-sign, table.ms-sign, table.qt-sign, .qt-sign, .signature-section,\n"
            . ".ms-sign-block, .qt-sign-block, .ms-sign td, .qt-sign td,\n"
            . ".qt-closing, .ms-closing {\n"
            . "  page-break-inside: auto !important;\n"
            . "  page-break-before: auto !important;\n"
            . "  page-break-after: auto !important;\n"
            . "  clear: both !important;\n"
            . "  float: none !important;\n"
            . "  position: static !important;\n"
            . "}\n"
            . ".qt-closing, .ms-closing {\n"
            . "  width: 100% !important;\n"
            . "  margin: 14px 0 8px 0 !important;\n"
            . "  font-family: dejavusans !important;\n"
            . "  font-size: 10px !important;\n"
            . "  color: #1e293b !important;\n"
            . "  line-height: 1.45 !important;\n"
            . "}\n"
            . ".ms-sign-clear { display: none !important; height: 0 !important; }\n"
            . ".qt-box, .customer-box, .quotation-meta, .amount-words, .qt-totals-box, .totals-table {\n"
            . "  box-shadow: none !important;\n"
            . "}\n"
            . ".qt-titlebar, .document-title {\n"
            . "  margin: 0 0 10px 0 !important;\n"
            . "  padding: 4px 0 8px 0 !important;\n"
            . "  position: static !important;\n"
            . "  border-bottom: none !important;\n"
            . "}\n"
            . ".qt-titlebar td, .qt-titlebar strong, .qt-titlebar .qt-right {\n"
            . "  border-bottom: none !important;\n"
            . "  text-decoration: none !important;\n"
            . "}\n"
            . ".qt-footer td,\n"
            . ".qt-footer td:first-child,\n"
            . ".qt-footer-left,\n"
            . ".ms-footer-left,\n"
            . ".t3-footer-left,\n"
            . ".sq-footer-left {\n"
            . "  text-align: left !important;\n"
            . "}\n"
            . ".qt-footer-right,\n"
            . ".ms-footer-right,\n"
            . ".t3-footer-right,\n"
            . ".sq-footer-right,\n"
            . ".qt-footer td:last-child {\n"
            . "  text-align: right !important;\n"
            . "  white-space: nowrap;\n"
            . "}\n"
            . ".qt-header-frame { width: 100% !important; border-collapse: collapse !important; margin: 0 0 12px 0 !important; }\n"
            . ".qt-header-bar { height: 4px !important; line-height: 4px !important; font-size: 1px !important; background: #2E7D32 !important; padding: 0 !important; }\n"
            . ".qt-header-body { padding: 10px 0 !important; vertical-align: middle !important; }\n"
            . ".qt-top { display: table !important; width: 100% !important; border-collapse: collapse !important; table-layout: auto !important; }\n"
            . ".qt-top tr { display: table-row !important; }\n"
            . ".qt-top td { display: table-cell !important; vertical-align: middle !important; }\n"
            . ".qt-top td.qt-top-spacer-cell { width: auto !important; padding: 0 !important; font-size: 0 !important; line-height: 0 !important; }\n"
            . ".qt-top td.qt-logo-cell { width: 20% !important; padding: 0 28px 0 0 !important; vertical-align: middle !important; }\n"
            . ".qt-top td.qt-company-name-row { text-align: left !important; vertical-align: middle !important; padding: 0 12px 6px 0 !important; }\n"
            . ".qt-top td.qt-company-cell { width: auto !important; text-align: left !important; padding: 0 12px 0 0 !important; vertical-align: top !important; }\n"
            . ".qt-top td.qt-contact-cell { width: 1% !important; text-align: right !important; vertical-align: middle !important; padding: 4px 0 4px 16px !important; border-left: 1px solid #c8d6e5 !important; white-space: nowrap !important; }\n"
            . ".qt-company-name { font-size: 12px !important; font-weight: bold !important; color: #2E7D32 !important; text-transform: uppercase !important; letter-spacing: 0.15px !important; line-height: 1.25 !important; white-space: nowrap !important; display: block !important; margin: 0 !important; }\n"
            . ".qt-gst { font-size: 9px !important; font-weight: normal !important; color: #555555 !important; }\n"
            . ".qt-contact-wrap { text-align: right !important; width: 100% !important; }\n"
            . ".qt-contact-cell { text-align: right !important; vertical-align: middle; padding: 4px 0 4px 16px !important; width: 1% !important; white-space: nowrap !important; border-left: 1px solid #c8d6e5 !important; }\n"
            . ".qt-contact-block { border-collapse: collapse; margin: 0 0 0 auto !important; float: none !important; }\n"
            . ".qt-contact-line { border-collapse: collapse; margin: 0; }\n"
            . ".qt-contact-icon-cell { width: 16px; padding: 3px 10px 3px 0; vertical-align: middle; }\n"
            . ".qt-contact-icon { display: block; width: 12px; height: 12px; margin: 0; padding: 0; border: 0; }\n"
            . ".qt-contact-text { vertical-align: middle; text-align: left; font-size: 10.5px; color: #444444; line-height: 1.35; padding: 3px 0; white-space: nowrap; }\n"
            . ".qt-doc-title, .document-title h1 {\n"
            . "  position: static !important;\n"
            . "  float: none !important;\n"
            . "}\n"
            . ".qt-info, .info-grid {\n"
            . "  clear: both !important;\n"
            . "  margin-top: 4px !important;\n"
            . "}\n"
            . ".ms-notes, .ms-block, .qt-section, .ms-block-wrap {\n"
            . "  width: 100% !important;\n"
            . "  max-width: 100% !important;\n"
            . "  border: 0 !important;\n"
            . "  background: transparent !important;\n"
            . "  border-collapse: collapse !important;\n"
            . "  page-break-inside: auto !important;\n"
            . "}\n"
            . ".ms-block-title-table {\n"
            . "  width: 100% !important;\n"
            . "  page-break-inside: avoid !important;\n"
            . "}\n"
            . ".ms-block-title, .qt-section .qt-label, td.ms-block-title, td.qt-label {\n"
            . "  width: 100% !important;\n"
            . "  font-family: dejavusans !important;\n"
            . "  font-size: 12px !important;\n"
            . "  font-weight: bold !important;\n"
            . "  color: #ffffff !important;\n"
            . "  text-transform: uppercase !important;\n"
            . "  text-decoration: none !important;\n"
            . "  border: 0 !important;\n"
            . "  padding: 8px 10px !important;\n"
            . "  background: #1e293b !important;\n"
            . "}\n"
            . ".ms-block-body, .qt-section-body, td.ms-block-body {\n"
            . "  width: 100% !important;\n"
            . "  font-family: dejavusans !important;\n"
            . "  font-size: 11px !important;\n"
            . "  color: #334155 !important;\n"
            . "  padding: 8px 2px 10px 2px !important;\n"
            . "  border: 0 !important;\n"
            . "  background: transparent !important;\n"
            . "  page-break-inside: auto !important;\n"
            . "  text-decoration: none !important;\n"
            . "  line-height: 1.55 !important;\n"
            . "}\n"
            . ".ms-block-body *, .qt-section-body * {\n"
            . "  page-break-inside: auto !important;\n"
            . "  text-decoration: none !important;\n"
            . "  border-bottom: none !important;\n"
            . "}\n"
            . ".ms-block-body p, .qt-section-body p,\n"
            . ".ms-block-body li, .qt-section-body li,\n"
            . ".ms-block-body td, .qt-section-body td,\n"
            . ".ms-block-body span, .qt-section-body span {\n"
            . "  font-size: 11px !important;\n"
            . "  line-height: 1.55 !important;\n"
            . "  color: #334155 !important;\n"
            . "  text-decoration: none !important;\n"
            . "}\n"
            . ".ms-block-body h1, .ms-block-body h2, .ms-block-body h3,\n"
            . ".ms-block-body h4, .qt-section-body h1, .qt-section-body h2,\n"
            . ".qt-section-body h3, .qt-section-body h4 {\n"
            . "  margin: 0 0 6px 0 !important;\n"
            . "  page-break-after: auto !important;\n"
            . "  page-break-inside: auto !important;\n"
            . "}\n"
            . ".ms-rt-h { page-break-after: auto !important; page-break-inside: auto !important; }\n"
            . ".ms-block-body > *:first-child, .qt-section-body > *:first-child { margin-top: 0 !important; }\n"
            . ".ms-block-body p, .qt-section-body p { margin: 0 0 6px 0 !important; line-height: 1.45 !important; width: auto !important; }\n"
            . ".ms-block-body ol, .ms-block-body ul, .qt-section-body ol, .qt-section-body ul {\n"
            . "  margin: 0 0 8px 18px !important;\n"
            . "  padding: 0 !important;\n"
            . "  width: auto !important;\n"
            . "  max-width: 100% !important;\n"
            . "  page-break-inside: auto !important;\n"
            . "}\n"
            . ".ms-block-body li, .qt-section-body li {\n"
            . "  margin: 0 0 5px 0 !important;\n"
            . "  width: auto !important;\n"
            . "  color: #334155 !important;\n"
            . "  page-break-inside: auto !important;\n"
            . "}\n"
            . ".ms-block-body table, .qt-section-body table {\n"
            . "  width: 100% !important;\n"
            . "  max-width: 100% !important;\n"
            . "  page-break-inside: auto !important;\n"
            . "}\n"
            // Template 3: kill flex / padded %-width cells that overlap in mPDF.
            . ".t3-shell, .t3-top, .t3-parties, .t3-bottom, .t3-panel, .t3-meta, .t3-totals, .t3-footer, .t3-titlebar, .t3-rule {\n"
            . "  width: 100% !important;\n"
            . "  border-collapse: collapse !important;\n"
            . "  clear: both !important;\n"
            . "  float: none !important;\n"
            . "  position: static !important;\n"
            . "}\n"
            . ".t3-shell-pad { padding: 0 10px !important; }\n"
            . ".t3-section, .t3-parties, .t3-bottom, .qt-items-wrap {\n"
            . "  width: 100% !important;\n"
            . "  display: block !important;\n"
            . "  clear: both !important;\n"
            . "  float: none !important;\n"
            . "  position: static !important;\n"
            . "  margin: 0 0 12px 0 !important;\n"
            . "  overflow: visible !important;\n"
            . "}\n"
            . ".t3-bill, .t3-meta-panel, .t3-words, .t3-totals-wrap {\n"
            . "  padding: 0 !important;\n"
            . "  background: transparent !important;\n"
            . "  border: 0 !important;\n"
            . "  vertical-align: top !important;\n"
            . "}\n"
            . ".t3-panel { background: #f8fafc !important; border: 1px solid #e2e8f0 !important; }\n"
            . ".t3-panel-inner { padding: 10px 12px !important; vertical-align: top !important; }\n"
            . ".t3-totals td { padding: 6px 10px !important; }\n"
            . ".qt-page-header, .qt-page-body, .qt-page-footer {\n"
            . "  display: block !important;\n"
            . "  clear: both !important;\n"
            . "  float: none !important;\n"
            . "  position: static !important;\n"
            . "}\n"
            . ".qt-quote-summary-title {\n"
            . "  color: #1e4785 !important;\n"
            . "  text-decoration: none !important;\n"
            . "  border: 0 !important;\n"
            . "  font-size: 16px !important;\n"
            . "}\n"
            . ".qt-quote-summary-intro,\n"
            . ".qt-quote-summary-heading,\n"
            . ".qt-subject-banner,\n"
            . ".qt-subject-banner-text {\n"
            . "  color: #222222 !important;\n"
            . "  text-decoration: none !important;\n"
            . "  border: 0 !important;\n"
            . "  border-top: 0 !important;\n"
            . "  border-bottom: 0 !important;\n"
            . "}\n"
            . ".qt-subject-banner-text { color: #1e4785 !important; font-size: 16px !important; }\n"
            . "a, a:link, a:visited, u, ins {\n"
            . "  color: inherit !important;\n"
            . "  text-decoration: none !important;\n"
            . "  border-bottom: none !important;\n"
            . "}\n"
            . ".t3-titlebar,\n"
            . ".t3-titlebar td,\n"
            . ".t3-titlebar strong,\n"
            . ".t3-k {\n"
            . "  border-bottom: none !important;\n"
            . "  text-decoration: none !important;\n"
            . "}\n"
            . ".qt-items-table { font-size: 10px !important; }\n"
            . ".ms-block-body, .qt-section-body, td.ms-block-body,\n"
            . ".ms-block-body *, .qt-section-body * {\n"
            . "  text-decoration: none !important;\n"
            . "  border-bottom-color: transparent;\n"
            . "}\n"
            . ".ms-block-body, .qt-section-body, td.ms-block-body {\n"
            . "  color: #334155 !important;\n"
            . "  font-size: 11px !important;\n"
            . "  line-height: 1.55 !important;\n"
            . "}\n";

        return $css;
    }

    protected function isTemplate3Stylesheet(string $css, string $html = ''): bool
    {
        return stripos($css, '.t3-') !== false
            || stripos($css, 't3-shell') !== false
            || stripos($html, 't3-shell') !== false
            || stripos($html, 'class="t3-') !== false
            || stripos($html, "class='t3-") !== false;
    }

    /**
     * Template 3 only: raise table/body fonts to match Standard Quotation readability.
     * Uses concrete font-size values (mPDF often ignores !important).
     */
    protected function applyTemplate3PdfFontScale(string $css): string
    {
        // Drop earlier shared shrinks that make Template 3 look tiny.
        $css = preg_replace(
            '/\.t3-line\s*,\s*\.t3-meta\s+td\s*,\s*\.t3-customer\s*,\s*\.t3-label\s*\{[^}]*\}/i',
            '',
            $css
        ) ?? $css;
        $css = preg_replace('/\.qt-items-table\s*\{\s*font-size\s*:\s*10px\s*!important\s*;\s*\}/i', '', $css) ?? $css;
        // Neutralize shared PDF shrinks (mPDF often keeps earlier !important sizes).
        $css = preg_replace('/font-size\s*:\s*10px\s*!important/i', 'font-size: 11px', $css) ?? $css;
        $css = preg_replace('/font-size\s*:\s*11px\s*!important/i', 'font-size: 12px', $css) ?? $css;

        $rewrites = [
            '/(\.t3-muted\b[^{]*\{[^}]*font-size\s*:\s*)[^;]+/i' => '$1 10px',
            '/(\.t3-contact-line\b[^{]*\{[^}]*font-size\s*:\s*)[^;]+/i' => '$1 10px',
            '/(\.t3-titlebar\s+\.t3-right\b[^{]*\{[^}]*font-size\s*:\s*)[^;]+/i' => '$1 11px',
            '/(\.t3-k\b[^{]*\{[^}]*font-size\s*:\s*)[^;]+/i' => '$1 9px',
            '/(\.t3-label\b[^{]*\{[^}]*font-size\s*:\s*)[^;]+/i' => '$1 10px',
            '/(\.t3-customer\b[^{]*\{[^}]*font-size\s*:\s*)[^;]+/i' => '$1 13px',
            '/(\.t3-line\b[^{]*\{[^}]*font-size\s*:\s*)[^;]+/i' => '$1 11px',
            '/(\.t3-meta\s+td\b[^{]*\{[^}]*font-size\s*:\s*)[^;]+/i' => '$1 11px',
            '/(\.qt-items-table\b[^{]*\{[^}]*font-size\s*:\s*)[^;]+/i' => '$1 11px',
            '/(\.qt-item-meta\b[^{]*\{[^}]*font-size\s*:\s*)[^;]+/i' => '$1 9px',
            '/(\.t3-words-text\b[^{]*\{[^}]*font-size\s*:\s*)[^;]+/i' => '$1 11px',
            '/(\.t3-totals\s+td\b[^{]*\{[^}]*font-size\s*:\s*)[^;]+/i' => '$1 11px',
            '/(\.t3-grand\s+td\b[^{]*\{[^}]*font-size\s*:\s*)[^;]+/i' => '$1 13px',
            '/(\.qt-closing\b[^{]*\{[^}]*font-size\s*:\s*)[^;]+/i' => '$1 11px',
            '/(\.qt-quote-summary-intro\b[^{]*\{[^}]*font-size\s*:\s*)[^;]+/i' => '$1 12px',
        ];
        foreach ($rewrites as $pattern => $replacement) {
            $css = preg_replace($pattern, $replacement, $css) ?? $css;
        }

        // Append last so mPDF picks these concrete sizes over earlier tiny rules.
        $css .= "\n/* Template 3 PDF fonts (match other templates) */\n"
            . "html, body { font-size: 12px; line-height: 1.55; }\n"
            . ".t3-company { font-size: 15px; }\n"
            . ".t3-muted, .t3-contact-line { font-size: 10px; line-height: 1.5; }\n"
            . ".t3-doc-title { font-size: 22px; }\n"
            . ".t3-titlebar .t3-right { font-size: 11px; }\n"
            . ".t3-k { font-size: 9px; }\n"
            . ".t3-label { font-size: 10px; }\n"
            . ".t3-customer { font-size: 13px; }\n"
            . ".t3-line, .t3-meta td, .t3-meta-k, .t3-meta-v { font-size: 11px; line-height: 1.55; }\n"
            . ".qt-subject-banner-text, .qt-quote-summary-title { font-size: 16px; }\n"
            . ".qt-quote-summary-intro { font-size: 12px; line-height: 1.55; }\n"
            . ".qt-quote-summary-heading { font-size: 13px; }\n"
            . ".qt-items-table, .qt-items-table th, .qt-items-table td { font-size: 11px; line-height: 1.45; }\n"
            . ".qt-items-table th { padding: 8px 5px; }\n"
            . ".qt-items-table td { padding: 8px 5px; }\n"
            . ".qt-item-meta { font-size: 9px; }\n"
            . ".t3-words-text { font-size: 11px; }\n"
            . ".t3-totals td { font-size: 11px; }\n"
            . ".t3-grand td { font-size: 13px; }\n"
            . ".ms-block-title, .qt-section .qt-label, td.ms-block-title, td.qt-label { font-size: 12px; }\n"
            . ".ms-block-body, .qt-section-body, td.ms-block-body { font-size: 12px; line-height: 1.6; }\n"
            . ".ms-block-body p, .qt-section-body p,\n"
            . ".ms-block-body li, .qt-section-body li,\n"
            . ".ms-block-body td, .qt-section-body td,\n"
            . ".ms-block-body span, .qt-section-body span { font-size: 12px; line-height: 1.6; }\n"
            . ".ms-block-body h1, .qt-section-body h1, .ms-rt-h { font-size: 14px; }\n"
            . ".ms-block-body h2, .qt-section-body h2 { font-size: 13px; }\n"
            . ".ms-block-body h3, .qt-section-body h3,\n"
            . ".ms-block-body h4, .qt-section-body h4 { font-size: 12px; }\n"
            . ".qt-bank td, .ms-bank td, .bank-details td { font-size: 11px; }\n"
            . ".qt-closing, .ms-closing { font-size: 11px; line-height: 1.5; }\n"
            . ".t3-footer-left, .t3-footer-right { font-size: 9px; }\n"
            . ".qt-pricing-card, .qt-product-items-table { width: 100% !important; border-collapse: collapse; }\n"
            . ".qt-pricing-card td, .qt-product-items-table th, .qt-product-items-table td { border: 1px solid #9cc3c3; padding: 8px 10px; vertical-align: middle; font-size: 11px; }\n"
            . ".qt-pricing-card .qt-pricing-head td { background: #2aa3a3 !important; color: #ffffff !important; font-weight: bold; }\n"
            . ".qt-pricing-card .qt-pricing-discount td { color: #dc3545 !important; font-weight: bold; }\n"
            . ".qt-product-items-table thead th { background: #2E7D32 !important; color: #ffffff !important; font-weight: bold; text-align: center; border-color: #2E7D32 !important; }\n"
            . ".qt-product-items-table tfoot td { font-weight: bold; background: #f8fafc !important; }\n";

        return $css;
    }

    /**
     * Template 3 only: bump small inline font-size values so mPDF honors them.
     */
    protected function applyTemplate3PdfHtmlFontScale(string $html): string
    {
        return preg_replace_callback(
            '/font-size\s*:\s*(\d+(?:\.\d+)?)px/i',
            static function (array $m): string {
                $n = (float) $m[1];
                if ($n <= 8) {
                    return 'font-size:10px';
                }
                if ($n <= 9) {
                    return 'font-size:11px';
                }
                if ($n <= 10) {
                    return 'font-size:11px';
                }
                if ($n <= 11) {
                    return 'font-size:12px';
                }

                return $m[0];
            },
            $html
        ) ?? $html;
    }
    protected function stripCssAtMediaBlocks(string $css): string
    {
        $out = '';
        $len = strlen($css);
        $i = 0;
        while ($i < $len) {
            if (preg_match('/@media\b/i', $css, $m, PREG_OFFSET_CAPTURE, $i) !== 1) {
                $out .= substr($css, $i);
                break;
            }

            $start = $m[0][1];
            $out .= substr($css, $i, $start - $i);
            $brace = strpos($css, '{', $start);
            if ($brace === false) {
                break;
            }

            $depth = 0;
            $j = $brace;
            for (; $j < $len; $j++) {
                $ch = $css[$j];
                if ($ch === '{') {
                    $depth++;
                } elseif ($ch === '}') {
                    $depth--;
                    if ($depth === 0) {
                        $j++;
                        break;
                    }
                }
            }
            $i = $j;
        }

        return $out;
    }

    /**
     * Replace var(--token) with declared :root values so mPDF can apply colors.
     */
    protected function expandCssVariables(string $css): string
    {
        $vars = [];
        if (preg_match_all('/--([a-z0-9_-]+)\s*:\s*([^;]+);/i', $css, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $vars['--' . strtolower($match[1])] = trim($match[2]);
            }
        }

        if ($vars === []) {
            return $css;
        }

        return preg_replace_callback(
            '/var\(\s*(--[a-z0-9_-]+)\s*(?:,\s*([^)]+))?\)/i',
            function (array $m) use ($vars) {
                $key = strtolower($m[1]);
                if (isset($vars[$key])) {
                    return $vars[$key];
                }

                return isset($m[2]) ? trim($m[2]) : $m[0];
            },
            $css
        ) ?? $css;
    }

    protected function adaptHtmlForPdf(string $html): string
    {
        $map = [
            '<header' => '<div',
            '</header>' => '</div>',
            '<footer' => '<div',
            '</footer>' => '</div>',
            '<section' => '<div',
            '</section>' => '</div>',
            '<article' => '<div',
            '</article>' => '</div>',
            '<nav' => '<div',
            '</nav>' => '</div>',
            '<main' => '<div',
            '</main>' => '</div>',
        ];

        $html = str_ireplace(array_keys($map), array_values($map), $html);

        // A full items <table> inside another table's thead/tbody/tfoot is invalid.
        $html = preg_replace(
            '#(<(?:thead|tbody|tfoot)\b[^>]*>)\s*(<table\b[^>]*>.*?</table>)\s*(</(?:thead|tbody|tfoot)>)#is',
            '$2',
            $html
        ) ?? $html;

        return $html;
    }

    /**
     * Remove Word/TinyMCE hyperlink styling (blue + underline) that breaks PDF readability.
     */
    protected function sanitizePdfTextStyles(string $html): string
    {
        $html = $this->stripUnderlineMarkup($html);

        // Quote summary should stay readable (not hyperlink-styled).
        $html = preg_replace(
            '/(class=(["\'])[^"\']*\bqt-quote-summary-title\b[^"\']*\2[^>]*)(?:\sstyle=(["\'])[^"\']*\3)?/i',
            '$1 style="color:#1e4785;font-size:16px;font-weight:bold;margin:0 0 8px 0;line-height:1.35;text-decoration:none;border:0;"',
            $html
        ) ?? $html;

        $html = preg_replace(
            '/(class=(["\'])[^"\']*\bqt-quote-summary-intro\b[^"\']*\2[^>]*)(?:\sstyle=(["\'])[^"\']*\3)?/i',
            '$1 style="text-align:left;color:#222222;font-size:12px;margin:0 0 10px 0;line-height:1.5;text-decoration:none;border:0;"',
            $html
        ) ?? $html;

        $html = preg_replace(
            '/(class=(["\'])[^"\']*\bqt-subject-banner-text\b[^"\']*\2[^>]*)(?:\sstyle=(["\'])[^"\']*\3)?/i',
            '$1 style="color:#1e4785;font-size:16px;font-weight:bold;line-height:1.4;text-decoration:none;border:0;"',
            $html
        ) ?? $html;

        return $html;
    }

    /**
     * Strip underline tags/styles and link wrappers from PDF HTML.
     */
    protected function stripUnderlineMarkup(string $html): string
    {
        // Unwrap anchors / underline tags but keep inner text.
        $html = preg_replace('/<\/?(?:a|u|ins)\b[^>]*>/i', '', $html) ?? $html;

        // Drop Google Docs / Word noise attrs that confuse mPDF.
        $html = preg_replace('/\s(?:dir|role|aria-level|aria-checked|aria-posinset|aria-setsize)=(["\'])[^"\']*\1/i', '', $html) ?? $html;

        // Only scrub text nodes. Never force border-bottom:none on td/th/div —
        // that overrides template box/table borders in mPDF.
        $html = preg_replace_callback(
            '#<(p|li|span|strong|b|em|i|h1|h2|h3|h4|h5|h6)(\s[^>]*)?>#i',
            function (array $m): string {
                $tag = strtolower($m[1]);
                $attrs = $m[2] ?? '';
                if (preg_match('/\sstyle=(["\'])(.*?)\1/i', $attrs, $sm)) {
                    $attrs = preg_replace(
                        '/\sstyle=(["\'])(.*?)\1/i',
                        ' style="' . $this->normalizeNoUnderlineStyle($sm[2], true) . '"',
                        $attrs,
                        1
                    );

                    return '<' . $tag . $attrs . '>';
                }

                return '<' . $tag . $attrs . ' style="text-decoration:none;border-bottom:none;">';
            },
            $html
        ) ?? $html;

        // Structural tags: strip link-blue / text-decoration only; keep borders.
        $html = preg_replace_callback(
            '#<(td|th|div|table)(\s[^>]*)?>#i',
            function (array $m): string {
                $tag = strtolower($m[1]);
                $attrs = $m[2] ?? '';
                if (!preg_match('/\sstyle=(["\'])(.*?)\1/i', $attrs, $sm)) {
                    return '<' . $tag . $attrs . '>';
                }

                $attrs = preg_replace(
                    '/\sstyle=(["\'])(.*?)\1/i',
                    ' style="' . $this->normalizeNoUnderlineStyle($sm[2], false) . '"',
                    $attrs,
                    1
                );

                return '<' . $tag . $attrs . '>';
            },
            $html
        ) ?? $html;

        return $html;
    }

    protected function normalizeNoUnderlineStyle(string $style, bool $stripBorderBottom = true): string
    {
        $style = preg_replace('/(^|;)\s*text-decoration(?:-line|-color|-style|-thickness)?\s*:[^;]*/i', '$1', $style) ?? $style;
        $style = preg_replace('/(^|;)\s*-webkit-text-decorations-in-effect\s*:[^;]*/i', '$1', $style) ?? $style;
        if ($stripBorderBottom) {
            $style = preg_replace('/(^|;)\s*border-bottom\s*:[^;]*/i', '$1', $style) ?? $style;
        }
        $style = preg_replace(
            '/(^|;)\s*color\s*:\s*(?:#0{0,2}56[3c]1|#0563c1|#1155cc|#2f6fed|#0000ee|#0000ff|blue|rgb\s*\(\s*0\s*,\s*0\s*,\s*238\s*\)|rgb\s*\(\s*5\s*,\s*99\s*,\s*193\s*\)|rgb\s*\(\s*17\s*,\s*85\s*,\s*204\s*\)|rgb\s*\(\s*47\s*,\s*111\s*,\s*237\s*\))\s*/i',
            '$1',
            $style
        ) ?? $style;
        $style = trim(preg_replace('/;;+/', ';', $style) ?? $style, "; \t\n\r");
        $style = rtrim($style, ';');

        if ($style === '') {
            return $stripBorderBottom ? 'text-decoration:none;border-bottom:none' : 'text-decoration:none';
        }

        return $stripBorderBottom
            ? ($style . ';text-decoration:none;border-bottom:none')
            : ($style . ';text-decoration:none');
    }

    /**
     * Compact closing used by all quotation templates (no Authorized Signatory box).
     *
     * @param  array<string, mixed>  $data
     */
    protected function renderClosingSignoffHtml(array $data): string
    {
        $signature = trim(strip_tags((string) ($data['signature'] ?? '')));
        $designation = trim(strip_tags((string) ($data['designation'] ?? '')));
        $companyName = trim(strip_tags((string) ($data['company_name'] ?? '')));

        if ($signature === '' && $designation === '' && $companyName === '') {
            return '';
        }

        $lines = '<div class="qt-closing-sincerely ms-closing-sincerely qt-closing-line ms-closing-line" style="margin:0 0 12px 0;">Sincerely,</div>';
        if ($signature !== '') {
            $lines .= '<div class="qt-closing-name ms-closing-name qt-closing-line ms-closing-line" style="margin:0;font-weight:bold;">'
                . e($signature) . '</div>';
        }
        if ($designation !== '') {
            $lines .= '<div class="qt-closing-line ms-closing-line" style="margin:0;">' . e($designation) . '</div>';
        }
        if ($companyName !== '') {
            $lines .= '<div class="qt-closing-line ms-closing-line" style="margin:0;">' . e($companyName) . '</div>';
        }

        return '<div class="qt-closing ms-closing" style="width:100%;margin:14px 0 8px 0;padding:0;clear:both;page-break-inside:auto;page-break-before:auto;font-family:dejavusans;font-size:10px;color:#1e293b;line-height:1.45;">'
            . $lines
            . '</div>';
    }

    /**
     * Bank account table shown between Remarks and Terms when enabled on the quotation.
     */
    protected function renderBankDetailsBlock($company, bool $enabled): string
    {
        if (! $enabled || ! $company) {
            return '';
        }

        $account = trim((string) ($company->bank_account_number ?? ''));
        $bankName = trim((string) ($company->bank_name ?? ''));
        $branch = trim((string) ($company->bank_branch ?? ''));
        $address = trim((string) ($company->bank_address ?? ''));
        $ifsc = trim((string) ($company->bank_ifsc ?? ''));
        $companyName = trim((string) ($company->name ?? ''));
        $email = trim((string) ($company->email ?? ''));

        if ($account === '' && $bankName === '' && $branch === '' && $address === '' && $ifsc === '') {
            return '';
        }

        $title = 'Bank Account details' . ($companyName !== '' ? ': ' . e($companyName) : '');
        $cell = 'border:1px solid #333333;padding:6px 8px;font-family:dejavusans;font-size:10px;color:#111111;vertical-align:top;text-decoration:none;';
        $head = $cell . 'background:#dcefdc;font-weight:bold;text-align:left;';

        $html = '<div class="qt-bank ms-bank bank-details" style="width:100%;margin:12px 0 10px 0;page-break-inside:auto;clear:both;text-decoration:none;">'
            . '<div style="font-family:dejavusans;font-size:12px;font-weight:bold;color:#111111;margin:0 0 6px 0;text-decoration:none;">'
            . $title
            . '</div>'
            . '<table width="100%" cellpadding="0" cellspacing="0" border="0" style="width:100%;border-collapse:collapse;border:1px solid #333333;">'
            . '<tr>'
            . '<td style="' . $head . 'width:18%;">Account Number</td>'
            . '<td style="' . $head . 'width:18%;">Bank Name</td>'
            . '<td style="' . $head . 'width:16%;">Branch</td>'
            . '<td style="' . $head . 'width:30%;">Full Address of the Bank</td>'
            . '<td style="' . $head . 'width:18%;">IFSC/NEFT Code</td>'
            . '</tr>'
            . '<tr>'
            . '<td style="' . $cell . '">' . ($account !== '' ? e($account) : '&nbsp;') . '</td>'
            . '<td style="' . $cell . '">' . ($bankName !== '' ? e($bankName) : '&nbsp;') . '</td>'
            . '<td style="' . $cell . '">' . ($branch !== '' ? e($branch) : '&nbsp;') . '</td>'
            . '<td style="' . $cell . '">' . ($address !== '' ? e($address) : '&nbsp;') . '</td>'
            . '<td style="' . $cell . '">' . ($ifsc !== '' ? e($ifsc) : '&nbsp;') . '</td>'
            . '</tr>'
            . '</table>';

        if ($email !== '') {
            $html .= '<div style="font-family:dejavusans;font-size:10px;color:#333333;margin:6px 0 0 0;text-decoration:none;">'
                . 'Email: ' . e($email)
                . '</div>';
        }

        return $html . '</div>';
    }

    protected function designationForUser($user): string
    {
        if (! $user) {
            return '';
        }

        $direct = trim((string) ($user->designation ?? $user->job_title ?? ''));
        if ($direct !== '') {
            return $direct;
        }

        return match ((int) ($user->crm_role_type ?? -1)) {
            0 => 'Super Admin',
            1 => 'Admin',
            2 => 'Manager',
            3 => 'Sales Executive',
            default => '',
        };
    }

    /**
     * Force every quotation (including old custom snapshots) to use the compact
     * Sincerely closing directly under Terms — never a multi-page Authorized Signatory box.
     *
     * @param  array<string, mixed>  $data
     */
    protected function ensureClosingSignoffPresent(string $html, array $data): string
    {
        $closing = trim((string) ($data['closing_block'] ?? ''));
        if ($closing === '') {
            $closing = $this->renderClosingSignoffHtml($data);
        }
        if ($closing === '') {
            return $html;
        }

        // Strip forced page-breaks left from older PDF workarounds.
        $html = preg_replace('/<pagebreak\b[^>]*\/?>/i', '', $html) ?? $html;
        $html = preg_replace(
            '/<div\b[^>]*class=(["\'])[^"\']*\bms-sign-clear\b[^"\']*\1[^>]*>.*?<\/div>/is',
            '',
            $html
        ) ?? $html;

        $replaced = false;
        $html = preg_replace_callback(
            '/<(table|div)\b[^>]*class=(["\'])[^"\']*\b(?:ms-sign|qt-sign|signature-section)\b[^"\']*\2[^>]*>.*?<\/\1>/is',
            static function () use ($closing, &$replaced) {
                $replaced = true;

                return $closing;
            },
            $html
        ) ?? $html;

        // Catch leftover Authorized Signatory markup without the usual classes.
        if (! $replaced && stripos($html, 'Authorized Signatory') !== false) {
            $html = preg_replace(
                '/<(table|div)\b[^>]*>[^<]*(?:For\s+[^<]*)?[^<]*Authorized Signatory[\s\S]*?<\/\1>/i',
                $closing,
                $html,
                1,
                $count
            ) ?? $html;
            $replaced = ($count ?? 0) > 0;
        }

        if (stripos($html, 'Sincerely') !== false) {
            return $html;
        }

        // Insert immediately after Terms block when possible.
        if (preg_match(
            '/TERMS\s*(?:&amp;|&)\s*CONDITIONS/i',
            $html,
            $m,
            PREG_OFFSET_CAPTURE
        )) {
            $from = $m[0][1];
            // End of the terms section wrapper (title table + following body chunk).
            if (preg_match('/<\/(?:table|div)>/i', $html, $endM, PREG_OFFSET_CAPTURE, $from)) {
                // Prefer the end of the terms body block if present shortly after the title.
                $slice = substr($html, $from, 12000);
                if (preg_match(
                    '/<\/table>[\s\S]*?(?:<\/div>\s*){1,3}(?=<table\b|<div\b[^>]*class=(["\'])[^"\']*\b(?:qt-closing|ms-closing|qt-page-footer|ms-sign|qt-sign)|$)/i',
                    $slice,
                    $blockM,
                    PREG_OFFSET_CAPTURE
                )) {
                    $end = $from + $blockM[0][1] + strlen($blockM[0][0]);

                    return substr($html, 0, $end) . $closing . substr($html, $end);
                }
            }
        }

        if (preg_match('/(<div\b[^>]*class=(["\'])[^"\']*\bqt-page-footer\b)/i', $html, $m, PREG_OFFSET_CAPTURE)) {
            return substr($html, 0, $m[0][1]) . $closing . substr($html, $m[0][1]);
        }

        return $html . $closing;
    }

    /**
     * Promote .qt-page-header / .qt-page-footer into mPDF repeating page chrome.
     */
    protected function promotePageChromeForPdf(string $html): string
    {
        [$headerBlock, $html] = $this->extractBlockByClass($html, 'qt-page-header');
        [$footerBlock, $html] = $this->extractBlockByClass($html, 'qt-page-footer');

        if ($headerBlock === '' && $footerBlock === '') {
            return $html;
        }

        $headerInner = $this->unwrapSingleDiv($headerBlock);
        $footerInner = $this->unwrapSingleDiv($footerBlock);

        // Flatten the body wrapper so flex/min-height on .qt-page-body cannot
        // reserve a blank region under PROJECT DETAIL.
        [$bodyBlock, $html] = $this->extractBlockByClass($html, 'qt-page-body');
        if ($bodyBlock !== '') {
            $html = $this->unwrapSingleDiv($bodyBlock) . $html;
        }

        $chrome = '';
        if ($headerInner !== '') {
            $chrome .= '<htmlpageheader name="qtHeader">'
                . '<div class="qt-page-header">' . $headerInner . '</div>'
                . '</htmlpageheader>';
        }
        if ($footerInner !== '') {
            $chrome .= '<htmlpagefooter name="qtFooter">'
                . '<div class="qt-page-footer">' . $footerInner . '</div>'
                . '</htmlpagefooter>';
        }
        if ($headerInner !== '') {
            // value=on repeats on every page; show-this-page draws it on the first page too.
            $chrome .= '<sethtmlpageheader name="qtHeader" value="on" show-this-page="1" />';
        }
        if ($footerInner !== '') {
            $chrome .= '<sethtmlpagefooter name="qtFooter" value="on" show-this-page="1" />';
        }

        return $chrome . $html;
    }

    /**
     * Wrap header/body/footer in a print table so browsers repeat thead/tfoot on every page.
     */
    protected function promotePageChromeForPrint(string $html): string
    {
        if (!preg_match('/\bqt-page-header\b/i', $html)) {
            return $html;
        }

        $pageBlock = '';
        $rest = $html;
        $openTag = '<div class="qt-page">';
        foreach (['qt-page', 'quotation-page'] as $class) {
            [$pageBlock, $rest] = $this->extractBlockByClass($html, $class);
            if ($pageBlock !== '') {
                if (preg_match('/^(<div\b[^>]*>)(.*)(<\/div>)$/is', trim($pageBlock), $m)) {
                    $openTag = $m[1];
                    $inner = $m[2];
                } else {
                    return $html;
                }
                break;
            }
        }

        if ($pageBlock === '') {
            $inner = $html;
            $rest = '';
        }

        [$headerBlock, $inner] = $this->extractBlockByClass($inner, 'qt-page-header');
        [$bodyBlock, $inner] = $this->extractBlockByClass($inner, 'qt-page-body');
        [$footerBlock, $inner] = $this->extractBlockByClass($inner, 'qt-page-footer');

        $headerInner = $this->unwrapSingleDiv($headerBlock);
        $bodyInner = $this->unwrapSingleDiv($bodyBlock);
        $footerInner = $this->unwrapSingleDiv($footerBlock);

        // Anything left over (unexpected markup) stays with the body.
        $bodyInner .= $inner;

        if ($headerInner === '' && $footerInner === '') {
            return $html;
        }

        $sheet = '<table class="qt-print-sheet" width="100%" cellpadding="0" cellspacing="0">'
            . '<thead><tr><td class="qt-print-header qt-page-header">' . $headerInner . '</td></tr></thead>'
            . '<tfoot><tr><td class="qt-print-footer qt-page-footer">' . $footerInner . '</td></tr></tfoot>'
            . '<tbody><tr><td class="qt-print-body qt-page-body">' . $bodyInner . '</td></tr></tbody>'
            . '</table>';

        return $rest . $openTag . $sheet . '</div>';
    }

    /**
     * Extract the outer HTML of the first div with the given class (balanced tags).
     *
     * @return array{0: string, 1: string} [innerOrBlockHtml, remainingHtml]
     */
    protected function extractBlockByClass(string $html, string $className): array
    {
        $pattern = '/<div\b[^>]*class=(["\'])[^"\']*\b'
            . preg_quote($className, '/')
            . '\b[^"\']*\1[^>]*>/i';

        if (!preg_match($pattern, $html, $m, PREG_OFFSET_CAPTURE)) {
            return ['', $html];
        }

        $openTag = $m[0][0];
        $start = $m[0][1];
        $pos = $start + strlen($openTag);
        $depth = 1;
        $len = strlen($html);

        while ($pos < $len && $depth > 0) {
            $nextOpen = stripos($html, '<div', $pos);
            $nextClose = stripos($html, '</div>', $pos);

            if ($nextClose === false) {
                return ['', $html];
            }

            if ($nextOpen !== false && $nextOpen < $nextClose) {
                $depth++;
                $pos = $nextOpen + 4;
                continue;
            }

            $depth--;
            $pos = $nextClose + 6;
        }

        $block = substr($html, $start, $pos - $start);
        $remaining = substr($html, 0, $start) . substr($html, $pos);

        // Keep the block HTML (including the wrapper div) for header/footer styling.
        return [$block, $remaining];
    }

    /**
     * Accept either an HTML fragment or a full document and return body + extra CSS.
     *
     * @return array{body: string, css: string}
     */
    protected function extractDocumentParts(string $html): array
    {
        $css = '';
        if (preg_match_all('#<style[^>]*>(.*?)</style>#is', $html, $matches)) {
            $css = implode("\n", $matches[1]);
            $html = preg_replace('#<style[^>]*>.*?</style>#is', '', $html) ?? $html;
        }

        if (preg_match('#<body[^>]*>(.*)</body>#is', $html, $m)) {
            $html = $m[1];
        }

        $html = preg_replace('#<!DOCTYPE[^>]*>#i', '', $html) ?? $html;
        $html = preg_replace('#</?(html|head|body|title|meta|link)\b[^>]*>#i', '', $html) ?? $html;

        return [
            'body' => trim($html),
            'css' => trim($css),
        ];
    }

    /**
     * Preview uses only the HTML/CSS passed in (editor or saved template). No default stylesheet.
     */
    public function renderPreviewHtml(?string $html = null, ?string $css = null, ?array $data = null): string
    {
        return $this->composeDocument(
            (string) ($html ?? ''),
            (string) ($css ?? ''),
            $data ?? $this->buildSampleData(),
            false
        );
    }

    /**
     * Render final HTML for a stored quotation using snapshot or linked template.
     * Keeps the custom template HTML/CSS exactly as saved — no live overwrite.
     */
    public function renderQuotationHtml(Quotation $quotation, ?Company $company = null): string
    {
        $company = $company ?: Company::first();
        $data = $this->buildDataFromQuotation($quotation, $company);

        $html = $quotation->template_html_snapshot;
        $css = $quotation->template_css_snapshot;

        if (empty($html) && $quotation->quotation_template_version_id) {
            $version = $quotation->quotationTemplateVersion
                ?: QuotationTemplateVersion::find($quotation->quotation_template_version_id);
            if ($version) {
                $html = $version->html_content;
                $css = $version->css_content;
            }
        }

        if (empty($html) && $quotation->quotation_template_id) {
            $template = $quotation->quotationTemplate
                ?: QuotationTemplate::find($quotation->quotation_template_id);
            if ($template) {
                $html = $template->html_content;
                $css = $template->css_content;
            }
        }

        // Fallback to built-in default layout
        if (empty($html)) {
            $html = self::defaultHtml();
            $css = self::defaultCss();
        }

        return $this->composeDocument($html, (string) $css, $data, true);
    }

    /**
     * Generate PDF binary from HTML+CSS using mPDF.
     *
     * @return array{binary: string, filename: string}
     */
    public function generatePdfFromHtml(string $fullHtml, string $filename): array
    {
        try {
            return [
                'binary' => MpdfDocument::binary($fullHtml),
                'filename' => $filename,
            ];
        } catch (\Throwable $e) {
            Log::error('mPDF quotation PDF failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            throw new \RuntimeException('Unable to generate quotation PDF: ' . $e->getMessage());
        }
    }

    /**
     * Seed a default Standard Quotation template if none exists.
     * Never overwrites an existing template's HTML/CSS.
     * Soft-deleted templates are not recreated.
     */
    public function ensureDefaultTemplateExists(?int $userId = null): QuotationTemplate
    {
        $existing = QuotationTemplate::withTrashed()
            ->where('name', 'Standard Quotation')
            ->first();

        if ($existing) {
            $this->upgradeQtTopHeaderTemplate($existing);

            return $existing;
        }

        $existingDefault = QuotationTemplate::query()->where('is_default', true)->first();
        if ($existingDefault) {
            return $existingDefault;
        }

        $html = $this->sanitizeHtml(self::defaultHtml());
        $css = $this->sanitizeCss(self::defaultCss());
        $description = 'Default professional quotation layout with company, customer, items and totals.';

        $template = QuotationTemplate::create([
            'name' => 'Standard Quotation',
            'description' => $description,
            'html_content' => $html,
            'css_content' => $css,
            'status' => 'active',
            'is_default' => true,
            'version' => 1,
            'created_by' => $userId,
            'updated_by' => $userId,
        ]);

        $this->createVersion($template, $userId);

        return $template;
    }

    /**
     * Seed Executive Proposal if missing. Never overwrites custom edits.
     * Soft-deleted templates are not recreated.
     */
    public function ensureProfessionalTemplateExists(?int $userId = null): ?QuotationTemplate
    {
        $existing = QuotationTemplate::withTrashed()
            ->where('name', 'Executive Proposal')
            ->first();

        if ($existing) {
            return $existing;
        }

        $html = $this->sanitizeHtml(self::professionalHtml());
        $css = $this->sanitizeCss(self::professionalCss());
        $description = 'Professional navy layout with repeating page header/footer, meta strip, and slate totals.';

        $template = QuotationTemplate::create([
            'name' => 'Executive Proposal',
            'description' => $description,
            'html_content' => $html,
            'css_content' => $css,
            'status' => 'active',
            'is_default' => false,
            'version' => 1,
            'created_by' => $userId,
            'updated_by' => $userId,
        ]);

        $this->createVersion($template, $userId);

        return $template;
    }

    /**
     * Seed Modern Slate if missing. Never overwrites custom edits.
     * Soft-deleted templates are not recreated.
     */
    public function ensureModernSlateTemplateExists(?int $userId = null): ?QuotationTemplate
    {
        $existing = QuotationTemplate::withTrashed()
            ->where('name', 'Modern Slate')
            ->first();

        if ($existing) {
            return $existing;
        }

        $html = $this->sanitizeHtml(self::modernSlateHtml());
        $css = $this->sanitizeCss(self::modernSlateCss());
        $description = 'Clean professional invoice-style layout with teal accents, slate item headers, and repeating page header/footer.';

        $template = QuotationTemplate::create([
            'name' => 'Modern Slate',
            'description' => $description,
            'html_content' => $html,
            'css_content' => $css,
            'status' => 'active',
            'is_default' => false,
            'version' => 1,
            'created_by' => $userId,
            'updated_by' => $userId,
        ]);

        $this->createVersion($template, $userId);

        return $template;
    }

    /**
     * Seed Template 3 if missing. Never overwrites custom edits.
     * Soft-deleted templates are not recreated.
     */
    public function ensureTemplateThreeExists(?int $userId = null): ?QuotationTemplate
    {
        $existing = QuotationTemplate::withTrashed()
            ->where('name', 'template 3')
            ->first();

        if ($existing) {
            return $existing;
        }

        $html = $this->sanitizeHtml(self::templateThreeHtml());
        $css = $this->sanitizeCss(self::templateThreeCss());
        $description = 'Professional charcoal & emerald layout with aligned parties, items, totals, and proposal version.';

        $template = QuotationTemplate::create([
            'name' => 'template 3',
            'description' => $description,
            'html_content' => $html,
            'css_content' => $css,
            'status' => 'active',
            'is_default' => false,
            'version' => 1,
            'created_by' => $userId,
            'updated_by' => $userId,
        ]);

        $this->createVersion($template, $userId);

        return $template;
    }

    /**
     * Seed Studio Quote if missing. Never overwrites custom edits.
     * Soft-deleted templates are not recreated.
     */
    public function ensureStudioQuoteTemplateExists(?int $userId = null): ?QuotationTemplate
    {
        $existing = QuotationTemplate::withTrashed()
            ->where('name', 'Studio Quote')
            ->first();

        if ($existing) {
            return $existing;
        }

        $html = $this->sanitizeHtml(self::studioQuoteHtml());
        $css = $this->sanitizeCss(self::studioQuoteCss());
        $description = 'Clean professional sapphire layout with simple parties, clear item tables, and an attractive totals panel.';

        $template = QuotationTemplate::create([
            'name' => 'Studio Quote',
            'description' => $description,
            'html_content' => $html,
            'css_content' => $css,
            'status' => 'active',
            'is_default' => false,
            'version' => 1,
            'created_by' => $userId,
            'updated_by' => $userId,
        ]);

        $this->createVersion($template, $userId);

        return $template;
    }

    /**
     * Seed Standard Quotation - 2 if missing. Never overwrites custom edits.
     * Soft-deleted templates are not recreated.
     */
    public function ensureStandardQuotationTwoExists(?int $userId = null): ?QuotationTemplate
    {
        $existing = QuotationTemplate::withTrashed()
            ->whereIn('name', ['Standard Quotation - 2', 'Standard Quotation -2'])
            ->first();

        if ($existing) {
            return $existing;
        }

        $html = $this->sanitizeHtml(self::standardQuotationTwoHtml());
        $css = $this->sanitizeCss(self::standardQuotationTwoCss());
        $description = 'Modern geometric header with white logo panel, orange sash, charcoal QUOTATION block, and clean standard body.';

        $template = QuotationTemplate::create([
            'name' => 'Standard Quotation - 2',
            'description' => $description,
            'html_content' => $html,
            'css_content' => $css,
            'status' => 'active',
            'is_default' => false,
            'version' => 1,
            'created_by' => $userId,
            'updated_by' => $userId,
        ]);

        $this->createVersion($template, $userId);

        return $template;
    }

    /**
     * Ensure built-in templates exist (create only — never overwrite saved designs).
     * Does not recreate soft-deleted templates.
     */
    public function ensureAllBuiltInTemplatesExist(?int $userId = null): void
    {
        $this->ensureDefaultTemplateExists($userId);
        $this->ensureProfessionalTemplateExists($userId);
        $this->ensureModernSlateTemplateExists($userId);
        $this->ensureTemplateThreeExists($userId);
        $this->ensureStudioQuoteTemplateExists($userId);
        $this->ensureStandardQuotationTwoExists($userId);

        $newDesign = QuotationTemplate::query()->where('name', 'New Quotation Design')->first();
        if ($newDesign) {
            $this->upgradeQtTopHeaderTemplate($newDesign);
        }
    }
}

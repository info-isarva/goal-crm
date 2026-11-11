<?php

if (! function_exists('format_amount')) {
    function format_amount($amount, $decimals = 2)
    {
        return \App\Helpers\MoneyFormatter::format($amount, $decimals);
    }
}

if (! function_exists('format_smart_number')) {
    /**
     * Format a number without trailing .00; keep 2 decimals when a fraction exists.
     * Examples: 40000 => "40,000", 40000.5 => "40,000.50"
     */
    function format_smart_number($amount, int $decimals = 2, bool $thousandsSeparator = true): string
    {
        if ($amount === null || $amount === '') {
            return '0';
        }

        $n = round((float) $amount, $decimals);
        $sep = $thousandsSeparator ? ',' : '';

        if (abs($n - round($n)) < 0.0000001) {
            return number_format(round($n), 0, '.', $sep);
        }

        return number_format($n, $decimals, '.', $sep);
    }
}

if (! function_exists('format_smart_money')) {
    /**
     * Currency display without trailing .00 (default ₹).
     */
    function format_smart_money($amount, string $symbol = '₹'): string
    {
        $symbol = trim($symbol);
        if ($symbol === '') {
            $symbol = '₹';
        }

        return $symbol . format_smart_number($amount);
    }
}

if (! function_exists('format_email_money')) {
    /**
     * Email-safe money formatting.
     * Arabic / RTL currency glyphs (e.g. د.إ) often corrupt in Gmail/Outlook,
     * so prefer an ASCII currency code when the symbol is not email-safe.
     */
    function format_email_money($amount, ?string $symbol = null, ?string $code = null): string
    {
        $symbol = trim((string) $symbol);
        $code = strtoupper(trim((string) $code));
        $number = format_smart_number($amount);

        $symbolIsUnsafe = $symbol === ''
            || preg_match('/[\x{0600}-\x{06FF}\x{0750}-\x{077F}\x{08A0}-\x{08FF}]/u', $symbol);

        if ($symbolIsUnsafe) {
            $prefix = $code !== '' ? $code : 'INR';

            return $prefix . ' ' . $number;
        }

        return $symbol . $number;
    }
}

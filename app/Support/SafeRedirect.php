<?php

namespace App\Support;

class SafeRedirect
{
    /**
     * Return $url only when it points to this application (relative path or same host);
     * otherwise return $fallback.
     */
    public static function internal(?string $url, string $fallback): string
    {
        $url = trim((string) $url);
        if ($url === '' || preg_match('/[\x00-\x1F\x7F\\\\]/', $url)) {
            return $fallback;
        }

        if (str_starts_with($url, '/') && !str_starts_with($url, '//')) {
            return url($url);
        }

        $parts = parse_url($url);
        if ($parts === false || empty($parts['host'])) {
            return $fallback;
        }

        $scheme = strtolower($parts['scheme'] ?? '');
        if (!in_array($scheme, ['http', 'https'], true)) {
            return $fallback;
        }

        $allowedHosts = array_filter([
            parse_url((string) config('app.url'), PHP_URL_HOST),
            request()->getHost(),
        ]);

        return in_array(strtolower($parts['host']), array_map('strtolower', $allowedHosts), true)
            ? $url
            : $fallback;
    }
}

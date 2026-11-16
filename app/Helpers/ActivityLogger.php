<?php

namespace App\Helpers;

use App\Models\ActivityLog;
use Throwable;

class ActivityLogger
{
    /** @var list<string> */
    public const SENSITIVE_KEYS = [
        'password',
        'password_confirmation',
        'current_password',
        'token',
        'remember_token',
        'api_token',
        'secret',
        'otp',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'authorization',
        'cookie',
    ];

    public static function log(array $params = []): void
    {
        try {
            // Never recurse into logging the log itself.
            if (($params['subject_type'] ?? null) === ActivityLog::class) {
                return;
            }

            ActivityLog::create([
                'user_id' => $params['user_id'] ?? (auth()->check() ? auth()->id() : null),
                'type' => $params['type'] ?? null,
                'module' => $params['module'] ?? null,
                'action' => $params['action'] ?? null,
                'ip_address' => $params['ip_address'] ?? request()->ip(),
                'device' => $params['device'] ?? request()->header('User-Agent'),
                'details' => self::normalizeDetails($params['details'] ?? null),
                'subject_type' => $params['subject_type'] ?? null,
                'subject_id' => $params['subject_id'] ?? null,
            ]);
        } catch (Throwable $e) {
            report($e);
        }
    }

    public static function redact(mixed $payload): mixed
    {
        if (!is_array($payload)) {
            return $payload;
        }

        $clean = [];
        foreach ($payload as $key => $value) {
            $keyStr = is_string($key) ? strtolower($key) : (string) $key;
            if (in_array($keyStr, self::SENSITIVE_KEYS, true) || str_contains($keyStr, 'password')) {
                $clean[$key] = '[redacted]';
                continue;
            }
            $clean[$key] = is_array($value) ? self::redact($value) : $value;
        }

        return $clean;
    }

    protected static function normalizeDetails(mixed $details): ?string
    {
        if ($details === null || $details === '') {
            return null;
        }

        if (is_string($details)) {
            $decoded = json_decode($details, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return json_encode(self::redact($decoded), JSON_UNESCAPED_UNICODE);
            }

            return $details;
        }

        if (is_array($details)) {
            return json_encode(self::redact($details), JSON_UNESCAPED_UNICODE);
        }

        return (string) $details;
    }
}

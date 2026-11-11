<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

/**
 * Helpers for storing user uploads without trusting the client-supplied
 * file name or extension.
 */
class SafeUpload
{
    private const IMAGE_MIMES = [
        'image/jpeg' => 'jpg',
        'image/pjpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'image/webp' => 'webp',
    ];

    private const ICON_MIMES = [
        'image/x-icon' => 'ico',
        'image/vnd.microsoft.icon' => 'ico',
        'image/ico' => 'ico',
    ];

    /** Extensions accepted for CRM attachments (lead/deal files, email attachments). */
    public const DOCUMENT_EXTENSIONS = [
        'pdf', 'doc', 'docx', 'xls', 'xlsx', 'csv', 'txt', 'rtf', 'ppt', 'pptx',
        'odt', 'ods', 'odp', 'jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp',
        'zip', 'rar', '7z', 'msg', 'eml', 'mp3', 'wav', 'm4a', 'mp4',
    ];

    /**
     * Extension derived from the detected (server-side) MIME type, or null when the
     * file is not a raster image.
     */
    public static function imageExtension(UploadedFile $file, bool $allowIcon = false): ?string
    {
        $mime = strtolower((string) $file->getMimeType());
        $map = $allowIcon ? self::IMAGE_MIMES + self::ICON_MIMES : self::IMAGE_MIMES;

        if (! isset($map[$mime])) {
            return null;
        }

        if ($map[$mime] !== 'ico' && @getimagesize($file->getRealPath()) === false) {
            return null;
        }

        return $map[$mime];
    }

    /**
     * Lower-cased client extension when it is on the attachment allow-list, otherwise null.
     */
    public static function documentExtension(UploadedFile $file): ?string
    {
        $ext = strtolower((string) $file->getClientOriginalExtension());

        return in_array($ext, self::DOCUMENT_EXTENSIONS, true) ? $ext : null;
    }

    /** Value for Laravel's "mimes:" rule (checked against the detected content type). */
    public static function documentMimesRule(): string
    {
        return implode(',', self::DOCUMENT_EXTENSIONS);
    }

    public static function randomName(string $extension): string
    {
        return Str::random(40) . '.' . $extension;
    }

    public static function isHttpUrl(?string $url): bool
    {
        if (! is_string($url) || filter_var($url, FILTER_VALIDATE_URL) === false) {
            return false;
        }

        return in_array(strtolower((string) parse_url($url, PHP_URL_SCHEME)), ['http', 'https'], true);
    }

    /**
     * Download name built from the user-entered label plus the stored file's extension.
     */
    public static function downloadName(?string $label, string $storedPath): string
    {
        $base = pathinfo(basename(str_replace('\\', '/', (string) $label)), PATHINFO_FILENAME);
        $base = trim(preg_replace('/[\x00-\x1F\x7F<>:"\/\\\\|?*]+/u', '_', $base) ?? '');
        $extension = strtolower(pathinfo($storedPath, PATHINFO_EXTENSION));

        return ($base !== '' ? $base : 'file') . ($extension !== '' ? '.' . $extension : '');
    }

    /**
     * Display-safe version of the original file name (no path, control or HTML characters).
     */
    public static function displayName(UploadedFile $file, int $max = 200): string
    {
        $name = basename(str_replace('\\', '/', (string) $file->getClientOriginalName()));
        $name = preg_replace('/[\x00-\x1F\x7F<>:"\/\\\\|?*]+/u', '_', $name) ?? 'file';

        return Str::limit(trim($name) !== '' ? $name : 'file', $max, '');
    }

    /**
     * Value safe to write into a spreadsheet cell: strings that would be read as a
     * formula by Excel / LibreOffice are prefixed with an apostrophe.
     */
    public static function spreadsheetCell(mixed $value): mixed
    {
        if (! is_string($value) || $value === '') {
            return $value;
        }

        if (preg_match('/^[=+\-@\t\r]/', $value) && ! is_numeric($value)) {
            return "'" . $value;
        }

        return $value;
    }
}

<?php

namespace App\Library;

class TcpdfSafeImage
{
    /**
     * Return a local image path TCPDF can embed without Imagick/GD alpha errors.
     * PNG/WebP with transparency are flattened onto a white JPEG when GD is available.
     * Returns null if the image cannot be made TCPDF-safe.
     */
    public static function path(?string $sourcePath): ?string
    {
        if (empty($sourcePath) || !is_file($sourcePath)) {
            return null;
        }

        $ext = strtolower(pathinfo($sourcePath, PATHINFO_EXTENSION));

        if (in_array($ext, ['jpg', 'jpeg', 'gif'], true)) {
            return $sourcePath;
        }

        if (!in_array($ext, ['png', 'webp', 'bmp'], true)) {
            return null;
        }

        $flattened = self::flattenToJpeg($sourcePath);
        if ($flattened) {
            return $flattened;
        }

        // Without GD, skip alpha PNG/WebP so TCPDF does not fatal.
        return null;
    }

    public static function htmlImgTag(?string $sourcePath, int $height = 50, string $alt = 'Logo'): string
    {
        $jpeg = self::path($sourcePath);
        $dataUri = $jpeg ? self::dataUri($jpeg) : self::dataUri($sourcePath);
        if (!$dataUri) {
            return '';
        }

        $width = (int) round($height * 2.6);

        return '<img src="' . htmlspecialchars($dataUri, ENT_QUOTES, 'UTF-8')
            . '" width="' . $width . '" height="' . $height . '" alt="' . htmlspecialchars($alt, ENT_QUOTES, 'UTF-8')
            . '" style="width:' . $width . 'px;height:' . $height . 'px;max-width:180px;">';
    }

    /**
     * Embed a local image as a data URI (works in Chrome PDF and mPDF, including PNG alpha).
     */
    public static function dataUri(?string $sourcePath): ?string
    {
        if (empty($sourcePath) || !is_file($sourcePath)) {
            return null;
        }

        $binary = @file_get_contents($sourcePath);
        if ($binary === false || $binary === '') {
            return null;
        }

        $mime = 'image/png';
        if (function_exists('mime_content_type')) {
            $detected = @mime_content_type($sourcePath);
            if (is_string($detected) && str_starts_with($detected, 'image/')) {
                $mime = $detected;
            }
        } else {
            $ext = strtolower(pathinfo($sourcePath, PATHINFO_EXTENSION));
            $mime = match ($ext) {
                'jpg', 'jpeg' => 'image/jpeg',
                'gif' => 'image/gif',
                'webp' => 'image/webp',
                'svg' => 'image/svg+xml',
                default => 'image/png',
            };
        }

        return 'data:' . $mime . ';base64,' . base64_encode($binary);
    }

    /**
     * Rewrite <img src> to JPEG files under storage so DomPDF can embed them.
     */
    public static function prepareHtml(string $html): string
    {
        if (!preg_match_all('/<img\b[^>]*>/i', $html, $tags)) {
            return $html;
        }

        foreach ($tags[0] as $tag) {
            if (!preg_match('/\ssrc\s*=\s*(["\'])(.*?)\1/is', $tag, $sm)) {
                continue;
            }
            $src = html_entity_decode($sm[2], ENT_QUOTES, 'UTF-8');
            $local = self::resolveToLocalFile($src);
            $safe = $local ? self::path($local) : $local;
            if (!$safe && $local && is_file($local)) {
                $safe = $local;
            }
            if (!$safe) {
                $html = str_replace($tag, '', $html);
                continue;
            }

            $safe = str_replace('\\', '/', realpath($safe) ?: $safe);
            $width = null;
            $height = null;
            if (preg_match('/\swidth\s*=\s*["\']?(\d+)/i', $tag, $wm)) {
                $width = (int) $wm[1];
            }
            if (preg_match('/\sheight\s*=\s*["\']?(\d+)/i', $tag, $hm)) {
                $height = (int) $hm[1];
            }
            if ($width === null && preg_match('/width\s*:\s*(\d+)px/i', $tag, $wm)) {
                $width = (int) $wm[1];
            }
            if ($height === null && preg_match('/height\s*:\s*(\d+)px/i', $tag, $hm)) {
                $height = (int) $hm[1];
            }
            $width = $width ?: 150;
            $height = $height ?: 52;

            $replacement = '<img src="' . htmlspecialchars($safe, ENT_QUOTES, 'UTF-8')
                . '" width="' . $width . '" height="' . $height . '"'
                . ' style="width:' . $width . 'px;height:' . $height . 'px;">';
            $html = str_replace($tag, $replacement, $html);
        }

        return $html;
    }

    protected static function resolveToLocalFile(string $src): ?string
    {
        $src = trim($src);
        if ($src === '') {
            return null;
        }

        if (str_starts_with($src, 'data:image/')) {
            return self::dataUriToTempFile($src);
        }

        return self::resolveLocalPath($src);
    }

    protected static function dataUriToTempFile(string $src): ?string
    {
        if (!preg_match('#^data:image/([a-zA-Z0-9+.-]+);base64,(.+)$#s', $src, $m)) {
            return null;
        }

        $binary = base64_decode(preg_replace('/\s+/', '', $m[2]), true);
        if ($binary === false || $binary === '') {
            return null;
        }

        $ext = strtolower($m[1]);
        $ext = match ($ext) {
            'jpeg', 'jpg' => 'jpg',
            'png' => 'png',
            'gif' => 'gif',
            'webp' => 'webp',
            default => 'png',
        };

        $dir = public_path('assets/tmp/qt-images');
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $out = $dir . DIRECTORY_SEPARATOR . md5(substr($src, 0, 200) . strlen($src)) . '.' . $ext;
        if (!is_file($out)) {
            if (@file_put_contents($out, $binary) === false) {
                return null;
            }
        }

        return $out;
    }

    protected static function resolveLocalPath(string $src): ?string
    {
        $src = trim($src);
        if ($src === '') {
            return null;
        }

        if (preg_match('#^https?://(localhost|127\.0\.0\.1)#i', $src)) {
            $path = parse_url($src, PHP_URL_PATH) ?: '';
            $file = public_path(ltrim(urldecode($path), '/'));
            return is_file($file) ? $file : null;
        }

        if (preg_match('#^https?://#i', $src)) {
            return null;
        }

        $src = str_replace('\\', '/', $src);

        if (preg_match('#^[a-zA-Z]:/#', $src) || str_starts_with($src, '/')) {
            return is_file($src) ? $src : null;
        }

        $candidates = [
            public_path(ltrim($src, '/')),
            public_path('storage/' . ltrim(preg_replace('#^/?storage/#', '', $src) ?? $src, '/')),
            storage_path('app/public/' . ltrim(preg_replace('#^/?storage/#', '', $src) ?? $src, '/')),
            public_path('assets/' . ltrim($src, '/')),
            public_path('assets/company_image/' . ltrim($src, '/')),
        ];
        foreach ($candidates as $file) {
            if (is_file($file)) {
                return $file;
            }
        }

        // Absolute app URL → local public path
        $appUrl = rtrim((string) config('app.url'), '/');
        if ($appUrl !== '' && str_starts_with($src, $appUrl . '/')) {
            $path = substr($src, strlen($appUrl));
            $file = public_path(ltrim(urldecode($path), '/'));
            if (is_file($file)) {
                return $file;
            }
            if (str_starts_with($path, '/storage/')) {
                $file = storage_path('app/public/' . ltrim(substr($path, strlen('/storage/')), '/'));
                if (is_file($file)) {
                    return $file;
                }
            }
        }

        return null;
    }

    protected static function flattenToJpeg(string $path): ?string
    {
        if (!function_exists('imagecreatefromstring') || !function_exists('imagejpeg')) {
            return null;
        }

        $binary = @file_get_contents($path);
        if ($binary === false || $binary === '') {
            return null;
        }

        $src = @imagecreatefromstring($binary);
        if (!$src) {
            return null;
        }

        $width = imagesx($src);
        $height = imagesy($src);
        if ($width < 1 || $height < 1) {
            imagedestroy($src);
            return null;
        }

        $dst = imagecreatetruecolor($width, $height);
        $white = imagecolorallocate($dst, 255, 255, 255);
        imagefilledrectangle($dst, 0, 0, $width, $height, $white);
        imagealphablending($dst, true);
        imagesavealpha($dst, false);
        imagecopy($dst, $src, 0, 0, 0, 0, $width, $height);

        $dir = public_path('assets/tmp/qt-images');
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $out = $dir . DIRECTORY_SEPARATOR . md5($path . '|' . filemtime($path) . '|' . filesize($path)) . '.jpg';
        $ok = imagejpeg($dst, $out, 90);

        imagedestroy($src);
        imagedestroy($dst);

        return $ok && is_file($out) ? $out : null;
    }
}

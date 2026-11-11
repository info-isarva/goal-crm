<?php

namespace App\Library;

use Mpdf\HTMLParserMode;
use Mpdf\Mpdf;
use Mpdf\Output\Destination;

class MpdfDocument
{
    /**
     * Render HTML to a PDF binary (A4, UTF-8, DejaVu for ₹).
     */
    public static function binary(string $html, string $format = 'A4'): string
    {
        $tempDir = storage_path('app/mpdf');
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        [$css, $body] = self::splitHtml($html);
        [$body, $imageVars] = self::prepareBodyHtml($body);

        $hasHeader = stripos($body, 'htmlpageheader') !== false;
        $hasFooter = stripos($body, 'htmlpagefooter') !== false;

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => $format,
            // Side margins stay tight; give more breathing room top/bottom.
            'margin_left' => 8,
            'margin_right' => 8,
            'margin_top' => $hasHeader ? 36 : 18,
            'margin_bottom' => $hasFooter ? 32 : 18,
            'margin_header' => $hasHeader ? 10 : 0,
            'margin_footer' => $hasFooter ? 8 : 0,
            'tempDir' => $tempDir,
            'default_font' => 'dejavusans',
            'default_font_size' => 11,
        ]);
        $mpdf->SetDisplayMode('fullpage');
        $mpdf->showImageErrors = false;
        $mpdf->packTableData = true;
        $mpdf->keep_block_together = 0;
        $mpdf->imageVars = $imageVars;
        // Prevent mPDF default link underlines from appearing on body copy.
        $mpdf->SetDefaultBodyCSS('text-decoration', 'none');
        $mpdf->SetDefaultBodyCSS('color', '#222222');
        $mpdf->SetDefaultBodyCSS('font-size', '11pt');

        // Stretch top margin for repeating headers. Do NOT stretch the bottom margin:
        // stretch + signature/end blocks causes overlapping text and large blank gaps.
        if ($hasHeader) {
            $mpdf->setAutoTopMargin = 'stretch';
        }

        $previous = set_error_handler(function (int $severity, string $message, string $file, int $line) use (&$previous) {
            if (str_contains($file, DIRECTORY_SEPARATOR . 'mpdf' . DIRECTORY_SEPARATOR)) {
                return true;
            }
            if (is_callable($previous)) {
                return $previous($severity, $message, $file, $line);
            }

            return false;
        });

        try {
            if ($css !== '') {
                $mpdf->WriteHTML($css, HTMLParserMode::HEADER_CSS);
            }
            $mpdf->WriteHTML($body, HTMLParserMode::HTML_BODY);
            $binary = $mpdf->Output('', Destination::STRING_RETURN);
            $pages = (int) $mpdf->page;
        } finally {
            if ($previous) {
                set_error_handler($previous);
            } else {
                restore_error_handler();
            }
        }

        if ($binary === '' || $binary === false) {
            throw new \RuntimeException('mPDF returned an empty document.');
        }

        if ($pages < 1) {
            throw new \RuntimeException('mPDF generated a PDF with no pages.');
        }

        return $binary;
    }

    /**
     * @return array{0: string, 1: string} [css, bodyHtml]
     */
    protected static function splitHtml(string $html): array
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
        $html = preg_replace('#</?(html|head|meta|title|link)\b[^>]*>#i', '', $html) ?? $html;

        return [trim($css), trim($html)];
    }

    /**
     * @return array{0: string, 1: array<string, string>} [html, imageVars]
     */
    protected static function prepareBodyHtml(string $html): array
    {
        $html = TcpdfSafeImage::prepareHtml($html);
        $html = preg_replace('/<(td|th)(\b[^>]*)>\s*<\/\1>/i', '<$1$2>&nbsp;</$1>', $html) ?? $html;

        $imageVars = [];
        $html = preg_replace_callback(
            '/(<img\b[^>]*\ssrc\s*=\s*)(["\'])(.*?)\2/is',
            function (array $m) use (&$imageVars) {
                $src = html_entity_decode($m[3], ENT_QUOTES, 'UTF-8');
                if ($src === '' || str_starts_with($src, 'var:') || !is_file($src)) {
                    return $m[0];
                }

                $binary = @file_get_contents($src);
                if ($binary === false || $binary === '') {
                    return $m[0];
                }

                $key = 'img' . md5($src);
                $imageVars[$key] = $binary;

                return $m[1] . $m[2] . 'var:' . $key . $m[2];
            },
            $html
        ) ?? $html;

        return [$html, $imageVars];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromView(string $view, array $data, string $filename, bool $download = true, string $format = 'A4')
    {
        $html = view($view, $data)->render();
        $binary = self::binary($html, $format);
        $disposition = $download ? 'attachment' : 'inline';

        return response($binary, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => $disposition . '; filename="' . $filename . '"',
        ]);
    }
}

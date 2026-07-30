<?php

namespace App\Support;

class HtmlContent
{
    private const ALLOWED_TAGS = '<p><br><strong><b><em><i><u><a><ul><ol><li>';

    public static function sanitize(?string $html): string
    {
        $html = html_entity_decode((string) $html, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $html = strip_tags($html, self::ALLOWED_TAGS);

        // Keep only safe hrefs on anchors.
        $html = preg_replace_callback(
            '/<a\s+([^>]*?)>/i',
            function (array $matches): string {
                $attrs = $matches[1];
                $href = null;

                if (preg_match('/href\s*=\s*([\'"])(.*?)\1/i', $attrs, $hrefMatch)) {
                    $candidate = trim($hrefMatch[2]);
                    if (preg_match('/^(https?:\/\/|mailto:)/i', $candidate)) {
                        $href = $candidate;
                    }
                }

                return $href
                    ? '<a href="'.e($href).'" target="_blank" rel="noopener noreferrer">'
                    : '<a>';
            },
            $html
        ) ?? $html;

        return trim($html);
    }

    public static function toPlainText(?string $html): string
    {
        $text = (string) $html;
        $text = preg_replace('/<\s*br\s*\/?>/i', "\n", $text) ?? $text;
        $text = preg_replace('/<\/\s*p\s*>/i', "\n\n", $text) ?? $text;
        $text = preg_replace('/<\s*p[^>]*>/i', '', $text) ?? $text;
        $text = preg_replace('/<\s*li[^>]*>/i', '• ', $text) ?? $text;
        $text = preg_replace('/<\/\s*li\s*>/i', "\n", $text) ?? $text;
        $text = preg_replace('/<\/?\s*(ul|ol)[^>]*>/i', "\n", $text) ?? $text;
        $text = strip_tags($text);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace("/[ \t]+\n/", "\n", $text) ?? $text;
        $text = preg_replace("/\n{3,}/", "\n\n", $text) ?? $text;

        return trim($text);
    }

    public static function plainToHtml(string $plain): string
    {
        $lines = preg_split("/\r\n|\n|\r/", $plain) ?: [];
        $parts = [];
        $inList = false;

        foreach ($lines as $line) {
            $trimmed = trim($line);

            if ($trimmed === '') {
                if ($inList) {
                    $parts[] = '</ul>';
                    $inList = false;
                }

                continue;
            }

            if (preg_match('/^[•\-\*]\s+(.+)$/u', $trimmed, $match)) {
                if (! $inList) {
                    $parts[] = '<ul>';
                    $inList = true;
                }

                $parts[] = '<li>'.e($match[1]).'</li>';

                continue;
            }

            if ($inList) {
                $parts[] = '</ul>';
                $inList = false;
            }

            $parts[] = '<p>'.e($trimmed).'</p>';
        }

        if ($inList) {
            $parts[] = '</ul>';
        }

        return implode("\n", $parts);
    }
}

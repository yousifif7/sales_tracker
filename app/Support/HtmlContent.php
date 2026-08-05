<?php

namespace App\Support;

class HtmlContent
{
    private const ALLOWED_TAGS = '<p><br><strong><b><em><i><u><a><ul><ol><li>';

    /** Extra tags kept for inbound mail so client HTML still renders. */
    private const INBOUND_ALLOWED_TAGS = '<p><br><strong><b><em><i><u><a><ul><ol><li><div><span><blockquote><h1><h2><h3><h4><h5><h6><table><thead><tbody><tr><th><td><hr><pre><img>';

    public static function sanitize(?string $html): string
    {
        return self::sanitizeWithTags($html, self::ALLOWED_TAGS);
    }

    public static function sanitizeInbound(?string $html): string
    {
        return self::sanitizeWithTags($html, self::INBOUND_ALLOWED_TAGS, true);
    }

    private static function sanitizeWithTags(?string $html, string $allowedTags, bool $preserveInboundLayout = false): string
    {
        $html = html_entity_decode((string) $html, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $html = preg_replace('/<script\b[^>]*>.*?<\/script>/is', '', $html) ?? $html;
        $html = preg_replace('/<style\b[^>]*>.*?<\/style>/is', '', $html) ?? $html;

        // Contenteditable paste/Enter often uses <div> for line breaks. Those are not in
        // ALLOWED_TAGS, so strip_tags would glue paragraphs into one blob.
        if (! $preserveInboundLayout) {
            $html = preg_replace('/<\s*div[^>]*>/i', '<p>', $html) ?? $html;
            $html = preg_replace('/<\/\s*div\s*>/i', '</p>', $html) ?? $html;
        }

        $html = strip_tags($html, $allowedTags);

        // Drop Gmail/client inline styles (white backgrounds, light text, etc.)
        // and keep only safe hrefs on anchors.
        $html = preg_replace_callback(
            '/<([a-z0-9]+)(\s[^>]*)?>/i',
            function (array $matches) use ($preserveInboundLayout): string {
                $tag = strtolower($matches[1]);
                $attrs = $matches[2] ?? '';

                if ($tag === 'a') {
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
                }

                if ($preserveInboundLayout && $tag === 'img') {
                    $src = self::extractAttribute($attrs, 'src');

                    if (! is_string($src) || ! preg_match('/^https?:\/\//i', $src)) {
                        return '';
                    }

                    $imgAttrs = ['src="'.e($src).'"'];

                    foreach (['alt', 'width', 'height'] as $attribute) {
                        $value = self::extractAttribute($attrs, $attribute);
                        if (! is_string($value) || $value === '') {
                            continue;
                        }

                        if (in_array($attribute, ['width', 'height'], true) && ! preg_match('/^\d+$/', $value)) {
                            continue;
                        }

                        $imgAttrs[] = $attribute.'="'.e($value).'"';
                    }

                    return '<img '.implode(' ', $imgAttrs).'>';
                }

                if ($preserveInboundLayout) {
                    $layoutAttributes = self::layoutAttributesForTag($tag, $attrs);

                    return '<'.$tag.($layoutAttributes !== '' ? ' '.$layoutAttributes : '').'>';
                }

                return '<'.$tag.'>';
            },
            $html
        ) ?? $html;

        $html = trim($html);

        // Contenteditable often leaves one block with literal newlines / <br> runs.
        // Rebuild into real paragraphs + lists so Gmail and the inbox don't collapse it.
        if (! $preserveInboundLayout) {
            $html = self::normalizeOutboundStructure($html);
        }

        return $html;
    }

    /**
     * Ensure outbound HTML has real paragraph/list structure instead of collapsed text.
     */
    public static function normalizeOutboundStructure(string $html): string
    {
        if (trim(strip_tags($html)) === '') {
            return '';
        }

        // Newlines inside a single <p> are invisible in HTML email.
        $html = preg_replace("/\r\n|\r|\n/u", '<br>', $html) ?? $html;

        $plain = self::toPlainText($html);
        $blockCount = preg_match_all('/<(?:p|ul|ol)\b/i', $html);
        $plainLines = preg_split("/\n+/u", $plain) ?: [];
        $plainLines = array_values(array_filter(array_map('trim', $plainLines), fn (string $line) => $line !== ''));

        // One (or zero) block with multiple visual lines → rebuild from plain text.
        if (count($plainLines) > 1 && $blockCount <= 1) {
            return self::plainToHtml($plain);
        }

        // Multiple <br> only (no paragraphs) → rebuild.
        if ($blockCount === 0 && preg_match('/<br\s*\/?>/i', $html)) {
            return self::plainToHtml($plain);
        }

        return $html;
    }

    /**
     * Add safe inline styles so outbound body content looks consistent in Gmail/Outlook.
     */
    public static function styleOutbound(string $html): string
    {
        $html = preg_replace(
            '/<\s*p(\s[^>]*)?>/i',
            '<p style="margin:0 0 14px 0;font-family:Arial,Helvetica,sans-serif;font-size:15px;line-height:1.65;color:#1f2937;">',
            $html
        ) ?? $html;

        $html = preg_replace(
            '/<\s*ul(\s[^>]*)?>/i',
            '<ul style="margin:0 0 16px 0;padding:0 0 0 22px;font-family:Arial,Helvetica,sans-serif;color:#1f2937;">',
            $html
        ) ?? $html;

        $html = preg_replace(
            '/<\s*ol(\s[^>]*)?>/i',
            '<ol style="margin:0 0 16px 0;padding:0 0 0 22px;font-family:Arial,Helvetica,sans-serif;color:#1f2937;">',
            $html
        ) ?? $html;

        $html = preg_replace(
            '/<\s*li(\s[^>]*)?>/i',
            '<li style="margin:0 0 8px 0;font-family:Arial,Helvetica,sans-serif;font-size:15px;line-height:1.55;color:#1f2937;">',
            $html
        ) ?? $html;

        $html = preg_replace_callback(
            '/<\s*a(\s[^>]*)?>/i',
            function (array $matches): string {
                $attrs = $matches[1] ?? '';
                $href = null;

                if (preg_match('/href\s*=\s*([\'"])(.*?)\1/i', $attrs, $hrefMatch)) {
                    $candidate = trim($hrefMatch[2]);
                    if (preg_match('/^(https?:\/\/|mailto:)/i', $candidate)) {
                        $href = $candidate;
                    }
                }

                return $href
                    ? '<a href="'.e($href).'" style="color:#0284c7;text-decoration:underline;" target="_blank" rel="noopener noreferrer">'
                    : '<a style="color:#0284c7;text-decoration:underline;">';
            },
            $html
        ) ?? $html;

        $html = preg_replace(
            '/<\s*strong(\s[^>]*)?>/i',
            '<strong style="font-weight:700;color:#0f172a;">',
            $html
        ) ?? $html;

        $html = preg_replace(
            '/<\s*b(\s[^>]*)?>/i',
            '<b style="font-weight:700;color:#0f172a;">',
            $html
        ) ?? $html;

        // If the body is still a single text blob, wrap it so spacing still looks intentional.
        if (! preg_match('/<(?:p|ul|ol|li|br)\b/i', $html) && trim(strip_tags($html)) !== '') {
            $html = '<p style="margin:0 0 14px 0;font-family:Arial,Helvetica,sans-serif;font-size:15px;line-height:1.65;color:#1f2937;">'
                .$html
                .'</p>';
        }

        return $html;
    }

    private static function extractAttribute(string $attrs, string $name): ?string
    {
        if (! preg_match('/\b'.preg_quote($name, '/').'\s*=\s*([\'"])(.*?)\1/i', $attrs, $match)) {
            return null;
        }

        return trim($match[2]);
    }

    private static function layoutAttributesForTag(string $tag, string $attrs): string
    {
        $allowed = match ($tag) {
            'table' => ['width', 'align', 'cellpadding', 'cellspacing', 'border', 'role'],
            'thead', 'tbody', 'tr' => ['align', 'valign'],
            'td', 'th' => ['width', 'height', 'align', 'valign', 'colspan', 'rowspan'],
            'div', 'span', 'p', 'blockquote', 'pre' => ['align'],
            default => [],
        };

        $pairs = [];

        foreach ($allowed as $attribute) {
            $value = self::extractAttribute($attrs, $attribute);

            if (! is_string($value) || $value === '') {
                continue;
            }

            if (in_array($attribute, ['width', 'height', 'cellpadding', 'cellspacing', 'border', 'colspan', 'rowspan'], true)
                && ! preg_match('/^\d+%?$/', $value)) {
                continue;
            }

            if (in_array($attribute, ['align', 'valign'], true)
                && ! preg_match('/^(left|right|center|justify|top|middle|bottom)$/i', $value)) {
                continue;
            }

            if ($attribute === 'role' && ! preg_match('/^[a-z0-9_\- ]+$/i', $value)) {
                continue;
            }

            $pairs[] = $attribute.'="'.e($value).'"';
        }

        return implode(' ', $pairs);
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

<?php

namespace Tests\Unit;

use App\Support\HtmlContent;
use PHPUnit\Framework\TestCase;

class HtmlContentTest extends TestCase
{
    public function test_sanitize_inbound_keeps_div_and_blockquote_text(): void
    {
        $html = '<div>Hello <strong>there</strong></div><blockquote>Quoted bit</blockquote><script>alert(1)</script>';

        $sanitized = HtmlContent::sanitizeInbound($html);

        $this->assertStringContainsString('<div>', $sanitized);
        $this->assertStringContainsString('Hello', $sanitized);
        $this->assertStringContainsString('<blockquote>', $sanitized);
        $this->assertStringContainsString('Quoted bit', $sanitized);
        $this->assertStringNotContainsString('<script>', $sanitized);
        $this->assertStringNotContainsString('alert(1)', $sanitized);
    }

    public function test_sanitize_strips_disallowed_tags_but_keeps_text(): void
    {
        $html = '<div class="x">Safe text</div>';

        $sanitized = HtmlContent::sanitize($html);

        $this->assertStringNotContainsString('<div', $sanitized);
        $this->assertStringContainsString('Safe text', $sanitized);
    }

    public function test_sanitize_inbound_strips_inline_styles(): void
    {
        $html = '<div style="background:#fff;color:#000"><p style="color:blue">Hello</p><a style="color:red" href="https://example.com">Link</a></div>';

        $sanitized = HtmlContent::sanitizeInbound($html);

        $this->assertStringNotContainsString('style=', $sanitized);
        $this->assertStringNotContainsString('background', $sanitized);
        $this->assertStringContainsString('<div>', $sanitized);
        $this->assertStringContainsString('Hello', $sanitized);
        $this->assertStringContainsString('href="https://example.com"', $sanitized);
    }
}

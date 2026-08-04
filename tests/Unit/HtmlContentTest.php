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

    public function test_sanitize_inbound_keeps_safe_layout_attributes_and_images(): void
    {
        $html = '<table width="600" cellpadding="0"><tr><td colspan="2" align="left"><img src="https://example.com/logo.png" width="120" alt="Logo"></td></tr></table>';

        $sanitized = HtmlContent::sanitizeInbound($html);

        $this->assertStringContainsString('<table width="600" cellpadding="0">', $sanitized);
        $this->assertMatchesRegularExpression('/<td[^>]*colspan="2"[^>]*align="left"|<td[^>]*align="left"[^>]*colspan="2"/', $sanitized);
        $this->assertStringContainsString('<img src="https://example.com/logo.png" alt="Logo" width="120">', $sanitized);
    }

    public function test_sanitize_inbound_drops_unsafe_image_sources(): void
    {
        $html = '<img src="cid:logo"><img src="javascript:alert(1)"><img src="https://example.com/logo.png">';

        $sanitized = HtmlContent::sanitizeInbound($html);

        $this->assertStringNotContainsString('cid:logo', $sanitized);
        $this->assertStringNotContainsString('javascript:alert(1)', $sanitized);
        $this->assertStringContainsString('https://example.com/logo.png', $sanitized);
    }
}

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

    public function test_sanitize_converts_contenteditable_divs_to_paragraphs(): void
    {
        $html = '<div>Hi Paul,</div><div><br></div><div>I built FieldLine.</div>';

        $sanitized = HtmlContent::sanitize($html);

        $this->assertStringNotContainsString('<div', $sanitized);
        $this->assertStringContainsString('<p>Hi Paul,</p>', $sanitized);
        $this->assertStringContainsString('<p>I built FieldLine.</p>', $sanitized);
    }

    public function test_sanitize_converts_plain_text_newlines_to_paragraphs(): void
    {
        $html = "Hi Paul,\n\nI built FieldLine.\n\nBest,\nYousif";

        $sanitized = HtmlContent::sanitize($html);

        $this->assertStringContainsString('<p>Hi Paul,</p>', $sanitized);
        $this->assertStringContainsString('<p>I built FieldLine.</p>', $sanitized);
        $this->assertStringContainsString('<p>Best,</p>', $sanitized);
        $this->assertStringContainsString('<p>Yousif</p>', $sanitized);
    }

    public function test_sanitize_rebuilds_single_paragraph_with_internal_breaks(): void
    {
        $html = "<p>Hi Yousif,\n\nA lot of security firms...\n\n• live GPS\n• shifts / rota\n\nBest,\nYousif</p>";

        $sanitized = HtmlContent::sanitize($html);

        $this->assertStringContainsString('<p>Hi Yousif,</p>', $sanitized);
        $this->assertStringContainsString('<ul>', $sanitized);
        $this->assertStringContainsString('<li>live GPS</li>', $sanitized);
        $this->assertStringContainsString('<li>shifts / rota</li>', $sanitized);
        $this->assertStringContainsString('<p>Best,</p>', $sanitized);
    }

    public function test_sanitize_strips_disallowed_tags_but_keeps_text(): void
    {
        $html = '<div class="x">Safe text</div>';

        $sanitized = HtmlContent::sanitize($html);

        $this->assertStringNotContainsString('<div', $sanitized);
        $this->assertStringContainsString('<p>Safe text</p>', $sanitized);
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

    public function test_style_outbound_adds_inline_styles_to_paragraphs_and_lists(): void
    {
        $html = '<p>Hello</p><ul><li>One</li></ul><a href="https://fieldline-wf.com">Demo</a>';

        $styled = HtmlContent::styleOutbound($html);

        $this->assertStringContainsString('margin:0 0 14px 0', $styled);
        $this->assertStringContainsString('<li style="margin:0 0 8px 0', $styled);
        $this->assertStringContainsString('href="https://fieldline-wf.com"', $styled);
        $this->assertStringContainsString('color:#0284c7', $styled);
    }
}

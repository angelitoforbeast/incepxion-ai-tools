<?php

namespace Tests\Feature;

use App\Support\RichText;
use Tests\TestCase;

class RichTextTest extends TestCase
{
    public function test_it_keeps_basic_formatting(): void
    {
        $html = '<p>Hello <strong>bold</strong> and <em>italic</em></p><ul><li>one</li><li>two</li></ul>';
        $clean = RichText::clean($html);

        $this->assertStringContainsString('<strong>bold</strong>', $clean);
        $this->assertStringContainsString('<em>italic</em>', $clean);
        $this->assertStringContainsString('<li>one</li>', $clean);
    }

    public function test_it_removes_script_tags(): void
    {
        $clean = RichText::clean('<p>ok</p><script>alert(1)</script>');

        $this->assertStringNotContainsString('<script', $clean);
        $this->assertStringNotContainsString('alert(1)', $clean);
        $this->assertStringContainsString('<p>ok</p>', $clean);
    }

    public function test_it_strips_event_handler_and_style_attributes(): void
    {
        $clean = RichText::clean('<p onclick="steal()" style="color:red">hi</p>');

        $this->assertStringNotContainsString('onclick', $clean);
        $this->assertStringNotContainsString('style', $clean);
        $this->assertStringContainsString('hi', $clean);
    }

    public function test_it_drops_javascript_links_but_keeps_https(): void
    {
        $bad = RichText::clean('<a href="javascript:alert(1)">x</a>');
        $this->assertStringNotContainsString('javascript:', $bad);

        $good = RichText::clean('<a href="https://example.com">x</a>');
        $this->assertStringContainsString('href="https://example.com"', $good);
        $this->assertStringContainsString('rel="noopener noreferrer"', $good);
    }

    public function test_it_unwraps_disallowed_tags_but_keeps_inner_text(): void
    {
        $clean = RichText::clean('<div><p>keep <span>me</span></p></div>');

        $this->assertStringContainsString('keep', $clean);
        $this->assertStringContainsString('me', $clean);
    }

    public function test_render_treats_plain_text_with_line_breaks(): void
    {
        $rendered = RichText::render("line one\nline two");

        $this->assertStringContainsString('<br', $rendered);
        $this->assertStringContainsString('line one', $rendered);
    }

    public function test_excerpt_strips_tags_and_truncates(): void
    {
        $excerpt = RichText::excerpt('<p><strong>Bold</strong> intro text here</p>', 10);

        $this->assertStringNotContainsString('<', $excerpt);
        $this->assertStringContainsString('Bold', $excerpt);
    }

    public function test_isHtml_detection(): void
    {
        $this->assertTrue(RichText::isHtml('<p>hi</p>'));
        $this->assertFalse(RichText::isHtml('just text'));
    }
}

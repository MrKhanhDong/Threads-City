<?php

namespace Tests\Unit;

use App\Support\ArticleText;
use PHPUnit\Framework\TestCase;

class ArticleTextTest extends TestCase
{
    public function test_plain_text_and_headings_become_sections(): void
    {
        $sections=ArticleText::sections("Mở đầu.\n\n## Một tiêu đề\nĐoạn dưới tiêu đề.\n\nĐoạn tiếp theo.");
        $this->assertSame('', $sections[0]['heading']);
        $this->assertSame(['Mở đầu.'], $sections[0]['paragraphs']);
        $this->assertSame('Một tiêu đề', $sections[1]['heading']);
        $this->assertSame(['Đoạn dưới tiêu đề.', 'Đoạn tiếp theo.'], $sections[1]['paragraphs']);
    }
    public function test_html_is_kept_as_plain_text_not_rendered_by_parser(): void
    {
        $this->assertSame(['<script>alert(1)</script>'], ArticleText::sections('<script>alert(1)</script>')[0]['paragraphs']);
    }
    public function test_reading_time_has_a_one_minute_minimum(): void
    {
        $this->assertSame(1, ArticleText::readingMinutes(''));
        $this->assertSame(3, ArticleText::readingMinutes(str_repeat('word ', 401)));
    }
}

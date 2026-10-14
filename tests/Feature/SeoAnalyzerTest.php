<?php

namespace Tests\Feature;

use App\Models\Media;
use App\Models\Post;
use App\Models\User;
use App\Services\HtmlSanitizer;
use App\Services\SeoAnalyzer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeoAnalyzerTest extends TestCase
{
    use RefreshDatabase;

    public function test_empty_article_does_not_pass_alt_or_other_checks(): void
    {
        $checks = app(SeoAnalyzer::class)->analyze(new Post);
        $this->assertNull($checks['Ảnh nội dung có ALT']);
        $this->assertNotContains(true, $checks);
    }

    public function test_metadata_fallbacks_and_unicode_length_boundaries(): void
    {
        foreach ([29 => false, 30 => true, 65 => true, 66 => false] as $length => $expected) {
            $checks = app(SeoAnalyzer::class)->analyze(new Post(['title' => str_repeat('ế', $length)]));
            $this->assertTrue($checks['Có tiêu đề tìm kiếm']);
            $this->assertSame($expected, $checks['Tiêu đề tìm kiếm khoảng 30–65 ký tự']);
            $this->assertTrue($checks['Đường dẫn đúng định dạng']);
        }
        foreach ([69 => false, 70 => true, 160 => true, 161 => false] as $length => $expected) {
            $checks = app(SeoAnalyzer::class)->analyze(new Post(['excerpt' => str_repeat('ế', $length)]));
            $this->assertSame($expected, $checks['Mô tả khoảng 70–160 ký tự']);
        }
        $checks = app(SeoAnalyzer::class)->analyze(new Post(['title' => str_repeat('a', 40), 'seo_title' => 'Ngắn', 'slug' => 'Sai slug!', 'excerpt' => str_repeat('a', 80), 'meta_description' => 'Ngắn']));
        $this->assertFalse($checks['Tiêu đề tìm kiếm khoảng 30–65 ký tự']);
        $this->assertFalse($checks['Mô tả khoảng 70–160 ký tự']);
        $this->assertFalse($checks['Đường dẫn đúng định dạng']);
    }

    public function test_alt_heading_and_keyword_are_checked_in_actual_content(): void
    {
        $analyzer = app(SeoAnalyzer::class);
        $checks = $analyzer->analyze(new Post(['title' => 'Từ khóa', 'excerpt' => 'Từ khóa', 'focus_keyword' => 'Từ khóa', 'content' => '<h2>&nbsp;</h2><img data-alt="giả" src="/media/1"><script>Từ khóa</script>']));
        $this->assertFalse($checks['Có heading trong bài']);
        $this->assertFalse($checks['Ảnh nội dung có ALT']);
        $this->assertFalse($checks['Từ khóa có trong nội dung']);
        $checks = $analyzer->analyze(new Post(['focus_keyword' => ' từ khóa ', 'content' => '<h2>Câu chuyện</h2><p>TỪ&nbsp;<strong>KHÓA</strong></p><img ALT = "Mô tả ảnh" src="/media/1">']));
        $this->assertTrue($checks['Có heading trong bài']);
        $this->assertTrue($checks['Ảnh nội dung có ALT']);
        $this->assertTrue($checks['Từ khóa có trong nội dung']);
        $checks = $analyzer->analyze(new Post(['content' => '<img alt="Đẹp"><img alt=" &nbsp; ">']));
        $this->assertFalse($checks['Ảnh nội dung có ALT']);
    }

    public function test_internal_links_require_real_anchor_and_same_origin(): void
    {
        $analyzer = app(SeoAnalyzer::class);
        foreach (['/gioi-thieu', 'cau-chuyen', url('/bai-viet/mot-bai')] as $href) {
            $checks = $analyzer->analyze(new Post(['content' => '<a href="'.$href.'">Đọc thêm</a>']));
            $this->assertTrue($checks['Có liên kết nội bộ'], $href);
        }
        foreach (['https://example.net/bai-viet/khac', '//example.net/bai-viet/khac', '#muc-1', 'mailto:a@example.com', 'javascript:alert(1)', '', '/\\example.net'] as $href) {
            $checks = $analyzer->analyze(new Post(['content' => '<a href="'.$href.'">Đọc thêm</a>']));
            $this->assertFalse($checks['Có liên kết nội bộ'], $href);
        }
        $checks = $analyzer->analyze(new Post(['content' => '<p>/bai-viet/noi-dung</p><img src="/bai-viet/anh">']));
        $this->assertFalse($checks['Có liên kết nội bộ']);
    }

    public function test_keyword_matches_visible_words_across_inline_formatting_but_not_parts_of_other_words(): void
    {
        $analyzer = app(SeoAnalyzer::class);
        foreach (['<p><strong>S</strong>EO cho bài viết</p>', '<p>Hướng dẫn</p><p>SEO</p>', '<p>Hướng dẫn<br>SEO!</p>'] as $content) {
            $checks = $analyzer->analyze(new Post(['content' => $content, 'focus_keyword' => 'seo']));
            $this->assertTrue($checks['Từ khóa có trong nội dung'], $content);
        }
        foreach (['<p>Seoul</p>', '<p>myseo</p>', '<p>S</p><p>EO</p>'] as $content) {
            $checks = $analyzer->analyze(new Post(['content' => $content, 'focus_keyword' => 'seo']));
            $this->assertFalse($checks['Từ khóa có trong nội dung'], $content);
        }
    }

    public function test_all_checks_pass_for_complete_article_and_reset_when_fields_are_cleared(): void
    {
        $cover = Media::factory()->create(['alt' => 'Ảnh bìa có mô tả']);
        $post = new Post([
            'title' => 'Hướng dẫn tối ưu nội dung bài viết cho SEO',
            'meta_description' => str_repeat('Mô tả bài viết. ', 6),
            'slug' => 'huong-dan-seo', 'focus_keyword' => 'SEO', 'cover_media_id' => $cover->id,
            'content' => '<h2>Hướng dẫn SEO</h2><p>Viết bài có ích.</p><img src="/media/1" alt="Minh họa bài viết"><a href="/gioi-thieu">Giới thiệu</a>',
        ]);
        $checks = app(SeoAnalyzer::class)->analyze($post);
        $this->assertCount(10, $checks);
        foreach ($checks as $label => $pass) {
            $this->assertTrue($pass, $label);
        }
        $post->fill(['title' => '', 'meta_description' => '', 'slug' => '', 'focus_keyword' => '', 'cover_media_id' => null, 'content' => '']);
        $checks = app(SeoAnalyzer::class)->analyze($post);
        $this->assertNotContains(true, $checks);
        $this->assertNull($checks['Ảnh nội dung có ALT']);
    }

    public function test_admin_can_analyze_unsaved_input_without_creating_post(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->postJson(route('admin.posts.seo-checks'), ['title' => 'Bài chưa lưu', 'content' => '<h2>Đang viết</h2>'])
            ->assertOk()->assertJsonPath('checks.Có heading trong bài', true)
            ->assertJsonPath('checks.Ảnh nội dung có ALT', null);
        $this->assertDatabaseCount('posts', 0);
        $this->postJson(route('admin.posts.seo-checks'), ['title' => ['invalid']])->assertUnprocessable();
    }

    public function test_library_alt_is_used_for_content_images_and_cover_without_overwriting_custom_alt(): void
    {
        $media = Media::factory()->create(['alt' => 'Khung cảnh bình yên']);
        $content = '<p><img src="'.$media->url().'" alt=""></p>';
        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->postJson(route('admin.posts.seo-checks'), ['content' => $content, 'cover_media_id' => $media->id])
            ->assertOk()->assertJsonPath('checks.Ảnh bìa có ALT', true)
            ->assertJsonPath('checks.Ảnh nội dung có ALT', true);
        $clean = app(HtmlSanitizer::class)->clean($content);
        $this->assertStringContainsString('alt="Khung cảnh bình yên"', $clean);
        $custom = app(HtmlSanitizer::class)->clean('<img src="'.$media->url().'" alt="Mô tả riêng">');
        $this->assertStringContainsString('alt="Mô tả riêng"', $custom);
        $this->assertStringNotContainsString('Khung cảnh bình yên', $custom);
        $this->postJson(route('admin.posts.seo-checks'), ['content' => '<p>Chưa chèn ảnh</p>', 'cover_media_id' => $media->id])
            ->assertOk()->assertJsonPath('checks.Ảnh bìa có ALT', true)
            ->assertJsonPath('checks.Ảnh nội dung có ALT', null);
    }

    public function test_alt_updated_in_library_is_rechecked_and_missing_images_still_fail(): void
    {
        $media = Media::factory()->create(['alt' => null]);
        $post = new Post(['content' => '<img src="/media/'.$media->id.'">']);
        $clean = app(HtmlSanitizer::class)->clean($post->content);
        $this->assertStringContainsString('alt=""', $clean);
        $analyzer = app(SeoAnalyzer::class);
        $this->assertFalse($analyzer->analyze($post)['Ảnh nội dung có ALT']);
        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->patch(route('admin.media.update', $media), ['alt' => 'Mô tả mới'])->assertSessionHasNoErrors();
        $this->assertTrue($analyzer->analyze($post)['Ảnh nội dung có ALT']);
        $post->content .= '<img src="https://external.example/media/'.$media->id.'" alt="">';
        $this->assertFalse($analyzer->analyze($post)['Ảnh nội dung có ALT']);
        $media->update(['alt' => '   ']);
        $post->content = '<img src="/media/'.$media->id.'">';
        $this->assertFalse($analyzer->analyze($post)['Ảnh nội dung có ALT']);
    }

    public function test_library_alt_is_escaped_when_written_into_html(): void
    {
        $media = Media::factory()->create(['alt' => '\"><script>alert(1)</script>']);
        $clean = app(HtmlSanitizer::class)->clean('<img src="'.$media->url().'">');
        $this->assertStringNotContainsString('<script>', $clean);
        $this->assertStringContainsString('&lt;script&gt;', $clean);
    }

    public function test_reader_and_guest_cannot_call_admin_analysis(): void
    {
        $this->postJson(route('admin.posts.seo-checks'), [])->assertUnauthorized();
        $this->actingAs(User::factory()->create())->postJson(route('admin.posts.seo-checks'), [])->assertForbidden();
    }
}

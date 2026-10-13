<?php

namespace Tests\Feature;

use App\Http\Requests\PostRequest;
use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use App\Services\HtmlSanitizer;
use App\Services\PostWriter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Mockery;
use Tests\TestCase;

class SecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_rich_text_removes_scripts_event_handlers_and_unsafe_urls(): void
    {
        $html = '<h2>Nội dung hợp lệ</h2><script>alert(1)</script><p onclick="alert(1)">Xin chào</p><a href="javascript:alert(1)">Link</a><img src="/media/1" onerror="alert(1)" alt="Ảnh"><iframe src="https://example.com"></iframe><svg onload="alert(1)"></svg>';
        $clean = app(HtmlSanitizer::class)->clean($html);
        $this->assertStringContainsString('<h2>Nội dung hợp lệ</h2>', $clean);
        foreach (['<script', 'onclick', 'onerror', 'javascript:', '<iframe', '<svg', 'onload'] as $unsafe) {
            $this->assertStringNotContainsString($unsafe, strtolower($clean));
        }
    }

    public function test_rich_text_keeps_safe_editor_formatting(): void
    {
        $clean = app(HtmlSanitizer::class)->clean('<h3>Tiêu đề nhỏ</h3><p style="text-align:center;color:#24332d;font-size:18px"><strong>Đậm</strong> <em>Nghiêng</em> <u>Gạch dưới</u></p><ul><li>Mục một</li></ul><table><tbody><tr><td>Ô dữ liệu</td></tr></tbody></table><figure><img src="/media/1" alt="Ảnh minh họa"><figcaption>Chú thích ảnh</figcaption></figure>');
        foreach (['<h3>', '<strong>', '<em>', '<u>', 'text-align:center', 'font-size:18px', '<ul>', '<table>', '<figcaption>'] as $format) {
            $this->assertStringContainsString($format, $clean);
        }
    }

    public function test_table_of_contents_keeps_unicode_and_stable_heading_anchors(): void
    {
        $result = app(HtmlSanitizer::class)->withHeadings('<h2>Một góc viết</h2><p>Nội dung.</p><h3>Hành trình nhỏ 🌿</h3>');
        $this->assertSame([
            ['id' => 'section-1', 'title' => 'Một góc viết', 'level' => 2],
            ['id' => 'section-2', 'title' => 'Hành trình nhỏ 🌿', 'level' => 3],
        ], $result['headings']);
        $this->assertStringContainsString('id="section-1"', $result['html']);
        $this->assertStringContainsString('id="section-2"', $result['html']);
    }

    public function test_image_alignment_is_kept_without_hidden_or_positioned_content(): void
    {
        $clean = app(HtmlSanitizer::class)->clean('<img src="/media/1" alt="Ảnh" style="display:block;margin-left:auto;margin-right:auto;max-width:100%"><p style="display:none;position:fixed;z-index:9999">Ẩn nguy hiểm</p>');
        $this->assertStringContainsString('display:block', $clean);
        $this->assertStringContainsString('margin-left:auto', $clean);
        $this->assertStringNotContainsString('display:none', $clean);
        $this->assertStringNotContainsString('position:', $clean);
        $this->assertStringNotContainsString('z-index:', $clean);
    }

    public function test_invalid_inline_media_rolls_back_the_whole_post_save(): void
    {
        $category = Category::factory()->create();
        $request = $this->postRequest(['content' => '<p>Nội dung</p><img src="/media/99999999" alt="Ảnh mất">', 'categories' => [$category->id]]);

        try {
            app(PostWriter::class)->save($request);
            $this->fail('Ảnh không tồn tại phải bị từ chối.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('content', $exception->errors());
        }

        $this->assertDatabaseCount('posts', 0);
        $this->assertDatabaseCount('post_category', 0);
    }

    public function test_stale_editor_version_cannot_overwrite_newer_content(): void
    {
        $post = Post::factory()->create(['content' => '<p>Nội dung mới đã lưu</p>', 'lock_version' => 2]);
        $category = Category::factory()->create();
        $request = $this->postRequest(['lock_version' => 1, 'content' => '<p>Nội dung cũ ở tab khác</p>', 'categories' => [$category->id]]);

        try {
            app(PostWriter::class)->save($request, $post);
            $this->fail('Không cho phép ghi đè phiên bản mới.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('lock_version', $exception->errors());
        }

        $this->assertSame('<p>Nội dung mới đã lưu</p>', $post->fresh()->content);
        $this->assertSame(2, $post->fresh()->lock_version);
    }

    public function test_changing_published_slug_saves_redirect_and_preserves_author(): void
    {
        $post = Post::factory()->published()->create(['slug' => 'duong-dan-cu', 'lock_version' => 1]);
        $category = Category::factory()->create();
        $request = $this->postRequest(['slug' => 'duong-dan-moi', 'status' => 'published', 'lock_version' => 1, 'categories' => [$category->id]]);
        $saved = app(PostWriter::class)->save($request, $post);

        $this->assertSame('duong-dan-moi', $saved->slug);
        $this->assertSame($post->user_id, $saved->user_id);
        $this->assertSame(2, $saved->lock_version);
        $this->assertDatabaseHas('post_redirects', ['post_id' => $post->id, 'slug' => 'duong-dan-cu']);
        $this->assertDatabaseHas('post_category', ['post_id' => $post->id, 'category_id' => $category->id]);
    }

    public function test_admin_command_requires_hidden_interactive_password(): void
    {
        $this->artisan('blog:admin', ['email' => 'owner@example.com', '--name' => 'Tác giả', '--no-interaction' => true])->assertFailed();
        $this->assertDatabaseCount('users', 0);
    }

    /** @param array<string, mixed> $overrides */
    private function postRequest(array $overrides): PostRequest
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $data = array_replace(['title' => 'Bài viết kiểm thử', 'slug' => 'bai-viet-kiem-thu', 'content' => '<p>Nội dung an toàn</p>', 'status' => 'draft', 'categories' => [], 'tags' => [], 'lock_version' => 1], $overrides);
        $request = Mockery::mock(PostRequest::class);
        $request->shouldReceive('validated')->once()->andReturn($data);
        $request->shouldReceive('user')->andReturn($admin);
        $request->shouldReceive('boolean')->with('is_featured')->andReturn(false);

        return $request;
    }
}

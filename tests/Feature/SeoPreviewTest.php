<?php

namespace Tests\Feature;

use App\Models\Media;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeoPreviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_saved_metadata_and_private_media_preview_render_in_admin_editor(): void
    {
        $cover = Media::factory()->create();
        $og = Media::factory()->create();
        $post = Post::factory()->create([
            'title' => 'Tiêu đề gốc', 'seo_title' => 'Tiêu đề tìm kiếm riêng',
            'meta_description' => 'Mô tả tìm kiếm riêng', 'og_title' => 'Tiêu đề mạng chia sẻ',
            'og_description' => 'Mô tả mạng chia sẻ', 'canonical_url' => 'https://example.com/bai-viet',
            'cover_media_id' => $cover->id, 'og_media_id' => $og->id,
        ]);
        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get(route('admin.posts.edit', $post))->assertOk()->assertSee('data-seo-preview', false)
            ->assertSee('data-seo-search-title>Tiêu đề tìm kiếm riêng', false)
            ->assertSee('data-seo-social-title>Tiêu đề mạng chia sẻ', false)
            ->assertSee('src="'.$og->url().'"', false)
            ->assertSee('data-preview-url="'.$cover->url().'"', false)
            ->assertSee('Google và nền tảng chia sẻ có thể hiển thị khác.');
    }

    public function test_empty_overrides_use_post_title_excerpt_slug_and_cover(): void
    {
        $cover = Media::factory()->create();
        $post = Post::factory()->create(['title' => 'Một bài viết nhỏ', 'excerpt' => 'Mô tả từ giới thiệu', 'cover_media_id' => $cover->id]);
        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get(route('admin.posts.edit', $post))->assertOk()->assertSee('data-seo-search-title>Một bài viết nhỏ', false)
            ->assertSee('data-seo-description>Mô tả từ giới thiệu', false)
            ->assertSee('data-seo-social-title>Một bài viết nhỏ', false)
            ->assertSee('data-seo-url>'.route('posts.show', $post->slug), false)->assertSee('src="'.$cover->url().'"', false);
    }

    public function test_preview_escapes_html_and_tolerates_invalid_old_input(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->withSession(['_old_input' => ['title' => '<script>unsafe()</script>', 'seo_title' => ['bad'], 'slug' => ['bad'], 'canonical_url' => 'javascript:alert(1)', 'og_media_id' => ['bad']]])
            ->get(route('admin.posts.create'))->assertOk()->assertSee('&lt;script&gt;unsafe()', false)
            ->assertDontSee('<script>unsafe()</script>', false)->assertSee('data-seo-preview', false)
            ->assertDontSee('data-seo-url>javascript:', false);
    }

    public function test_reader_cannot_access_preview_editor(): void
    {
        $this->actingAs(User::factory()->create())->get(route('admin.posts.create'))->assertForbidden();
    }
}

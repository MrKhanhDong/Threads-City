<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Comment;
use App\Models\Post;
use App\Models\PostRedirect;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicBlogTest extends TestCase
{
    use RefreshDatabase;

    public function test_desktop_columns_use_existing_personal_routes_and_replace_the_old_right_sidebar(): void
    {
        $reader = User::factory()->create();
        $response = $this->actingAs($reader)->get(route('home'))->assertOk()
            ->assertSee('data-column-workspace', false)
            ->assertSee('data-desktop-column-frame src="about:blank"', false)
            ->assertSee('data-column-drag="primary"', false)
            ->assertSee('data-column-drag="secondary"', false)
            ->assertSee('data-column-close', false)
            ->assertDontSee('aria-label="Khám phá blog"', false);
        foreach (['search.index', 'activity.index', 'insights.index', 'saved', 'liked.index', 'archives.index'] as $route) {
            $response->assertSee('data-column-url="'.e(route($route)).'"', false);
        }
        $response->assertSee('data-column-url="'.e(route('profile.show', $reader->username ?: $reader->id)).'"', false);
    }

    public function test_first_request_to_unknown_route_renders_404_without_web_session(): void
    {
        $this->get('/missing-route')->assertNotFound()->assertSee('Câu chuyện này không ở đây.');
    }

    public function test_home_and_article_render_only_published_content(): void
    {
        $post = Post::factory()->published()->create(['title' => 'Câu chuyện công khai', 'content' => '<h2>Góc nhìn</h2><p>Nội dung thật.</p><h3>Hành trình</h3>']);
        $draft = Post::factory()->create(['title' => 'Bản nháp riêng tư']);
        $this->get(route('home'))->assertOk()->assertSee('Câu chuyện công khai')->assertDontSee('Bản nháp riêng tư');
        $this->get(route('posts.show', $post->slug))->assertOk()->assertSee('Góc nhìn')->assertSee('section-1')->assertSee('application/ld+json', false);
        $this->get(route('posts.show', $draft->slug))->assertNotFound();
        $this->assertSame(1, $post->fresh()->views_count);
    }

    public function test_search_category_and_tag_filters_are_combined(): void
    {
        $category = Category::factory()->create(['slug' => 'hoc-tap']);
        $tag = Tag::factory()->create(['slug' => 'laravel']);
        $post = Post::factory()->published()->create(['title' => 'Học Laravel mỗi ngày']);
        $post->categories()->attach($category);
        $post->tags()->attach($tag);
        Post::factory()->published()->create(['title' => 'Chuyện của một chuyến đi']);
        $this->get(route('posts.index', ['q' => 'Laravel', 'category' => 'hoc-tap', 'tag' => 'laravel']))->assertOk()->assertSee('Học Laravel mỗi ngày')->assertDontSee('Chuyện của một chuyến đi');
        $this->get(route('posts.index', ['q' => 'khong-ton-tai']))->assertOk()->assertSee('Chưa tìm thấy bài viết');
        $this->get(route('posts.index', ['q' => ['bad']]))->assertSessionHasErrors('q');
    }

    public function test_old_slug_redirects_only_when_target_is_public(): void
    {
        $post = Post::factory()->published()->create(['slug' => 'duong-moi']);
        PostRedirect::factory()->create(['post_id' => $post->id, 'slug' => 'duong-cu']);
        $this->get(route('posts.show', 'duong-cu'))->assertStatus(301)->assertRedirect(route('posts.show', 'duong-moi'));
        $post->update(['status' => 'draft', 'published_at' => null]);
        $this->get(route('posts.show', 'duong-cu'))->assertNotFound();
    }

    public function test_sitemap_excludes_drafts_future_and_trashed_posts(): void
    {
        $published = Post::factory()->published()->create();
        $draft = Post::factory()->create();
        $future = Post::factory()->scheduled()->create();
        $deleted = Post::factory()->published()->create();
        $deleted->delete();
        $this->get(route('sitemap'))->assertOk()->assertHeader('Content-Type', 'application/xml; charset=UTF-8')->assertSee($published->slug)->assertDontSee($draft->slug)->assertDontSee($future->slug)->assertDontSee($deleted->slug);
        $xml = simplexml_load_string($this->get(route('sitemap'))->getContent());
        $this->assertNotFalse($xml);
        $this->assertSame('urlset', $xml->getName());
        $this->assertCount(6, $xml->children('http://www.sitemaps.org/schemas/sitemap/0.9'));
        $this->get(route('robots'))->assertOk()->assertSee('Disallow: /admin', false);
    }

    public function test_metadata_is_escaped_and_article_html_is_sanitized(): void
    {
        $post = Post::factory()->published()->create(['seo_title' => 'SEO tiêu đề riêng', 'meta_description' => 'Mô tả riêng " đẹp', 'og_title' => 'Tiêu đề chia sẻ', 'canonical_url' => 'https://example.com/cau-chuyen', 'content' => '<h2>Đọc an toàn</h2><script>alert(9)</script><p onclick="alert(8)">Xin chào</p>']);
        $response = $this->get(route('posts.show', $post->slug));
        $response->assertOk()->assertSee('SEO tiêu đề riêng')->assertSee('https://example.com/cau-chuyen', false)->assertSee('Tiêu đề chia sẻ')->assertDontSee('<script>alert(9)</script>', false)->assertDontSee('onclick="alert(8)"', false);
    }

    public function test_guest_sees_approved_comments_not_pending_hidden_or_spam(): void
    {
        $post = Post::factory()->published()->create();
        Comment::factory()->approved()->create(['post_id' => $post->id, 'body' => 'Lời nhắn đã duyệt']);
        foreach (['pending', 'hidden', 'spam'] as $status) {
            Comment::factory()->create(['post_id' => $post->id, 'status' => $status, 'body' => 'Riêng tư '.$status]);
        }
        $this->get(route('posts.show', $post->slug))->assertOk()->assertSee('Lời nhắn đã duyệt')->assertDontSee('Riêng tư pending')->assertDontSee('Riêng tư hidden')->assertDontSee('Riêng tư spam');
    }

    public function test_public_information_pages_and_empty_states_render(): void
    {
        foreach (['home', 'posts.index', 'about', 'contact', 'privacy', 'terms', 'login', 'register', 'password.request'] as $name) {
            $this->get(route($name))->assertOk();
        }
        $this->get(route('author'))->assertRedirect(route('about'));
        $this->get('/khong-co-trang-nay')->assertNotFound()->assertSee('Câu chuyện này không ở đây.');
    }

    public function test_about_describes_a_community_and_information_pages_have_current_breadcrumbs(): void
    {
        $this->get(route('about'))->assertOk()->assertSee('Threads là không gian chia sẻ dành cho mọi người.')->assertSee('Mỗi tài khoản đều có thể đăng bài')->assertSee('Khám phá và kết nối')
            ->assertDontSee('Gặp tác giả')->assertDontSee('MỘC /')->assertDontSee('góc viết của tôi')->assertDontSee('Ở đây, tác giả viết');
        foreach (['about' => 'Về Threads', 'contact' => 'Liên hệ Threads', 'privacy' => 'Chính sách quyền riêng tư', 'terms' => 'Điều khoản sử dụng', 'login' => 'Đăng nhập', 'search.index' => 'Tìm kiếm'] as $route => $label) {
            $html = $this->get(route($route))->assertOk()->getContent();
            $this->assertSame(1, preg_match('~<nav class="site-breadcrumbs"[^>]*>(.*?)</nav>~s', $html, $matches));
            $this->assertStringContainsString('href="'.route('home').'">Threads</a>', $matches[1]);
            $this->assertStringContainsString('aria-current="page">'.$label.'</span>', $matches[1]);
            $this->assertSame(1, substr_count($matches[1], 'aria-current="page"'));
        }
    }

    public function test_article_breadcrumbs_use_its_title_and_category_and_link_to_its_own_author(): void
    {
        $writer = User::factory()->create(['username' => 'nguoi_viet']);
        $category = Category::factory()->create(['name' => 'Học & chia sẻ']);
        $post = Post::factory()->published()->create(['user_id' => $writer->id, 'title' => 'Câu chuyện <riêng>', 'seo_title' => 'Tiêu đề SEO khác']);
        $post->categories()->attach($category);
        $html = $this->get(route('posts.show', $post->slug))->assertOk()->getContent();
        $this->assertSame(1, preg_match('~<nav class="site-breadcrumbs"[^>]*>(.*?)</nav>~s', $html, $matches));
        $this->assertStringContainsString('href="'.route('posts.index').'">Bài viết</a>', $matches[1]);
        $this->assertStringContainsString('href="'.route('categories.show', $category->slug).'">Học &amp; chia sẻ</a>', $matches[1]);
        $this->assertStringContainsString('aria-current="page">Câu chuyện &lt;riêng&gt;</span>', $matches[1]);
        $this->assertStringNotContainsString('Tiêu đề SEO khác', $matches[1]);
        $this->assertStringContainsString('href="'.route('profile.show', $writer->username).'"', $html);
        $this->assertStringNotContainsString('href="'.route('author').'"', $html);
        $post->categories()->detach();
        $html = $this->get(route('posts.show', $post->slug))->assertOk()->getContent();
        preg_match('~<nav class="site-breadcrumbs"[^>]*>(.*?)</nav>~s', $html, $matches);
        $this->assertSame(3, substr_count($matches[1], '<li>'));
    }

    public function test_personal_and_admin_pages_use_the_shared_breadcrumbs(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        foreach (['saved' => 'Bài viết đã lưu', 'liked.index' => 'Đã thích', 'archives.index' => 'Lưu trữ', 'activity.index' => 'Hoạt động', 'insights.index' => 'Thông tin chi tiết', 'account.edit' => 'Cài đặt tài khoản', 'admin.settings.edit' => 'Cài đặt Threads'] as $route => $label) {
            $html = $this->get(route($route))->assertOk()->getContent();
            $this->assertSame(1, preg_match('~<nav class="site-breadcrumbs"[^>]*>(.*?)</nav>~s', $html, $matches));
            $this->assertStringContainsString('aria-current="page">'.$label.'</span>', $matches[1]);
        }
    }
}

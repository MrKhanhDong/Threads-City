<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Post;
use Tests\TestCase;

class BlogTest extends TestCase
{
    public function test_public_pages_and_auth_forms_render(): void
    {
        foreach (['/', '/bai-viet', '/chuyen-muc', '/gioi-thieu', '/dang-nhap', '/dang-ky', '/quen-mat-khau', '/dat-lai-mat-khau/token?email=reader%40blog.local'] as $path) {
            $this->get($path)->assertOk()->assertSee('lang="vi"', false)->assertSee('Khang', false);
        }
    }
    public function test_articles_have_content_and_do_not_execute_html(): void
    {
        foreach (Post::published()->get() as $post) $this->get(route('posts.show', $post->slug))->assertOk()->assertSee($post->title);
        $post = $this->published();
        $post->update(['content' => "## A safe heading\n\n<script>window.attacked=true</script>"]);
        $this->get(route('posts.show', $post->slug))->assertOk()->assertSee('A safe heading')->assertSee('&lt;script&gt;', false)->assertDontSee('<script>window.attacked', false);
    }
    public function test_draft_archived_future_and_inactive_category_are_hidden(): void
    {
        $draft = Post::where('status', 'draft')->firstOrFail();
        $this->get(route('posts.show', $draft->slug))->assertNotFound();
        $post = $this->published();
        foreach (['archived', 'draft'] as $status) {
            $post->update(['status'=>$status]);
            $this->get(route('posts.show', $post->slug))->assertNotFound();
        }
        $post->update(['status'=>'published', 'published_at'=>now()->addDay()]);
        $this->get(route('posts.show', $post->slug))->assertNotFound();
        $post->update(['published_at'=>now()->subDay()]);
        $post->category->update(['is_active'=>false]);
        $this->get(route('posts.show', $post->slug))->assertNotFound();
        $this->get('/bai-viet')->assertViewHas('posts', fn ($items)=>!$items->contains('id', $post->id));
    }
    public function test_search_category_and_tag_filters_compose(): void
    {
        $this->get('/bai-viet?q=pHp')->assertOk()->assertViewHas('posts', fn ($p)=>$p->total() === 1 && $p->first()['slug'] === 'tu-php-thuan-den-laravel');
        $this->get('/bai-viet?category=lap-trinh')->assertOk()->assertViewHas('posts', fn ($p)=>$p->total() === 2);
        $this->get('/bai-viet?category=doi-song&q=Laravel')->assertOk()->assertViewHas('posts', fn ($p)=>$p->total() === 0);
        $this->get('/bai-viet?tag=PHP')->assertOk()->assertViewHas('posts', fn ($p)=>$p->total() === 1);
        $this->get('/bai-viet?q='.urlencode('cuốn sổ bên cạnh máy tính'))->assertOk()->assertViewHas('posts', fn ($p)=>$p->total() === 1);
    }
    public function test_filters_reject_arrays_and_escape_query_markup(): void
    {
        $this->get('/bai-viet?q='.urlencode('<script>alert(1)</script>'))->assertOk()->assertSee('&lt;script&gt;', false)->assertDontSee('<script>alert', false);
        foreach (['q', 'tag', 'category'] as $field) $this->getJson("/bai-viet?{$field}[]=x")->assertUnprocessable()->assertJsonValidationErrors($field);
        $this->getJson('/bai-viet?q='.str_repeat('x', 201))->assertUnprocessable();
    }
    public function test_empty_blog_and_missing_urls_are_handled(): void
    {
        Post::query()->update(['status'=>'draft']);
        $this->get('/')->assertOk()->assertSee('Chưa có bài viết');
        $this->get('/bai-viet')->assertOk()->assertSee('Chưa tìm thấy');
        $this->get('/bai-viet/not-found')->assertNotFound();
        $this->get('/not-found')->assertNotFound();
    }
    public function test_archive_paginates_and_preserves_filters(): void
    {
        $post = $this->published();
        for ($i=0; $i<8; $i++) $post->replicate()->fill(['slug'=>'pagination-'.$i, 'title'=>'Pagination '.$i])->save();
        $this->get('/bai-viet?q=Pagination')->assertOk()->assertViewHas('posts', fn ($p)=>$p->count() === 6 && $p->total() === 8)->assertSee('q=Pagination', false);
        $this->get('/bai-viet?q=Pagination&page=2')->assertOk()->assertViewHas('posts', fn ($p)=>$p->count() === 2);
    }
    public function test_views_count_once_per_session_not_for_admin_preview(): void
    {
        $post = $this->published();
        $this->get(route('posts.show', $post->slug))->assertOk();
        $this->get(route('posts.show', $post->slug))->assertOk();
        $this->assertSame(1, (int)$post->fresh()->views_count);
        $this->actingAs($this->admin())->get(route('admin.posts.preview', $post))->assertOk();
        $this->assertSame(1, (int)$post->fresh()->views_count);
    }
    public function test_blog_has_no_social_feed_and_counts_are_real(): void
    {
        $this->get('/')->assertOk()->assertDontSee('Dành cho bạn')->assertDontSee('Theo dõi')->assertDontSee('Gợi ý tác giả');
        $this->assertSame(4, Post::published()->count());
        $this->assertSame(3, Category::count());
    }
}

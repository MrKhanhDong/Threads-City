<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Comment;
use App\Models\CommentReport;
use App\Models\Media;
use App\Models\Post;
use App\Models\PostDailyView;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_moderation_and_report_resolution_change_real_records(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $comment = Comment::factory()->create(['body' => 'Bình luận cần duyệt']);
        $this->actingAs($admin)->patch(route('admin.comments.update', $comment), ['reason' => 'Kiểm duyệt nội dung', 'status' => 'approved'])->assertRedirect()->assertSessionHasNoErrors();
        $this->get(route('posts.show', $comment->post->slug))->assertOk()->assertSee('Bình luận cần duyệt');
        $report = CommentReport::factory()->create(['comment_id' => $comment->id]);
        $this->get(route('admin.comments.index'))->assertOk()->assertSee($report->reason);
        $this->post(route('admin.reports.resolve', $report), ['reason' => 'Đã kiểm tra báo cáo'])->assertRedirect();
        $this->assertTrue($report->fresh()->resolved);
        $this->patch(route('admin.comments.update', $comment), ['reason' => 'Kiểm duyệt nội dung', 'status' => 'hidden'])->assertRedirect();
        $this->get(route('posts.show', $comment->post->slug))->assertOk()->assertDontSee('Bình luận cần duyệt');
        $this->patch(route('admin.comments.update', $comment), ['reason' => 'Kiểm duyệt nội dung', 'status' => 'invalid'])->assertSessionHasErrors('status');
    }

    public function test_dashboard_uses_actual_period_views_and_zero_fills_days(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $post = Post::factory()->published()->create();
        PostDailyView::factory()->create(['post_id' => $post->id, 'view_date' => today(), 'views' => 4]);
        PostDailyView::factory()->create(['post_id' => $post->id, 'view_date' => today()->subDay(), 'views' => 3]);
        PostDailyView::factory()->create(['post_id' => $post->id, 'view_date' => today()->subDays(10), 'views' => 99]);
        $comment = Comment::factory()->create(['post_id' => $post->id]);
        CommentReport::factory()->create(['comment_id' => $comment->id, 'resolved' => false]);
        $response = $this->actingAs($admin)->get(route('admin.index', ['days' => 7]))->assertOk();
        $chart = $response->viewData('chart');
        $this->assertCount(7, $chart);
        $this->assertSame(7, array_sum(array_column($chart, 'views')));
        $this->assertSame(0, $chart[0]['views']);
        $this->assertSame(1, $response->viewData('counts')['reports']);
        $this->assertEquals(7, $response->viewData('topPosts')->first()->period_views);
        $this->get(route('admin.index', ['days' => 30]))->assertOk()->assertViewHas('chart', fn (array $data): bool => count($data) === 30 && array_sum(array_column($data, 'views')) === 106);
        $this->get(route('admin.index', ['days' => 8]))->assertSessionHasErrors('days');
    }

    public function test_guest_and_reader_cannot_access_admin_or_upload(): void
    {
        $this->get(route('admin.index'))->assertRedirect(route('login'));
        $reader = User::factory()->create();
        $this->actingAs($reader)->get(route('admin.index'))->assertForbidden();
        $this->post(route('admin.posts.store'), [])->assertForbidden();
        $this->postJson(route('admin.media.store'), [])->assertForbidden();
    }

    public function test_dashboard_counts_comments_and_replies_without_restoring_trash(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $post = Post::factory()->published()->create(['user_id' => $admin->id]);
        $parent = Comment::factory()->create(['post_id' => $post->id]);
        Comment::factory()->create(['post_id' => $post->id, 'parent_id' => $parent->id]);
        foreach (['pending', 'hidden', 'spam'] as $status) {
            Comment::factory()->create(['post_id' => $post->id, 'status' => $status]);
        }
        $trashedPost = Post::factory()->published()->create(['user_id' => $admin->id]);
        $trashedComment = Comment::factory()->create(['post_id' => $trashedPost->id, 'status' => 'pending']);
        CommentReport::factory()->create(['comment_id' => $trashedComment->id]);
        $trashedPost->delete();

        $response = $this->actingAs($admin)->get(route('admin.index'))
            ->assertOk()->assertSee('Trả lời')->assertSee('1 bình luận blog cũ đang chờ duyệt')
            ->assertSee(route('admin.comments.index', ['status' => 'pending']), false)
            ->assertSee('Cần xem xét');
        $counts = $response->viewData('counts');
        $this->assertSame(5, $counts['comments']);
        $this->assertSame(1, $counts['pending_comments']);
        $this->assertSame(0, $counts['reports']);
        $this->assertSame(1, $counts['trash']);
        $this->get(route('admin.comments.index'))->assertOk()->assertViewHas('comments', fn ($comments): bool => $comments->total() === 5);
        $this->get(route('admin.comments.index', ['status' => 'pending']))->assertOk()->assertViewHas('comments', fn ($comments): bool => $comments->total() === 1);
        $this->post(route('admin.posts.restore', $trashedPost->id))->assertRedirect()->assertSessionHasNoErrors();
        $this->get(route('admin.index'))->assertOk()->assertViewHas('counts', fn (array $counts): bool => $counts['comments'] === 6 && $counts['pending_comments'] === 2 && $counts['reports'] === 1);
        $this->assertSame('pending', $trashedComment->fresh()->status);
    }

    public function test_dashboard_handles_no_comments_and_counts_database_changes(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $post = Post::factory()->published()->create(['user_id' => $admin->id]);
        $this->actingAs($admin)->get(route('admin.index'))->assertOk()
            ->assertViewHas('counts', fn (array $counts): bool => $counts['comments'] === 0 && $counts['pending_comments'] === 0)
            ->assertDontSee('bình luận cũ đang chờ duyệt');
        $comment = Comment::factory()->create(['post_id' => $post->id]);
        $this->get(route('admin.index', ['comments' => 999]))->assertOk()
            ->assertViewHas('counts', fn (array $counts): bool => $counts['comments'] === 1 && $counts['pending_comments'] === 0);
        $this->delete(route('admin.comments.destroy', $comment))->assertRedirect()->assertSessionHasNoErrors();
        $this->get(route('admin.index'))->assertOk()
            ->assertViewHas('counts', fn (array $counts): bool => $counts['comments'] === 0);
    }

    public function test_admin_pages_render_with_real_database_records(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $post = Post::factory()->published()->create(['user_id' => $admin->id]);
        Category::factory()->create();
        Tag::factory()->create();
        foreach (['admin.index', 'admin.posts.index', 'admin.posts.create', 'admin.media.index', 'admin.comments.index', 'admin.users.index', 'admin.settings.edit'] as $name) {
            $this->actingAs($admin)->get(route($name))->assertOk();
        }
        $this->get(route('admin.posts.edit', $post))->assertOk()->assertSee('data-editor', false);
        $this->get(route('admin.taxonomies.index', 'categories'))->assertOk();
        $this->get(route('admin.taxonomies.index', 'tags'))->assertOk();
    }

    public function test_admin_creates_draft_previews_publishes_and_redirects_old_slug(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $category = Category::factory()->create();
        $tag = Tag::factory()->create();
        $data = $this->postData($category->id, ['tags' => [$tag->id]]);
        $this->actingAs($admin)->post(route('admin.posts.store'), $data)->assertSessionHasNoErrors();
        $post = Post::firstOrFail();
        $this->assertSame('draft', $post->status);
        $this->assertCount(1, $post->categories);
        $this->assertCount(1, $post->tags);
        $this->get(route('posts.show', $post->slug))->assertNotFound();
        $this->get(route('admin.posts.preview', $post))->assertOk()->assertSee('Đang xem bản đã lưu');
        $this->assertSame(0, $post->fresh()->views_count);
        $this->put(route('admin.posts.update', $post), array_replace($data, ['status' => 'published', 'lock_version' => 1]))->assertSessionHasNoErrors();
        $this->assertTrue($post->fresh()->published_at->lte(now()));
        $this->put(route('admin.posts.update', $post), array_replace($data, ['status' => 'published', 'slug' => 'duong-dan-da-doi', 'lock_version' => 2]))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('post_redirects', ['post_id' => $post->id, 'slug' => 'bai-kiem-thu']);
        $this->get(route('posts.show', 'bai-kiem-thu'))->assertStatus(301);
    }

    public function test_post_validation_retains_content_and_never_creates_invalid_post(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->from(route('admin.posts.create'))->post(route('admin.posts.store'), ['title' => 'Tiêu đề đã nhập', 'content' => '<h2>Nội dung cần giữ</h2>', 'status' => 'draft'])->assertRedirect(route('admin.posts.create'))->assertSessionHasErrors('categories')->assertSessionHasInput('content', '<h2>Nội dung cần giữ</h2>');
        $this->assertDatabaseCount('posts', 0);
        $this->get(route('admin.posts.create'))->assertOk()->assertSee('Nội dung cần giữ');
    }

    public function test_array_values_in_invalid_form_do_not_crash_reloaded_editor(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->from(route('admin.posts.create'))->post(route('admin.posts.store'), ['title' => ['bad'], 'slug' => ['bad'], 'cover_media_id' => ['bad'], 'og_media_id' => ['bad'], 'content' => ['bad'], 'status' => 'draft'])->assertSessionHasErrors('title');
        $this->get(route('admin.posts.create'))->assertOk();
    }

    public function test_schedule_only_becomes_public_at_due_time(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $category = Category::factory()->create();
        $this->actingAs($admin)->post(route('admin.posts.store'), $this->postData($category->id, ['status' => 'scheduled', 'published_at' => now()->addHour()->format('Y-m-d H:i:s')]))->assertSessionHasNoErrors();
        $post = Post::firstOrFail();
        $this->get(route('posts.show', $post->slug))->assertNotFound();
        $this->travel(2)->hours();
        $this->get(route('posts.show', $post->slug))->assertOk();
    }

    public function test_soft_delete_restore_and_permanent_delete_require_confirmation(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $post = Post::factory()->published()->create(['user_id' => $admin->id]);
        $comment = Comment::factory()->create(['post_id' => $post->id]);
        $this->actingAs($admin)->delete(route('admin.posts.destroy', $post))->assertRedirect();
        $this->assertSoftDeleted($post);
        $this->assertDatabaseHas('comments', ['id' => $comment->id]);
        $this->get(route('admin.posts.index', ['status' => 'trash']))->assertOk()->assertSee($post->title);
        $this->post(route('admin.posts.restore', $post->id))->assertRedirect();
        $this->assertSame('draft', $post->fresh()->status);
        $this->assertNull($post->fresh()->published_at);
        $this->delete(route('admin.posts.destroy', $post))->assertRedirect();
        $this->delete(route('admin.posts.force-delete', $post->id))->assertSessionHasErrors('confirm');
        $this->assertSoftDeleted($post);
        $this->delete(route('admin.posts.force-delete', $post->id), ['confirm' => 1])->assertRedirect();
        $this->assertDatabaseMissing('posts', ['id' => $post->id]);
        $this->assertDatabaseMissing('comments', ['id' => $comment->id]);
    }

    public function test_autosave_updates_draft_and_rejects_old_version(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $category = Category::factory()->create();
        $post = Post::factory()->create(['user_id' => $admin->id]);
        $data = $this->postData($category->id, ['slug' => $post->slug, 'lock_version' => 1]);
        $this->actingAs($admin)->postJson(route('admin.posts.autosave', $post), $data)->assertOk()->assertJsonPath('version', 2);
        $this->postJson(route('admin.posts.autosave', $post), $data)->assertUnprocessable()->assertJsonValidationErrors('lock_version');
        $this->assertSame(2, $post->fresh()->lock_version);
    }

    public function test_image_upload_is_real_private_until_published_and_referenced(): void
    {
        Storage::fake('local');
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->postJson(route('admin.media.store'), ['file' => UploadedFile::fake()->image('anh.png', 640, 480), 'alt' => 'Ảnh minh họa'])->assertCreated();
        $media = Media::firstOrFail();
        Storage::disk('local')->assertExists($media->path);
        $this->assertSame('Ảnh minh họa', $media->alt);
        $this->get($media->url())->assertOk();
        auth()->logout();
        $this->get($media->url())->assertNotFound();
        $post = Post::factory()->published()->create(['cover_media_id' => $media->id]);
        $this->get($media->url())->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff');
        $post->delete();
        $this->get($media->url())->assertNotFound();
    }

    public function test_cover_picker_upload_can_be_used_without_reloading_or_saving_a_draft_first(): void
    {
        Storage::fake('local');
        $admin = User::factory()->create(['role' => 'admin']);
        $category = Category::factory()->create();
        $this->actingAs($admin)->get(route('admin.posts.create'))->assertOk()
            ->assertSee('data-cover-picker', false)->assertSee('Thư viện ảnh')->assertSee('Tải ảnh lên')
            ->assertSee('Dùng làm ảnh bìa')->assertDontSee('Mở thư viện / tải ảnh');
        $upload = $this->postJson(route('admin.media.store'), ['file' => UploadedFile::fake()->image('bia-moi.jpg', 640, 480), 'alt' => 'Ảnh bìa mới'])
            ->assertCreated()->assertJsonStructure(['id', 'location', 'alt']);
        $this->assertDatabaseCount('posts', 0);
        $this->assertDatabaseCount('media', 1);
        $this->post(route('admin.posts.store'), $this->postData($category->id, ['cover_media_id' => $upload->json('id')]))
            ->assertSessionHasNoErrors()->assertRedirect();
        $post = Post::sole();
        $this->assertSame($upload->json('id'), $post->cover_media_id);
        $this->get(route('admin.posts.edit', $post))->assertOk()->assertSee('src="'.$upload->json('location').'"', false);
        Storage::disk('local')->assertExists(Media::sole()->path);
    }

    public function test_upload_rejects_executables_svg_and_oversized_images(): void
    {
        Storage::fake('local');
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->postJson(route('admin.media.store'), ['file' => UploadedFile::fake()->create('danger.php', 10, 'application/x-php')])->assertUnprocessable();
        $this->postJson(route('admin.media.store'), ['file' => UploadedFile::fake()->create('image.svg', 10, 'image/svg+xml')])->assertUnprocessable();
        $this->postJson(route('admin.media.store'), ['file' => UploadedFile::fake()->image('large.png')->size(6000)])->assertUnprocessable();
        $this->assertDatabaseCount('media', 0);
    }

    public function test_media_in_trash_cannot_be_deleted_but_unused_media_can(): void
    {
        Storage::fake('local');
        $admin = User::factory()->create(['role' => 'admin']);
        $media = Media::factory()->create(['path' => 'media/example.jpg']);
        Storage::disk('local')->put($media->path, 'test');
        $post = Post::factory()->create(['cover_media_id' => $media->id]);
        $post->delete();
        $this->actingAs($admin)->delete(route('admin.media.destroy', $media))->assertSessionHasErrors('media');
        Storage::disk('local')->assertExists($media->path);
        $post->forceDelete();
        $this->delete(route('admin.media.destroy', $media))->assertRedirect();
        Storage::disk('local')->assertMissing($media->path);
        $this->assertDatabaseMissing('media', ['id' => $media->id]);
    }

    public function test_media_and_avatar_backup_restore_preserves_bytes_metadata_and_access(): void
    {
        Storage::fake('local');
        Storage::fake('recovery-test');
        $disk = Storage::disk('local');
        $backup = Storage::disk('recovery-test');
        $files = $this->app['files'];
        $admin = User::factory()->create(['role' => 'admin']);
        $upload = $this->actingAs($admin)->postJson(route('admin.media.store'), [
            'file' => UploadedFile::fake()->image('cover.jpg', 80, 60),
            'alt' => 'Ảnh kiểm thử khôi phục',
        ])->assertCreated();
        $media = Media::findOrFail($upload->json('id'));
        $post = Post::factory()->published()->create(['user_id' => $admin->id, 'cover_media_id' => $media->id]);
        $post->media()->attach($media);
        $reader = User::factory()->create();
        $this->actingAs($reader)->patch(route('account.update'), [
            'name' => $reader->name,
            'avatar' => UploadedFile::fake()->image('avatar.png', 40, 40),
        ])->assertRedirect()->assertSessionHasNoErrors();
        $avatarPath = $reader->fresh()->avatar_path;
        $mediaHash = hash_file('sha256', $disk->path($media->path));
        $avatarHash = hash_file('sha256', $disk->path($avatarPath));
        $disk->put('unrelated-private.txt', 'Dữ liệu riêng không được ghi đè');

        foreach (['media', 'avatars'] as $folder) {
            $this->assertTrue($files->copyDirectory($disk->path($folder), $backup->path($folder)));
        }
        $this->assertSame($mediaHash, hash_file('sha256', $backup->path($media->path)));
        $this->assertSame($avatarHash, hash_file('sha256', $backup->path($avatarPath)));
        $backup->assertMissing('unrelated-private.txt');

        $this->assertTrue($disk->delete([$media->path, $avatarPath]));
        $this->get(route('media.show', $media))->assertNotFound();
        $this->get(route('avatar.show', $reader))->assertNotFound();
        foreach (['media', 'avatars'] as $folder) {
            $this->assertTrue($files->copyDirectory($backup->path($folder), $disk->path($folder)));
        }

        $this->assertSame($mediaHash, hash_file('sha256', $disk->path($media->path)));
        $this->assertSame($avatarHash, hash_file('sha256', $disk->path($avatarPath)));
        $this->assertSame('Dữ liệu riêng không được ghi đè', $disk->get('unrelated-private.txt'));
        $this->assertSame([80, 60], array_slice(getimagesize($disk->path($media->path)), 0, 2));
        $this->assertSame([40, 40], array_slice(getimagesize($disk->path($avatarPath)), 0, 2));
        $this->assertDatabaseHas('media', ['id' => $media->id, 'path' => $media->path, 'alt' => 'Ảnh kiểm thử khôi phục']);
        $this->assertDatabaseHas('users', ['id' => $reader->id, 'avatar_path' => $avatarPath]);
        $this->get(route('media.show', $media))->assertOk()->assertHeader('Content-Type', 'image/jpeg');
        $this->get(route('avatar.show', $reader))->assertOk()->assertHeader('Content-Type', 'image/png');
        $this->get(route('posts.show', $post->slug))->assertOk()->assertSee($media->url(), false);

        $post->update(['status' => 'draft']);
        $this->get(route('media.show', $media))->assertNotFound();
        $this->actingAs($admin)->get(route('media.show', $media))->assertOk();
    }

    public function test_taxonomies_create_update_and_delete_safely(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->post(route('admin.taxonomies.store', 'categories'), ['name' => 'Học tập', 'slug' => 'hoc-tap'])->assertSessionHasNoErrors();
        $category = Category::firstOrFail();
        $this->patch(route('admin.taxonomies.update', ['categories', $category->id]), ['name' => 'Góc học tập', 'slug' => 'goc-hoc-tap'])->assertSessionHasNoErrors();
        $post = Post::factory()->create();
        $post->categories()->attach($category);
        $this->delete(route('admin.taxonomies.destroy', ['categories', $category->id]))->assertSessionHasErrors('category');
        $this->post(route('admin.taxonomies.store', 'tags'), ['name' => 'Laravel'])->assertSessionHasNoErrors();
        $tag = Tag::firstOrFail();
        $post->tags()->attach($tag);
        $this->delete(route('admin.taxonomies.destroy', ['tags', $tag->id]))->assertRedirect();
        $this->assertDatabaseCount('post_tag', 0);
        $this->assertDatabaseMissing('tags', ['id' => $tag->id]);
    }

    public function test_locking_reader_cannot_change_role_or_lock_admin(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $reader = User::factory()->create();
        $this->actingAs($admin)->patch(route('admin.users.update', $reader), ['reason' => 'Kiểm duyệt nội dung', 'is_locked' => 1, 'role' => 'admin'])->assertRedirect();
        $this->assertTrue($reader->fresh()->is_locked);
        $this->assertSame('reader', $reader->fresh()->role);
        $this->patch(route('admin.users.update', $admin), ['reason' => 'Kiểm duyệt nội dung', 'is_locked' => 1])->assertForbidden();
    }

    public function test_settings_are_persisted_and_render_escaped(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->patch(route('admin.settings.update'), ['site_name' => 'Tên blog đã sửa', 'site_tagline' => 'Nhịp sống chậm', 'site_description' => 'Mô tả thật', 'about' => '<script>unsafe()</script>', 'contact_email' => 'owner@example.com'])->assertSessionHasNoErrors();
        $this->get(route('about'))->assertOk()->assertSee('Tên blog đã sửa')->assertSee('&lt;script&gt;', false)->assertDontSee('<script>unsafe()</script>', false);
    }

    /** @param array<string,mixed> $overrides @return array<string,mixed> */
    private function postData(int $categoryId, array $overrides = []): array
    {
        return array_replace(['title' => 'Bài viết kiểm thử', 'slug' => 'bai-kiem-thu', 'excerpt' => 'Một câu chuyện nhỏ', 'content' => '<h2>Heading đã lưu</h2><p><strong>Nội dung in đậm</strong> được giữ lại.</p>', 'status' => 'draft', 'categories' => [$categoryId], 'tags' => [], 'seo_title' => 'Tiêu đề SEO riêng', 'meta_description' => 'Mô tả SEO riêng'], $overrides);
    }
}

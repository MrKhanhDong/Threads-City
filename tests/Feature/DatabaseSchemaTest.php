<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Comment;
use App\Models\CommentReport;
use App\Models\Media;
use App\Models\Post;
use App\Models\PostDailyView;
use App\Models\PostRedirect;
use App\Models\Setting;
use App\Models\Tag;
use App\Models\User;
use App\Services\ViewRecorder;
use Database\Seeders\BlogSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DatabaseSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_uses_dedicated_mysql_database_on_laragon(): void
    {
        $this->assertSame('mysql', DB::connection()->getDriverName());
        $this->assertContains(DB::connection()->getDatabaseName(), ['threads_test', 'moc_blog_test']);
        $server = DB::selectOne('SELECT @@port AS port, @@basedir AS basedir');
        $this->assertSame(3307, (int) $server->port);
        $this->assertStringContainsString('laragon', strtolower($server->basedir));
    }

    public function test_all_blog_and_laravel_tables_exist(): void
    {
        foreach (['users', 'password_reset_tokens', 'sessions', 'cache', 'jobs', 'categories', 'tags', 'media', 'posts', 'post_category', 'post_tag', 'post_media', 'comments', 'comment_reports', 'bookmarks', 'post_redirects', 'post_daily_views', 'settings'] as $table) {
            $this->assertTrue(Schema::hasTable($table), $table);
        }
    }

    public function test_public_user_creation_cannot_assign_admin_or_verified_status(): void
    {
        $user = User::create(['name' => 'Độc giả', 'email' => 'new@example.com', 'password' => 'Reader@12345', 'role' => 'admin', 'is_locked' => false, 'email_verified_at' => now(), 'google_id' => 'untrusted']);
        $user->refresh();
        $this->assertSame('reader', $user->role);
        $this->assertFalse($user->isAdmin());
        $this->assertNull($user->email_verified_at);
        $this->assertNull($user->google_id);
        $this->assertTrue(Hash::check('Reader@12345', $user->password));
    }

    public function test_post_has_multiple_categories_and_tags_in_both_directions(): void
    {
        $post = Post::factory()->create();
        $categories = Category::factory()->count(2)->create();
        $tags = Tag::factory()->count(3)->create();
        $post->categories()->sync($categories->modelKeys());
        $post->tags()->sync($tags->modelKeys());

        $this->assertCount(2, $post->fresh()->categories);
        $this->assertCount(3, $post->fresh()->tags);
        $this->assertSame($post->id, $categories->first()->posts()->first()->id);
        $this->assertSame($post->id, $tags->first()->posts()->first()->id);
        $this->assertDatabaseCount('post_category', 2);
        $this->assertDatabaseCount('post_tag', 3);
    }

    public function test_soft_delete_keeps_relations_and_restore_recovers_post(): void
    {
        $post = Post::factory()->published()->create();
        $category = Category::factory()->create();
        $comment = Comment::factory()->approved()->create(['post_id' => $post->id]);
        $post->categories()->attach($category);
        $post->delete();

        $this->assertSoftDeleted($post);
        $this->assertFalse(Post::published()->whereKey($post->id)->exists());
        $this->assertDatabaseHas('post_category', ['post_id' => $post->id, 'category_id' => $category->id]);
        $this->assertDatabaseHas('comments', ['id' => $comment->id]);

        $post->restore();
        $this->assertTrue(Post::published()->whereKey($post->id)->exists());
        $this->assertCount(1, $post->fresh()->categories);
    }

    public function test_drafts_and_future_scheduled_posts_are_not_public(): void
    {
        $draft = Post::factory()->create();
        $scheduled = Post::factory()->scheduled()->create();
        $futurePublished = Post::factory()->published()->create(['published_at' => now()->addHour()]);
        $published = Post::factory()->published()->create();
        $due = Post::factory()->scheduled()->create(['published_at' => now()->subMinute()]);

        $ids = Post::published()->pluck('id')->all();
        $this->assertContains($published->id, $ids);
        $this->assertContains($due->id, $ids);
        $this->assertNotContains($draft->id, $ids);
        $this->assertNotContains($scheduled->id, $ids);
        $this->assertNotContains($futurePublished->id, $ids);
    }

    public function test_post_slug_stays_reserved_while_post_is_in_trash(): void
    {
        $post = Post::factory()->create(['slug' => 'giu-duong-dan']);
        $post->delete();
        $this->expectException(QueryException::class);
        Post::factory()->create(['slug' => 'giu-duong-dan']);
    }

    public function test_referenced_category_cannot_be_deleted_even_for_trashed_post(): void
    {
        $post = Post::factory()->create();
        $category = Category::factory()->create();
        $post->categories()->attach($category);
        $post->delete();

        $this->expectException(QueryException::class);
        $category->delete();
    }

    public function test_post_with_unknown_author_is_rejected_by_foreign_key(): void
    {
        $this->expectException(QueryException::class);
        Post::factory()->create(['user_id' => 99999999]);
    }

    public function test_author_with_posts_cannot_be_deleted(): void
    {
        $post = Post::factory()->create();
        $this->expectException(QueryException::class);
        $post->author->delete();
    }

    public function test_cover_inline_and_og_media_relationships(): void
    {
        $media = Media::factory()->create();
        $post = Post::factory()->create(['cover_media_id' => $media->id, 'og_media_id' => $media->id]);
        $post->media()->attach($media);

        $this->assertSame($media->id, $post->cover->id);
        $this->assertSame($media->id, $post->ogImage->id);
        $this->assertSame($media->id, $post->media()->first()->id);
        $this->assertSame($post->id, $media->posts()->first()->id);
    }

    public function test_inline_media_in_use_cannot_be_deleted(): void
    {
        $media = Media::factory()->create();
        $post = Post::factory()->create();
        $post->media()->attach($media);
        $this->expectException(QueryException::class);
        $media->delete();
    }

    public function test_comment_reply_and_report_relationships(): void
    {
        $post = Post::factory()->published()->create();
        $parent = Comment::factory()->approved()->create(['post_id' => $post->id]);
        $reply = Comment::factory()->create(['post_id' => $post->id, 'parent_id' => $parent->id]);
        $report = CommentReport::factory()->create(['comment_id' => $parent->id]);

        $this->assertSame($parent->id, $reply->parent->id);
        $this->assertSame($reply->id, $parent->replies()->first()->id);
        $this->assertSame($post->id, $reply->post->id);
        $this->assertSame($parent->id, $report->comment->id);
        $this->assertSame('approved', $reply->status);
    }

    public function test_deleting_parent_comment_does_not_orphan_reply_foreign_key(): void
    {
        $parent = Comment::factory()->approved()->create();
        $reply = Comment::factory()->create(['post_id' => $parent->post_id, 'parent_id' => $parent->id]);
        $parent->delete();
        $this->assertNull($reply->fresh()->parent_id);
        $this->assertDatabaseHas('comments', ['id' => $reply->id]);
    }

    public function test_user_cannot_report_the_same_comment_twice(): void
    {
        $report = CommentReport::factory()->create();
        $this->expectException(QueryException::class);
        CommentReport::factory()->create(['comment_id' => $report->comment_id, 'user_id' => $report->user_id]);
    }

    public function test_bookmark_is_private_to_its_owner_and_has_unique_pair(): void
    {
        $post = Post::factory()->published()->create();
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $owner->bookmarks()->syncWithoutDetaching([$post->id => ['created_at' => now()]]);

        $this->assertSame($post->id, $owner->bookmarks()->first()->id);
        $this->assertSame(0, $other->bookmarks()->count());
        $this->expectException(QueryException::class);
        $owner->bookmarks()->attach($post->id);
    }

    public function test_daily_views_have_one_row_per_post_per_day(): void
    {
        $post = Post::factory()->published()->create();
        $date = now()->startOfDay();
        $daily = PostDailyView::factory()->create(['post_id' => $post->id, 'view_date' => $date]);
        $this->assertSame($date->toDateString(), $daily->fresh()->view_date->toDateString());

        $this->expectException(QueryException::class);
        PostDailyView::factory()->create(['post_id' => $post->id, 'view_date' => $date]);
    }

    public function test_view_recorder_counts_once_per_session_and_again_next_day(): void
    {
        $post = Post::factory()->published()->create();
        $request = Request::create('/');
        $request->setLaravelSession($this->app['session']->driver());
        $request->setUserResolver(fn (): null => null);
        $recorder = app(ViewRecorder::class);
        $recorder->record($request, $post);
        $recorder->record($request, $post);

        $this->assertSame(1, $post->fresh()->views_count);
        $this->assertDatabaseHas('post_daily_views', ['post_id' => $post->id, 'views' => 1]);
        $this->travel(1)->days();
        $recorder->record($request, $post);

        $this->assertSame(2, $post->fresh()->views_count);
        $this->assertDatabaseCount('post_daily_views', 2);
    }

    public function test_admin_views_do_not_change_reader_statistics(): void
    {
        $post = Post::factory()->published()->create();
        $admin = User::factory()->create(['role' => 'admin']);
        $request = Request::create('/');
        $request->setLaravelSession($this->app['session']->driver());
        $request->setUserResolver(fn (): User => $admin);
        app(ViewRecorder::class)->record($request, $post);

        $this->assertSame(0, $post->fresh()->views_count);
        $this->assertDatabaseCount('post_daily_views', 0);
    }

    public function test_permanent_post_delete_cascades_dependencies_but_keeps_library_media(): void
    {
        $post = Post::factory()->published()->create();
        $media = Media::factory()->create();
        $category = Category::factory()->create();
        $tag = Tag::factory()->create();
        $reader = User::factory()->create();
        $post->categories()->attach($category);
        $post->tags()->attach($tag);
        $post->media()->attach($media);
        $reader->bookmarks()->attach($post->id);
        $parent = Comment::factory()->create(['post_id' => $post->id]);
        Comment::factory()->create(['post_id' => $post->id, 'parent_id' => $parent->id]);
        CommentReport::factory()->create(['comment_id' => $parent->id]);
        PostDailyView::factory()->create(['post_id' => $post->id]);
        PostRedirect::factory()->create(['post_id' => $post->id]);

        $post->delete();
        $post->forceDelete();

        foreach (['posts', 'post_category', 'post_tag', 'post_media', 'comments', 'comment_reports', 'bookmarks', 'post_daily_views', 'post_redirects'] as $table) {
            $this->assertDatabaseCount($table, 0);
        }
        $this->assertDatabaseHas('media', ['id' => $media->id]);
        $this->assertDatabaseHas('categories', ['id' => $category->id]);
        $this->assertDatabaseHas('tags', ['id' => $tag->id]);
        $this->assertDatabaseHas('users', ['id' => $reader->id]);
    }

    public function test_utf8mb4_keeps_vietnamese_emoji_and_rich_text(): void
    {
        $content = '<h2>Góc viết tiếng Việt 🌿</h2><p><strong>Xin chào</strong> — một ngày bình yên ☕</p>';
        $post = Post::factory()->create(['title' => 'Nhật ký — hành trình 🌿', 'content' => $content]);
        $this->assertSame($content, $post->fresh()->content);
        $this->assertSame('Nhật ký — hành trình 🌿', $post->fresh()->title);
    }

    public function test_demo_seed_is_repeatable_without_overwriting_user_changes(): void
    {
        $this->seed(BlogSeeder::class);
        $post = Post::where('slug', 'mot-goc-viet-mot-nhip-song-cham')->firstOrFail();
        $post->update(['title' => 'Tiêu đề do tác giả đã sửa']);
        Setting::where('key', 'site_name')->update(['value' => 'Tên đã sửa']);
        $admin = User::where('email', 'admin@blog.local')->firstOrFail();
        $admin->update(['password' => 'Changed@54321']);
        $this->seed(BlogSeeder::class);

        $this->assertDatabaseCount('users', 2);
        $this->assertDatabaseCount('posts', 5);
        $this->assertDatabaseCount('categories', 3);
        $this->assertDatabaseCount('tags', 7);
        $this->assertDatabaseCount('comments', 3);
        $this->assertDatabaseCount('post_daily_views', 0);
        $this->assertSame('Tiêu đề do tác giả đã sửa', $post->fresh()->title);
        $this->assertSame('Tên đã sửa', Setting::findOrFail('site_name')->value);
        $this->assertTrue(Hash::check('Changed@54321', $admin->fresh()->password));
        $this->assertSame(4, Post::published()->count());
    }
}

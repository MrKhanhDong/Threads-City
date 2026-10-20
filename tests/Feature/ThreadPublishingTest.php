<?php

namespace Tests\Feature;

use App\Models\Media;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ThreadPublishingTest extends TestCase
{
    use RefreshDatabase;

    public function test_rich_posts_preserve_headings_lists_links_and_editable_html(): void
    {
        $owner = User::factory()->create(['name' => 'Nguyễn Minh Anh', 'username' => 'minh_anh']);
        $content = '<h2>Góc học tập</h2><p><strong>In đậm</strong> và <em>in nghiêng</em> #Laravel</p><ul><li>Mục đầu</li><li>Mục sau</li></ul><blockquote>Một ghi chú</blockquote><p><a href="https://example.com/">Tham khảo</a></p>';
        $response = $this->actingAs($owner)->postJson(route('threads.store'), ['content' => $content, 'content_format' => 'html'])->assertCreated();
        $post = Post::findOrFail($response->json('id'));
        $html = $response->json('html');
        foreach (['<h2>Góc học tập</h2>', '<strong>In đậm</strong>', '<em>in nghiêng</em>', '<ul><li>Mục đầu</li>', '<blockquote>Một ghi chú</blockquote>', 'class="stream-hashtag"', 'rel="nofollow noopener noreferrer"'] as $format) {
            $this->assertStringContainsString($format, $html);
        }
        preg_match('/data-edit-thread="([^"]+)"/', $html, $matches);
        $edit = json_decode(html_entity_decode($matches[1], ENT_QUOTES | ENT_HTML5, 'UTF-8'), true, flags: JSON_THROW_ON_ERROR);
        $this->assertStringContainsString('<h2>Góc học tập</h2>', $edit['content']);
        $this->assertStringContainsString('<strong>In đậm</strong>', $edit['content']);
        $this->assertStringNotContainsString('<h2>', $post->title);
        $this->assertDatabaseHas('tags', ['slug' => 'laravel']);
        $updated = $this->patchJson(route('threads.update', $post), ['content' => '<h3>Tiêu đề đã sửa</h3><p><strong>Vẫn in đậm</strong></p>', 'content_format' => 'html'])->assertOk();
        $this->assertStringContainsString('<h3>Tiêu đề đã sửa</h3>', $updated->json('html'));
        $this->get(route('threads.show', $post))->assertOk()->assertSee('<strong>Vẫn in đậm</strong>', false);

        if (getenv('THREADS_EDITOR_PREVIEW')) {
            $home = $this->get(route('home'))->assertOk();
            file_put_contents(storage_path('framework/testing/thread-editor-preview.html'), $home->getContent());
        }
    }

    public function test_rich_content_is_sanitized_and_length_counts_visible_unicode_text(): void
    {
        $this->actingAs(User::factory()->create());
        $this->postJson(route('threads.store'), ['content' => '<p><strong>'.str_repeat('🌿', 500).'</strong></p>', 'content_format' => 'html'])->assertCreated();
        $this->postJson(route('threads.store'), ['content' => '<h2>'.str_repeat('đ', 501).'</h2>', 'content_format' => 'html'])
            ->assertUnprocessable()->assertJsonValidationErrors('content');
        foreach (['<p><br></p>', '<p>&nbsp; </p>', '<img src="https://example.com/a.jpg">', '<script>alert(1)</script>'] as $content) {
            $this->postJson(route('threads.store'), ['content' => $content, 'content_format' => 'html'])->assertUnprocessable()->assertJsonValidationErrors('content');
        }
        $response = $this->postJson(route('threads.store'), ['content_format' => 'html', 'content' => '<h2 onclick="alert(1)">An toàn</h2><p style="position:fixed;text-align:center">Đoạn văn <a href="javascript:alert(1)">liên kết</a></p><script>alert(2)</script><img src=x onerror="alert(3)"><iframe src="https://example.com"></iframe>'])->assertCreated();
        $stored = Post::findOrFail($response->json('id'))->content;
        foreach (['onclick', 'onerror', 'javascript:', '<script', '<iframe', '<img', 'position:'] as $unsafe) {
            $this->assertStringNotContainsString($unsafe, $stored);
        }
        $this->assertStringContainsString('<h2>An toàn</h2>', $stored);
        $this->assertStringContainsString('text-align:center', str_replace(' ', '', $stored));
        $this->assertDatabaseCount('posts', 2);
    }

    public function test_rich_chains_validate_and_preserve_each_continuation_format(): void
    {
        $this->actingAs(User::factory()->create());
        foreach (['<p><br></p>', '<p>'.str_repeat('x', 501).'</p>'] as $continuation) {
            $this->postJson(route('threads.store'), ['content' => '<h2>Bài đầu</h2>', 'content_format' => 'html', 'continuations' => [$continuation]])
                ->assertUnprocessable()->assertJsonValidationErrors('continuations.0');
        }
        $this->assertDatabaseCount('posts', 0);
        $response = $this->postJson(route('threads.store'), ['content' => '<h2>Bài đầu</h2>', 'content_format' => 'html', 'continuations' => ['<h3>Bài tiếp</h3><p><em>Nội dung</em></p>']])->assertCreated();
        $reply = Post::where('parent_id', $response->json('id'))->sole();
        $this->assertStringContainsString('<h3>Bài tiếp</h3>', $reply->content);
        $this->get(route('threads.show', $response->json('id')))->assertOk()->assertSee('<em>Nội dung</em>', false);
    }

    public function test_every_signed_in_reader_can_publish_without_admin_or_verified_email(): void
    {
        $reader = User::factory()->unverified()->create();
        $this->actingAs($reader)->postJson(route('threads.store'), ['content' => 'Xin chào #Laravel', 'role' => 'admin', 'status' => 'draft', 'type' => 'reply'])->assertCreated()->assertJsonPath('success', true)->assertJsonStructure(['html', 'id', 'url']);
        $post = Post::sole();
        $this->assertSame($reader->id, $post->user_id);
        $this->assertSame('published', $post->status);
        $this->assertSame('post', $post->type);
        $this->assertNull($post->parent_id);
        $this->assertSame('reader', $reader->fresh()->role);
        $this->assertSame('Laravel', $post->tags()->sole()->name);
        $this->get(route('home'))->assertOk()->assertSee('Xin chào')->assertSee('data-compose="new"', false);
    }

    public function test_guests_and_locked_accounts_cannot_publish(): void
    {
        $this->postJson(route('threads.store'), ['content' => 'Guest post'])->assertUnauthorized();
        $user = User::factory()->create(['is_locked' => true]);
        $this->actingAs($user)->postJson(route('threads.store'), ['content' => 'Locked post'])->assertForbidden();
        $this->assertDatabaseCount('posts', 0);
    }

    public function test_links_hashtags_and_line_breaks_render_safely(): void
    {
        $this->actingAs(User::factory()->create());
        $response = $this->postJson(route('threads.store'), ['content' => "Đọc https://example.com/?a=1&b=2\n#Học <script>alert(1)</script>"])->assertCreated();
        $html = $response->json('html');
        $this->assertStringContainsString('href="https://example.com/?a=1&amp;b=2"', $html);
        $this->assertStringContainsString('rel="nofollow noopener noreferrer"', $html);
        $this->assertStringContainsString('class="stream-hashtag"', $html);
        $this->assertStringContainsString('<br>', $html);
        $this->assertStringNotContainsString('<script>alert(1)</script>', $html);
    }

    public function test_post_length_empty_content_and_image_count_are_validated(): void
    {
        $this->actingAs(User::factory()->create());
        foreach (['', '   ', str_repeat('x', 501)] as $content) {
            $this->postJson(route('threads.store'), compact('content'))->assertUnprocessable()->assertJsonValidationErrors('content');
        }
        $this->postJson(route('threads.store'), ['content' => str_repeat('đ', 500)])->assertCreated();
        $this->postJson(route('threads.store'), ['content' => 'Text', 'media_ids' => [1, 2, 3, 4, 5]])->assertUnprocessable()->assertJsonValidationErrors('media_ids');
        $this->assertDatabaseCount('posts', 1);
    }

    public function test_upload_preview_and_image_only_post_work_for_the_owner(): void
    {
        Storage::fake('local');
        $owner = User::factory()->create();
        $response = $this->actingAs($owner)->postJson(route('threads.upload-media'), ['image' => UploadedFile::fake()->image('photo.jpg', 600, 400)])->assertCreated();
        $media = Media::findOrFail($response->json('id'));
        $this->get($response->json('url'))->assertOk();
        $this->postJson(route('threads.store'), ['media_ids' => [$media->id]])->assertCreated();
        $this->assertDatabaseHas('post_media', ['post_id' => Post::sole()->id, 'media_id' => $media->id]);
        $this->postJson(route('threads.upload-media'), ['image' => UploadedFile::fake()->create('bad.svg', 2, 'image/svg+xml')])->assertUnprocessable();
    }

    public function test_other_peoples_uploads_cannot_be_previewed_or_attached(): void
    {
        Storage::fake('local');
        $owner = User::factory()->create();
        $upload = $this->actingAs($owner)->postJson(route('threads.upload-media'), ['image' => UploadedFile::fake()->image('private.jpg')])->assertCreated();
        $this->actingAs(User::factory()->create())->get($upload->json('url'))->assertNotFound();
        $this->postJson(route('threads.store'), ['content' => 'Stolen media', 'media_ids' => [$upload->json('id')]])->assertUnprocessable()->assertJsonValidationErrors('media_ids.0');
        $this->assertDatabaseCount('posts', 0);
    }

    public function test_chain_is_published_together_and_invalid_segments_create_nothing(): void
    {
        $this->actingAs(User::factory()->create());
        $this->postJson(route('threads.store'), ['content' => 'Phần 1', 'continuations' => ['Phần 2', '']])->assertUnprocessable();
        $this->assertDatabaseCount('posts', 0);
        $response = $this->postJson(route('threads.store'), ['content' => 'Phần 1', 'continuations' => ['Phần 2', 'Phần 3']])->assertCreated();
        $posts = Post::orderBy('id')->get();
        $this->assertCount(3, $posts);
        $this->assertSame($response->json('id'), $posts[1]->parent_id);
        $this->assertSame($posts[1]->id, $posts[2]->parent_id);
        $this->assertSame(1, $posts[0]->replies_count);
        $this->assertSame('reply', $posts[1]->type);
    }

    public function test_reply_and_quote_update_the_original_and_render_without_double_escaping(): void
    {
        $original = Post::factory()->published()->create();
        $reader = User::factory()->create();
        $this->actingAs($reader)->postJson(route('threads.store'), ['content' => 'Một trả lời', 'parent_id' => $original->id])->assertCreated()->assertJsonPath('parent_replies_count', 1);
        $this->postJson(route('threads.store'), ['content' => 'Một trích dẫn', 'repost_of_id' => $original->id])->assertCreated()->assertJsonPath('original_reposts_count', 1);
        $this->assertSame(1, $original->fresh()->replies_count);
        $this->assertSame(1, $original->fresh()->reposts_count);
        $this->get(route('threads.show', $original))->assertOk()->assertSee('Một trả lời');
        $this->get(route('home'))->assertOk()->assertSee('Một trích dẫn')->assertDontSee('Một trả lời');
    }

    public function test_unpublished_deleted_and_future_posts_cannot_receive_actions_or_replies(): void
    {
        $draft = Post::factory()->create();
        $future = Post::factory()->scheduled()->create();
        $deleted = Post::factory()->published()->create();
        $deleted->delete();
        $this->actingAs(User::factory()->create());
        foreach ([$draft, $future, $deleted] as $post) {
            $this->postJson(route('threads.store'), ['content' => 'Hidden reply', 'parent_id' => $post->id])->assertUnprocessable();
            $this->postJson(route('threads.store'), ['content' => 'Hidden quote', 'repost_of_id' => $post->id])->assertUnprocessable();
            foreach (['threads.like', 'threads.bookmark', 'threads.repost'] as $route) {
                $this->postJson(route($route, $post))->assertNotFound();
            }
        }
        $this->assertDatabaseCount('post_likes', 0);
        $this->assertDatabaseCount('bookmarks', 0);
    }

    public function test_owner_can_edit_and_delete_but_other_readers_cannot(): void
    {
        $owner = User::factory()->create();
        $created = $this->actingAs($owner)->postJson(route('threads.store'), ['content' => 'Bản đầu'])->assertCreated();
        $post = Post::findOrFail($created->json('id'));
        $this->actingAs(User::factory()->create())->patchJson(route('threads.update', $post), ['content' => 'Overwrite'])->assertForbidden();
        $this->deleteJson(route('threads.destroy', $post))->assertForbidden();
        $this->actingAs($owner)->patchJson(route('threads.update', $post), ['content' => 'Đã sửa #Học'])->assertOk();
        $this->assertSame('Đã sửa #Học', $post->fresh()->content);
        $this->deleteJson(route('threads.destroy', $post))->assertOk();
        $this->assertSoftDeleted($post);
    }

    public function test_like_bookmark_and_repost_can_be_toggled_without_duplicate_rows_or_negative_counts(): void
    {
        $post = Post::factory()->published()->create();
        $this->actingAs(User::factory()->create());
        $this->postJson(route('threads.like', $post))->assertOk()->assertJsonPath('likes_count', 1)->assertJsonPath('liked', true);
        $this->postJson(route('threads.like', $post))->assertOk()->assertJsonPath('likes_count', 0)->assertJsonPath('liked', false);
        $this->postJson(route('threads.bookmark', $post))->assertOk()->assertJsonPath('bookmarked', true);
        $this->postJson(route('threads.bookmark', $post))->assertOk()->assertJsonPath('bookmarked', false);
        $this->postJson(route('threads.repost', $post))->assertOk()->assertJsonPath('reposted', true)->assertJsonPath('reposts_count', 1);
        $this->postJson(route('threads.repost', $post))->assertOk()->assertJsonPath('reposted', false)->assertJsonPath('reposts_count', 0);
        $this->assertDatabaseCount('post_likes', 0);
        $this->assertDatabaseCount('bookmarks', 0);
        $this->assertSame(0, $post->fresh()->likes_count);
    }

    public function test_following_feed_and_profile_use_real_published_posts(): void
    {
        $reader = User::factory()->create();
        $author = User::factory()->create();
        $followed = Post::factory()->published()->create(['user_id' => $author->id, 'content' => 'Followed content']);
        Post::factory()->published()->create(['content' => 'Unrelated content']);
        $ownPost = Post::factory()->published()->create(['user_id' => $reader->id, 'content' => 'My own original content']);
        Post::factory()->published()->create(['user_id' => $reader->id, 'type' => 'repost', 'repost_of_id' => $followed->id, 'content' => 'My own repost content']);
        Post::factory()->published()->create(['user_id' => $reader->id, 'type' => 'quote', 'repost_of_id' => $followed->id, 'content' => 'My own quote content']);
        $this->actingAs($reader)->postJson(route('users.follow', $author))->assertOk()->assertJsonPath('following', true);
        $this->get(route('home', ['tab' => 'following']))->assertOk()->assertSee('Followed content')->assertDontSee('Unrelated content')
            ->assertViewHas('threads', fn ($threads): bool => $threads->pluck('id')->all() === [$followed->id])
            ->assertSee('data-stream-primary="following"', false);
        $feed = $this->getJson(route('home', ['tab' => 'following']))->assertOk()->json('html');
        $this->assertStringContainsString('Followed content', $feed);
        $this->assertStringNotContainsString('My own original content', $feed);
        $this->assertStringNotContainsString('My own repost content', $feed);
        $this->assertStringNotContainsString('My own quote content', $feed);
        $this->get(route('home'))->assertOk()->assertSee('My own original content')->assertSee('data-stream-primary="for-you"', false);
        $this->get(route('profile.show', $reader->id))->assertOk()->assertSee($ownPost->title);
        $this->get(route('profile.show', $author->id))->assertOk()->assertSee($followed->title);
        $this->postJson(route('users.follow', $reader))->assertUnprocessable();
        $this->postJson(route('users.follow', $author))->assertOk()->assertJsonPath('following', false);
        $this->get(route('home', ['tab' => 'following']))->assertOk()->assertViewHas('threads', fn ($threads): bool => $threads->isEmpty());
    }

    public function test_published_text_and_author_names_are_safe_in_composer_attributes_and_html(): void
    {
        $reader = User::factory()->create(['name' => '<img src=x onerror=alert(1)>']);
        $response = $this->actingAs($reader)->postJson(route('threads.store'), ['content' => '<script>alert(2)</script> & "hello"'])->assertCreated();
        $this->assertStringNotContainsString('<script>alert(2)</script>', $response->json('html'));
        $this->assertStringNotContainsString('<img src=x onerror=alert(1)>', $response->json('html'));
        $this->get(route('threads.show', $response->json('id')))->assertOk()->assertDontSee('<script>alert(2)</script>', false);
    }
}

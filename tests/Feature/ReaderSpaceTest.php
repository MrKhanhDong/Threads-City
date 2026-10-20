<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\Post;
use App\Models\PostDailyView;
use App\Models\User;
use App\Services\CommentThreads;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReaderSpaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_personal_feeds_require_sign_in_and_sidebar_has_working_destinations(): void
    {
        foreach (['activity.index', 'liked.index', 'insights.index', 'archives.index'] as $route) {
            $this->get(route($route))->assertRedirect(route('login'));
        }
        $this->get(route('home'))->assertOk()->assertSee('Hoạt động')->assertSee('Thông tin chi tiết')->assertSee('Bài viết mới')->assertSee('Bảng feed khác')->assertSee('data-feed-visibility="liked"', false)
            ->assertDontSee('Tin nhắn')->assertDontSee('Bài viết tự hủy')->assertDontSee('GÓC VIẾT')->assertDontSee('Thread mới');
    }

    public function test_liked_feed_is_private_and_contains_only_current_public_likes(): void
    {
        $viewer = User::factory()->create();
        $liked = Post::factory()->published()->create(['title' => 'A liked story']);
        $draft = Post::factory()->create();
        $archived = Post::factory()->published()->create(['archived_at' => now()]);
        $unliked = Post::factory()->published()->create();
        $viewer->likedPosts()->attach([$liked->id, $draft->id, $archived->id], ['created_at' => now()]);
        $this->actingAs($viewer)->get(route('liked.index'))->assertOk()->assertViewHas('threads', fn ($threads): bool => $threads->total() === 1 && $threads->first()->id === $liked->id)->assertSee('A liked story');
        $this->actingAs(User::factory()->create())->get(route('liked.index'))->assertOk()->assertViewHas('threads', fn ($threads): bool => $threads->isEmpty());
        $this->actingAs($viewer)->postJson(route('threads.like', $liked))->assertOk()->assertJsonPath('liked', false);
        $this->get(route('liked.index'))->assertViewHas('threads', fn ($threads): bool => $threads->isEmpty());
    }

    public function test_insights_use_the_signed_in_users_actual_public_interactions(): void
    {
        $viewer = User::factory()->create();
        $actor = User::factory()->create();
        $post = Post::factory()->published()->create(['user_id' => $viewer->id, 'views_count' => 14, 'likes_count' => 999]);
        PostDailyView::factory()->create(['post_id' => $post->id, 'view_date' => today(), 'views' => 14]);
        $post->likers()->attach($actor, ['created_at' => now()]);
        Post::factory()->published()->create(['parent_id' => $post->id, 'type' => 'reply']);
        Post::factory()->create(['parent_id' => $post->id, 'type' => 'reply']);
        Post::factory()->published()->create(['repost_of_id' => $post->id, 'type' => 'quote']);
        Post::factory()->create(['user_id' => $viewer->id, 'views_count' => 100]);
        Post::factory()->published()->create(['user_id' => $viewer->id, 'archived_at' => now(), 'views_count' => 200]);
        Post::factory()->published()->create(['views_count' => 300]);
        $actor->following()->attach($viewer, ['created_at' => now()]);
        $this->actingAs($viewer)->get(route('insights.index'))->assertOk()->assertViewHas('insights', fn (array $insights): bool => $insights['posts'] === 1 && $insights['metrics']['views']['total'] === 14 && $insights['metrics']['likes']['total'] === 1 && $insights['breakdown']['replies'] === 1 && $insights['breakdown']['reposts'] === 1 && $insights['followers'] === 1);
        $this->actingAs($actor)->get(route('insights.index'))->assertViewHas('insights', fn (array $insights): bool => $insights['posts'] === 0 && $insights['metrics']['views']['total'] === 0);
    }

    public function test_activity_combines_real_likes_replies_follows_reposts_and_blog_comments(): void
    {
        $viewer = User::factory()->create();
        $actor = User::factory()->create(['name' => 'Người tương tác']);
        $post = Post::factory()->published()->create(['user_id' => $viewer->id]);
        $post->likers()->attach($actor, ['created_at' => now()->subMinutes(5)]);
        $actor->following()->attach($viewer, ['created_at' => now()]);
        Post::factory()->published()->create(['user_id' => $actor->id, 'parent_id' => $post->id, 'type' => 'reply', 'content' => 'Một lời trả lời', 'created_at' => now()->subMinutes(2)]);
        Post::factory()->published()->create(['user_id' => $actor->id, 'repost_of_id' => $post->id, 'type' => 'repost', 'created_at' => now()->subMinutes(2)]);
        Comment::factory()->create(['user_id' => $actor->id, 'post_id' => $post->id, 'body' => 'Một bình luận thật', 'status' => 'approved', 'created_at' => now()->subMinutes(2)]);
        $response = $this->actingAs($viewer)->get(route('activity.index'))->assertOk()->assertSee('Người tương tác')->assertSee('Một bình luận thật')->assertViewHas('activities', fn ($activities): bool => $activities->total() === 5 && $activities->first()->kind === 'follows');
        $this->get(route('activity.index', ['type' => 'replies']))->assertOk()->assertViewHas('activities', fn ($activities): bool => $activities->total() === 2 && $activities->every(fn ($event): bool => $event->kind === 'replies'));
        $this->get(route('activity.index', ['type' => 'likes']))->assertViewHas('activities', fn ($activities): bool => $activities->total() === 1);
        $this->get(route('activity.index', ['type' => 'invalid']))->assertSessionHasErrors('type');
    }

    public function test_activity_excludes_other_peoples_events_and_hidden_content(): void
    {
        $viewer = User::factory()->create();
        $actor = User::factory()->create();
        $post = Post::factory()->published()->create(['user_id' => $viewer->id]);
        $post->likers()->attach($viewer, ['created_at' => now()]);
        Post::factory()->published()->create(['user_id' => $viewer->id, 'parent_id' => $post->id, 'type' => 'reply']);
        $hiddenPosts = [Post::factory()->create(['user_id' => $viewer->id]), Post::factory()->scheduled()->create(['user_id' => $viewer->id]), Post::factory()->published()->create(['user_id' => $viewer->id, 'archived_at' => now()]), Post::factory()->published()->create()];
        foreach ($hiddenPosts as $hidden) {
            $hidden->likers()->attach($actor, ['created_at' => now()]);
            Comment::factory()->create(['post_id' => $hidden->id, 'user_id' => $actor->id, 'status' => 'approved']);
        }
        $parent = Comment::factory()->create(['post_id' => $post->id, 'status' => 'hidden']);
        Comment::factory()->create(['post_id' => $post->id, 'parent_id' => $parent->id, 'user_id' => $actor->id, 'status' => 'approved']);
        $this->actingAs($viewer)->get(route('activity.index'))->assertOk()->assertViewHas('activities', fn ($activities): bool => $activities->isEmpty());
    }

    public function test_activity_pagination_preserves_the_selected_filter(): void
    {
        $viewer = User::factory()->create();
        $actor = User::factory()->create();
        $posts = Post::factory()->published()->count(21)->create(['user_id' => $viewer->id]);
        $actor->likedPosts()->attach($posts->modelKeys(), ['created_at' => now()]);
        $this->actingAs($viewer)->get(route('activity.index', ['type' => 'likes']))->assertOk()->assertViewHas('activities', fn ($activities): bool => $activities->total() === 21 && str_contains($activities->nextPageUrl(), 'type=likes'));
        $this->get(route('activity.index', ['type' => 'likes', 'page' => 2]))->assertViewHas('activities', fn ($activities): bool => $activities->count() === 1);
    }

    public function test_archiving_hides_the_entire_conversation_and_restores_it_without_losing_interactions(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $root = Post::factory()->published()->create(['user_id' => $owner->id, 'title' => 'Private archive needle']);
        $reply = Post::factory()->published()->create(['parent_id' => $root->id, 'type' => 'reply']);
        $grandchild = Post::factory()->published()->create(['parent_id' => $reply->id, 'type' => 'reply']);
        $root->likers()->attach($other, ['created_at' => now()]);
        $other->bookmarks()->attach($root, ['created_at' => now()]);
        $comment = Comment::factory()->create(['post_id' => $root->id, 'status' => 'approved']);
        $this->actingAs($owner)->post(route('threads.archive', $root))->assertRedirect(route('archives.index'));
        foreach ([$root, $reply, $grandchild] as $post) {
            $this->assertSame($root->id, $post->fresh()->archive_root_id);
            $this->get(route('threads.show', $post))->assertNotFound();
        }
        $this->assertNull(app(CommentThreads::class)->root($comment));
        $this->get(route('home'))->assertDontSee('Private archive needle');
        $this->get(route('search.index', ['q' => 'Private archive needle']))->assertViewHas('threads', fn ($threads): bool => $threads->isEmpty());
        $this->get(route('posts.show', $root->slug))->assertNotFound();
        $this->get(route('archives.index'))->assertOk()->assertSee('Private archive needle')->assertViewHas('threads', fn ($threads): bool => $threads->total() === 1);
        $this->actingAs($other)->get(route('archives.index'))->assertDontSee('Private archive needle');
        $this->get(route('saved'))->assertViewHas('threads', fn ($threads): bool => $threads->isEmpty());
        $this->actingAs($owner)->post(route('threads.unarchive', $root))->assertRedirect(route('archives.index'));
        foreach ([$root, $reply, $grandchild] as $post) {
            $this->assertNull($post->fresh()->archived_at);
            $this->get(route('threads.show', $post))->assertOk();
        }
        $this->assertSame(1, $root->likers()->count());
        $this->assertSame(1, $root->bookmarkers()->count());
        $this->assertSame($comment->id, app(CommentThreads::class)->root($comment));
    }

    public function test_archive_actions_are_owner_only_and_cannot_target_replies_or_drafts(): void
    {
        $owner = User::factory()->create();
        $root = Post::factory()->published()->create(['user_id' => $owner->id]);
        $reply = Post::factory()->published()->create(['user_id' => $owner->id, 'parent_id' => $root->id, 'type' => 'reply']);
        $draft = Post::factory()->create(['user_id' => $owner->id]);
        $this->post(route('threads.archive', $root))->assertRedirect(route('login'));
        $this->actingAs(User::factory()->create(['role' => 'admin']))->post(route('threads.archive', $root))->assertForbidden();
        $this->post(route('threads.unarchive', $root))->assertForbidden();
        $this->actingAs($owner)->postJson(route('threads.archive', $reply))->assertUnprocessable();
        $this->post(route('threads.archive', $draft))->assertNotFound();
        $this->post(route('threads.unarchive', $root))->assertNotFound();
        $this->post(route('threads.archive', $root))->assertRedirect();
        $this->post(route('threads.archive', $root))->assertNotFound();
        $this->postJson(route('threads.store'), ['content' => 'Late reply', 'parent_id' => $root->id])->assertUnprocessable();
        $this->postJson(route('threads.like', $root))->assertNotFound();
    }

    public function test_restoring_a_conversation_does_not_publish_drafts_or_restore_deleted_posts(): void
    {
        $owner = User::factory()->create();
        $root = Post::factory()->published()->create(['user_id' => $owner->id]);
        $draft = Post::factory()->create(['parent_id' => $root->id, 'type' => 'reply']);
        $deleted = Post::factory()->published()->create(['parent_id' => $root->id, 'type' => 'reply']);
        $deleted->delete();
        $this->actingAs($owner)->post(route('threads.archive', $root))->assertRedirect();
        $this->assertNotNull($deleted->fresh()->archived_at);
        $this->post(route('threads.unarchive', $root))->assertRedirect();
        $this->assertSame('draft', $draft->fresh()->status);
        $this->assertNull($draft->fresh()->archived_at);
        $this->assertTrue($deleted->fresh()->trashed());
        $this->get(route('threads.show', $draft))->assertNotFound();
    }

    public function test_empty_personal_pages_render_without_fabricated_metrics_or_events(): void
    {
        $this->actingAs(User::factory()->create());
        $this->get(route('activity.index'))->assertOk()->assertSee('Chưa có hoạt động');
        $this->get(route('insights.index'))->assertOk()->assertSee('Bắt đầu từ bài viết đầu tiên');
        $this->get(route('liked.index'))->assertOk()->assertSee('Chưa có bài viết đã thích');
        $this->get(route('archives.index'))->assertOk()->assertSee('Chưa có bài viết lưu trữ');
    }
}

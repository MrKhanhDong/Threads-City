<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use App\Notifications\CommentReplied;
use App\Services\CommentThreads;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class CommentThreadsTest extends TestCase
{
    use RefreshDatabase;

    public function test_ten_levels_can_be_replied_to_without_ten_indents_and_only_direct_author_is_notified(): void
    {
        Mail::fake();
        $post = Post::factory()->published()->create();
        $recipient = User::factory()->create();
        $root = Comment::factory()->create(['post_id' => $post->id, 'user_id' => $recipient->id]);
        $parent = $root;
        for ($level = 1; $level <= 10; $level++) {
            $parent = Comment::factory()->create(['post_id' => $post->id, 'parent_id' => $parent->id, 'user_id' => $recipient->id, 'body' => 'Cấp '.$level]);
        }
        $actor = User::factory()->create();
        $response = $this->actingAs($actor)->post(route('comments.store', $post->id), ['parent_id' => $parent->id, 'body' => 'Trả lời cấp mười một']);
        $reply = Comment::where('body', 'Trả lời cấp mười một')->sole();
        $response->assertRedirect(app(CommentThreads::class)->url($reply, $actor->id))->assertSessionHasNoErrors();
        $this->assertDatabaseCount('notifications', 1);
        $this->assertSame($reply->id, $recipient->notifications()->sole()->data['comment_id']);
        $this->assertSame(0, $actor->notifications()->count());
        $html = $this->getJson(route('comments.replies', ['comment' => $root->id, 'focus' => $reply->id]))->assertOk()->json('html');
        $this->assertStringContainsString('value="'.$reply->id.'"', $html);
        $this->assertStringContainsString('Trả lời '.$recipient->name, strip_tags($html));
        $this->assertStringNotContainsString('comment-replies', $html);
        Mail::assertNothingSent();
    }

    public function test_batches_are_lazy_bounded_and_have_no_duplicate_ids(): void
    {
        $root = Comment::factory()->create(['body' => 'Gốc']);
        $ids = [];
        for ($i = 1; $i <= 12; $i++) {
            $ids[] = Comment::factory()->create(['post_id' => $root->post_id, 'parent_id' => $root->id, 'body' => 'Reply riêng '.$i])->id;
        }
        $this->get(route('posts.show', $root->post->slug))->assertOk()->assertDontSee('Reply riêng')->assertSee('Xem trả lời');
        $after = 0;
        foreach ([5, 5, 2] as $index => $size) {
            $data = $this->getJson(route('comments.replies', ['comment' => $root->id, 'after' => $after]))->assertOk()->assertHeader('Cache-Control', 'no-store, private')->json();
            preg_match_all('/class="comment" id="comment-(\d+)"/', $data['html'], $matches);
            $this->assertCount($size, $matches[1]);
            $this->assertSame(array_slice($ids, $index * 5, $size), array_map('intval', $matches[1]));
            $this->assertSame($index < 2, $data['more']);
            $after = $data['next'];
        }
        $this->getJson(route('comments.replies', ['comment' => $root->id, 'after' => -1]))->assertUnprocessable();
    }

    public function test_hidden_ancestors_cross_post_children_and_private_posts_never_leak(): void
    {
        $root = Comment::factory()->create();
        $hidden = Comment::factory()->create(['post_id' => $root->post_id, 'parent_id' => $root->id, 'status' => 'hidden', 'body' => 'Bí mật cha']);
        $child = Comment::factory()->create(['post_id' => $root->post_id, 'parent_id' => $hidden->id, 'body' => 'Bí mật con']);
        Comment::factory()->create(['parent_id' => $root->id, 'body' => 'Sai bài']);
        $recipient = User::factory()->create();
        $recipient->notify(new CommentReplied($child->id));
        $html = $this->getJson(route('comments.replies', $root))->assertOk()->json('html');
        foreach (['Bí mật cha', 'Bí mật con', 'Sai bài'] as $text) {
            $this->assertStringNotContainsString($text, $html);
        }
        $this->actingAs($recipient)->getJson(route('notifications.index'))->assertOk()->assertJsonPath('unread', 0);
        $this->post(route('notifications.open', $recipient->notifications()->sole()->id))->assertNotFound();
        $root->update(['status' => 'hidden']);
        $this->getJson(route('comments.replies', $root))->assertNotFound();
        $root->update(['status' => 'approved']);
        $root->post->update(['status' => 'draft']);
        $this->getJson(route('comments.replies', $root))->assertNotFound();
    }

    public function test_notification_ownership_read_state_and_deep_link_to_second_page(): void
    {
        $recipient = User::factory()->create();
        $root = Comment::factory()->create(['user_id' => $recipient->id]);
        Comment::factory()->count(12)->create(['post_id' => $root->post_id]);
        $reply = Comment::factory()->create(['post_id' => $root->post_id, 'parent_id' => $root->id, 'body' => '<script>xss()</script> Reply']);
        $recipient->notify(new CommentReplied($reply->id));
        $notification = $recipient->notifications()->sole();
        $other = User::factory()->create();
        $this->actingAs($other)->getJson(route('notifications.index'))->assertJsonPath('unread', 0);
        $this->post(route('notifications.open', $notification->id))->assertNotFound();
        $this->postJson(route('notifications.read-all'))->assertOk();
        $this->assertNull($notification->fresh()->read_at);
        $this->actingAs($recipient)->get(route('home'))->assertOk()->assertSee('notification-button', false)->assertSee('1 chưa đọc');
        $data = $this->getJson(route('notifications.index'))->assertOk()->assertJsonPath('unread', 1)->json();
        $this->assertStringContainsString(e($reply->user->name), $data['html']);
        $this->assertStringNotContainsString('<script>', $data['html']);
        $this->post(route('notifications.open', $notification->id))
            ->assertRedirect(route('posts.show', ['slug' => $root->post->slug, 'comments_page' => 2, 'comment' => $reply->id]).'#comment-'.$reply->id);
        $this->assertNotNull($notification->fresh()->read_at);
        $this->get(route('posts.show', ['slug' => $root->post->slug, 'comments_page' => 2, 'comment' => $reply->id]))->assertOk()->assertSee($reply->body)->assertDontSee('<script>xss()</script>', false)->assertSee('Ẩn bớt trả lời');
        $this->get(route('notifications.index'))->assertOk()->assertSee('Đã đọc')->assertSee('noindex,nofollow', false);
    }

    public function test_self_reply_and_root_comment_create_no_notifications_and_mark_all_is_private(): void
    {
        $user = User::factory()->create();
        $root = Comment::factory()->create(['user_id' => $user->id]);
        $this->actingAs($user)->post(route('comments.store', $root->post_id), ['parent_id' => $root->id, 'body' => 'Tự trả lời'])->assertSessionHasNoErrors();
        $this->post(route('comments.store', $root->post_id), ['body' => 'Bình luận gốc mới'])->assertSessionHasNoErrors();
        $this->assertDatabaseCount('notifications', 0);
        $reply = Comment::factory()->create(['post_id' => $root->post_id, 'parent_id' => $root->id]);
        $user->notify(new CommentReplied($reply->id));
        $other = User::factory()->create();
        $other->notify(new CommentReplied($reply->id));
        $this->postJson(route('notifications.read-all'))->assertOk();
        $this->getJson(route('notifications.index', ['summary' => 1]))->assertJsonPath('unread', 0)->assertJsonMissingPath('html');
        $this->assertNotNull($user->notifications()->sole()->read_at);
        $this->assertNull($other->notifications()->sole()->read_at);
    }

    public function test_delete_or_trash_removes_notifications_from_badge_and_list(): void
    {
        $user = User::factory()->create();
        $root = Comment::factory()->create(['user_id' => $user->id]);
        $reply = Comment::factory()->create(['post_id' => $root->post_id, 'parent_id' => $root->id]);
        $user->notify(new CommentReplied($reply->id));
        $this->actingAs($user)->getJson(route('notifications.index'))->assertJsonPath('unread', 1);
        $root->post->delete();
        $this->getJson(route('notifications.index'))->assertJsonPath('unread', 0);
        $root->post()->withTrashed()->first()->restore();
        $this->getJson(route('notifications.index'))->assertJsonPath('unread', 1);
        $reply->delete();
        $this->getJson(route('notifications.index'))->assertJsonPath('unread', 0);
    }

    public function test_deep_reply_retry_preserves_body_and_focus_batch_has_previous_button(): void
    {
        $root = Comment::factory()->create();
        $parent = $root;
        for ($i = 0; $i < 10; $i++) {
            $parent = Comment::factory()->create(['post_id' => $root->post_id, 'parent_id' => $parent->id]);
        }
        $user = User::factory()->create();
        $this->actingAs($user)->withSession(['_old_input' => ['parent_id' => $parent->id, 'body' => 'Nội dung đang viết']])
            ->get(route('posts.show', $root->post->slug))->assertOk()->assertSee('Nội dung đang viết')->assertSee('Xem các trả lời trước')->assertSee('id="reply-'.$parent->id.'"', false);
        $data = $this->getJson(route('comments.replies', ['comment' => $root->id, 'focus' => $parent->id]))->assertOk()->json();
        $this->assertTrue($data['earlier']);
        $this->assertStringContainsString('id="comment-'.$parent->id.'"', $data['html']);
    }

    public function test_only_immediate_parent_author_receives_notice_and_deleted_parent_promotes_children(): void
    {
        $root = Comment::factory()->create();
        $parent = Comment::factory()->create(['post_id' => $root->post_id, 'parent_id' => $root->id]);
        $actor = User::factory()->create();
        $this->actingAs($actor)->post(route('comments.store', $root->post_id), ['parent_id' => $parent->id, 'body' => 'Trả lời trực tiếp'])->assertSessionHasNoErrors();
        $reply = Comment::where('body', 'Trả lời trực tiếp')->sole();
        $this->assertSame(0, $root->user->notifications()->count());
        $this->assertSame(1, $parent->user->notifications()->count());
        $parent->delete();
        $this->assertNull($reply->fresh()->parent_id);
        $this->get(route('posts.show', $root->post->slug))->assertOk()->assertSee('Trả lời trực tiếp');
    }

    public function test_pending_reply_is_visible_only_to_its_owner_and_notifications_require_login(): void
    {
        $root = Comment::factory()->create();
        $pending = Comment::factory()->create(['post_id' => $root->post_id, 'parent_id' => $root->id, 'status' => 'pending', 'body' => 'Nội dung riêng cũ']);
        $this->getJson(route('comments.replies', $root))->assertOk()->assertJsonPath('html', '');
        $this->get(route('notifications.index'))->assertRedirect(route('login'));
        $this->post(route('notifications.read-all'))->assertRedirect(route('login'));
        $html = $this->actingAs($pending->user)->getJson(route('comments.replies', $root))->assertOk()->json('html');
        $this->assertStringContainsString('Nội dung riêng cũ', $html);
        $this->assertStringContainsString('chỉ bạn thấy', $html);
        $this->assertStringNotContainsString('<summary>Trả lời</summary>', $html);
    }
}

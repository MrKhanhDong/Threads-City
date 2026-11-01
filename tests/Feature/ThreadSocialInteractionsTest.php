<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use App\Notifications\ThreadInteractionNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ThreadSocialInteractionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_liking_thread_triggers_notification_for_author(): void
    {
        $author = User::factory()->create();
        $liker = User::factory()->create();
        $thread = Post::factory()->create([
            'user_id' => $author->id,
            'status' => 'published',
            'type' => 'post',
        ]);

        $this->actingAs($liker)
            ->postJson(route('threads.like', $thread->id))
            ->assertOk()
            ->assertJson(['liked' => true]);

        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $author->id,
            'notifiable_type' => User::class,
        ]);

        $notification = $author->notifications()->first();
        $this->assertNotNull($notification);
        $this->assertSame('like', $notification->data['action']);
        $this->assertSame($liker->id, $notification->data['actor_id']);
    }

    public function test_following_triggers_notification_for_target_user(): void
    {
        $targetUser = User::factory()->create();
        $follower = User::factory()->create();

        $this->actingAs($follower)
            ->postJson(route('users.follow', $targetUser->id))
            ->assertOk()
            ->assertJson(['following' => true]);

        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $targetUser->id,
            'notifiable_type' => User::class,
        ]);

        $notification = $targetUser->notifications()->first();
        $this->assertNotNull($notification);
        $this->assertSame('follow', $notification->data['action']);
        $this->assertSame($follower->id, $notification->data['actor_id']);
    }

    public function test_reply_and_mentions_trigger_notifications(): void
    {
        $author = User::factory()->create(['username' => 'author_user']);
        $mentionedUser = User::factory()->create(['username' => 'cool_dev']);
        $replier = User::factory()->create(['username' => 'replier_user']);

        $thread = Post::factory()->create([
            'user_id' => $author->id,
            'status' => 'published',
            'type' => 'post',
        ]);

        $this->actingAs($replier)
            ->postJson(route('threads.store'), [
                'parent_id' => $thread->id,
                'content' => 'Xin chào @cool_dev, đây là câu trả lời cho bài viết!',
            ])
            ->assertOk();

        // 1. Author should receive reply notification
        $authorNotif = $author->notifications()->first();
        $this->assertNotNull($authorNotif);
        $this->assertSame('reply', $authorNotif->data['action']);
        $this->assertSame($replier->id, $authorNotif->data['actor_id']);

        // 2. Mentioned user should receive mention notification
        $mentionNotif = $mentionedUser->notifications()->first();
        $this->assertNotNull($mentionNotif);
        $this->assertSame('mention', $mentionNotif->data['action']);
        $this->assertSame($replier->id, $mentionNotif->data['actor_id']);
    }

    public function test_notifications_unread_count_and_tabs(): void
    {
        $user = User::factory()->create();
        $actor = User::factory()->create(['name' => 'John Doe', 'username' => 'johndoe']);

        $this->actingAs($user)
            ->getJson(route('notifications.unread'))
            ->assertOk()
            ->assertJson(['unread' => 0]);

        $thread = Post::factory()->create(['user_id' => $user->id, 'status' => 'published']);

        // Actor likes user's thread
        $this->actingAs($actor)->postJson(route('threads.like', $thread->id))->assertOk();

        // Unread count should now be 1
        $this->actingAs($user)
            ->getJson(route('notifications.unread'))
            ->assertOk()
            ->assertJson(['unread' => 1]);

        // Notifications page index
        $this->actingAs($user)
            ->get(route('notifications.index', ['tab' => 'likes']))
            ->assertOk()
            ->assertSee('John Doe')
            ->assertSee('đã thích bài viết của bạn');
    }

    public function test_notification_popup_is_private_limited_and_does_not_mark_items_as_read(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        foreach (range(1, 8) as $index) {
            $user->notifications()->create([
                'id' => (string) Str::uuid(),
                'type' => ThreadInteractionNotification::class,
                'data' => [
                    'action' => 'like',
                    'actor_name' => 'Người thích '.$index,
                    'content_snippet' => $index === 1 ? '<script>alert("xss")</script>' : 'Nội dung '.$index,
                    'url' => route('home'),
                ],
                'created_at' => now()->subMinutes($index),
            ]);
        }
        $other->notify(new ThreadInteractionNotification(['action' => 'follow', 'actor_name' => 'Thông báo riêng của người khác', 'url' => route('home')]));

        $this->getJson(route('notifications.index', ['preview' => 1]))->assertUnauthorized();
        $response = $this->actingAs($user)->getJson(route('notifications.index', ['preview' => 1]))
            ->assertOk()->assertJsonPath('unread', 8);
        $html = $response->json('html');
        $this->assertStringContainsString('Người thích 1', $html);
        $this->assertStringContainsString('Người thích 6', $html);
        $this->assertStringNotContainsString('Người thích 7', $html);
        $this->assertStringNotContainsString('Thông báo riêng của người khác', $html);
        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
        $this->assertSame(8, $user->unreadNotifications()->count());
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        $this->get(route('home'))->assertOk()
            ->assertSee('aria-controls="notification-popup"', false)
            ->assertSee('Thông báo: 8 chưa đọc')
            ->assertSee('Xem tất cả hoạt động');
    }

    public function test_notification_summary_and_read_all_keep_popup_and_activity_consistent(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        foreach ([$user, $other] as $recipient) {
            $recipient->notify(new ThreadInteractionNotification(['action' => 'follow', 'actor_name' => 'Người theo dõi mới', 'url' => route('home')]));
        }

        $this->actingAs($user)->getJson(route('notifications.index', ['preview' => 1, 'summary' => 1]))
            ->assertOk()->assertExactJson(['unread' => 1]);
        $this->postJson(route('notifications.read-all'))->assertOk();
        $html = $this->getJson(route('notifications.index', ['preview' => 1]))
            ->assertOk()->assertJsonPath('unread', 0)->json('html');
        $this->assertStringContainsString('Đã đọc', $html);
        $this->assertStringNotContainsString('notification-preview-unread', $html);
        $this->assertSame(1, $other->unreadNotifications()->count());
        $this->getJson(route('notifications.index'))->assertOk()->assertJsonPath('notifications.total', 1);
    }

    public function test_notification_popup_links_open_and_read_only_the_selected_notification(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $user->notify(new ThreadInteractionNotification(['action' => 'reply', 'actor_name' => 'Người trả lời', 'url' => route('home')]));
        $notification = $user->notifications()->sole();

        $this->actingAs($other)->get(route('notifications.open', $notification->id))->assertNotFound();
        $this->assertNull($notification->fresh()->read_at);
        $this->actingAs($user)->get(route('notifications.open', $notification->id))->assertRedirect(route('home'));
        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_notification_popup_has_an_empty_state(): void
    {
        $user = User::factory()->create();
        $html = $this->actingAs($user)->getJson(route('notifications.index', ['preview' => 1]))
            ->assertOk()->assertJsonPath('unread', 0)->json('html');
        $this->assertStringContainsString('Chưa có thông báo nào', $html);
    }

    public function test_mention_suggestions_api(): void
    {
        User::factory()->create(['name' => 'Alice Nguyen', 'username' => 'alicenguyen']);
        User::factory()->create(['name' => 'Bob Tran', 'username' => 'bobtran']);

        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->getJson(route('users.mention-suggestions', ['q' => 'alice']));

        $response->assertOk()
            ->assertJsonFragment(['username' => 'alicenguyen']);
    }

    public function test_followers_and_following_endpoints(): void
    {
        $user = User::factory()->create(['username' => 'target']);
        $friend = User::factory()->create(['username' => 'friend']);

        $user->followers()->attach($friend->id, ['created_at' => now()]);
        $user->following()->attach($friend->id, ['created_at' => now()]);

        $this->getJson(route('users.followers', 'target'))
            ->assertOk()
            ->assertJson(['count' => 1])
            ->assertJsonFragment(['username' => 'friend']);

        $this->getJson(route('users.following', 'target'))
            ->assertOk()
            ->assertJson(['count' => 1])
            ->assertJsonFragment(['username' => 'friend']);
    }

    public function test_creating_thread_with_poll_and_voting_updates_percentages(): void
    {
        $author = User::factory()->create();
        $voter = User::factory()->create();

        $createResponse = $this->actingAs($author)
            ->postJson(route('threads.store'), [
                'content' => 'Bạn có thích đá bóng không?',
                'poll_options' => ['Có', 'Không'],
            ]);

        $createResponse->assertOk()
            ->assertJson(['success' => true]);

        $thread = Post::where('user_id', $author->id)->latest('id')->first();
        $this->assertNotNull($thread);
        $this->assertNotNull($thread->excerpt);

        $pollData = json_decode($thread->excerpt, true);
        $this->assertTrue($pollData['is_poll']);
        $this->assertCount(2, $pollData['options']);

        // Voter casts vote for Option 1 ("Có")
        $voteResponse = $this->actingAs($voter)
            ->postJson(route('threads.vote', $thread->id), [
                'option_id' => 1,
            ]);

        $voteResponse->assertOk()
            ->assertJson(['success' => true])
            ->assertJsonPath('poll.total_votes', 1)
            ->assertJsonPath('poll.options.0.percentage', 100)
            ->assertJsonPath('poll.options.1.percentage', 0);
    }

    public function test_quote_picker_endpoint_returns_saved_liked_and_user_posts(): void
    {
        $user = User::factory()->create();
        $otherAuthor = User::factory()->create();

        $savedThread = Post::factory()->create(['user_id' => $otherAuthor->id, 'status' => 'published', 'published_at' => now()->subMinute(), 'type' => 'post']);
        $likedThread = Post::factory()->create(['user_id' => $otherAuthor->id, 'status' => 'published', 'published_at' => now()->subMinute(), 'type' => 'post']);
        $myThread = Post::factory()->create(['user_id' => $user->id, 'status' => 'published', 'published_at' => now()->subMinute(), 'type' => 'post']);

        $user->bookmarks()->attach($savedThread->id, ['created_at' => now()]);
        $user->likedPosts()->attach($likedThread->id, ['created_at' => now()]);

        // Saved tab
        $this->actingAs($user)
            ->getJson(route('api.quote-picker', ['tab' => 'saved']))
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonFragment(['id' => $savedThread->id]);

        // Liked tab
        $this->actingAs($user)
            ->getJson(route('api.quote-picker', ['tab' => 'liked']))
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonFragment(['id' => $likedThread->id]);

        // Mine tab
        $this->actingAs($user)
            ->getJson(route('api.quote-picker', ['tab' => 'mine']))
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonFragment(['id' => $myThread->id]);
    }

    public function test_user_can_create_thread_with_attached_text_document(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson(route('threads.store'), [
                'content' => 'Xem văn bản đính kèm này nhé #tailieu',
                'attached_document_html' => '<p><b>Nội dung in đậm</b> và <i>in nghiêng</i></p>',
            ]);

        $response->assertOk()
            ->assertJson(['success' => true]);

        $thread = Post::where('user_id', $user->id)->latest('id')->first();
        $this->assertNotNull($thread);
        $this->assertStringContainsString('Nội dung in đậm', $thread->excerpt);

        // Verify thread page renders the attached document
        $showResponse = $this->actingAs($user)->get(route('threads.show', $thread->id));
        $showResponse->assertOk()
            ->assertSee('Văn bản đính kèm')
            ->assertSee('Nội dung in đậm');
    }

    public function test_user_can_create_thread_with_location(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson(route('threads.store'), [
                'content' => 'Check in tại một nơi tuyệt đẹp',
                'location' => 'TP. Hồ Chí Minh',
            ]);

        $response->assertOk()
            ->assertJson(['success' => true]);

        $thread = Post::where('user_id', $user->id)->latest('id')->first();
        $this->assertNotNull($thread);
        $this->assertStringContainsString('TP. Hồ Chí Minh', $thread->excerpt);

        // Verify thread page renders the location pill
        $showResponse = $this->actingAs($user)->get(route('threads.show', $thread->id));
        $showResponse->assertOk()
            ->assertSee('TP. Hồ Chí Minh');
    }
}

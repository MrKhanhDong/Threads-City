<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}

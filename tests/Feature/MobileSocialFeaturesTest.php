<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use App\Notifications\Mentioned;
use App\Services\SendWebPush;
use GuzzleHttp\Psr7\Request as PushRequest;
use GuzzleHttp\Psr7\Response as PushResponse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Minishlink\WebPush\MessageSentReport;
use Minishlink\WebPush\WebPush;
use Tests\TestCase;

class MobileSocialFeaturesTest extends TestCase
{
    use RefreshDatabase;

    public function test_selected_mentions_notify_once_and_keep_identity_after_username_changes(): void
    {
        $author = User::factory()->create(['username' => 'author']);
        $recipient = User::factory()->create(['username' => 'friend']);
        $content = '<p>Hello '.$this->mention($recipient).' '.$this->mention($recipient).'</p>';
        $response = $this->actingAs($author)->postJson(route('threads.store'), ['content' => $content, 'content_format' => 'html'])->assertCreated();
        $post = Post::findOrFail($response->json('id'));
        $this->assertDatabaseCount('content_mentions', 1);
        $this->assertSame(1, $recipient->notifications()->count());

        $this->patchJson(route('threads.update', $post), ['content' => $content, 'content_format' => 'html'])->assertOk();
        $this->patchJson(route('threads.update', $post), ['content' => 'Removed mention'])->assertOk();
        $this->actingAs($recipient)->getJson(route('notifications.index', ['summary' => 1]))->assertJsonPath('unread', 0);
        $this->actingAs($author)->patchJson(route('threads.update', $post), ['content' => $content, 'content_format' => 'html'])->assertOk();
        $this->assertSame(1, $recipient->notifications()->count());

        $recipient->update(['username' => 'renamed']);
        $this->get(route('threads.show', $post))->assertOk()->assertSee('@renamed')->assertSee(route('mentions.profile', $recipient), false);
        $this->get(route('mentions.profile', $recipient))->assertRedirect(route('profile.show', 'renamed'));
        $this->actingAs($recipient)->get(route('notifications.index'))->assertOk()->assertSee('đã nhắc đến bạn');
        $this->post(route('notifications.open', $recipient->notifications()->sole()->id))->assertRedirect(route('threads.show', $post));
        $this->assertNotNull($recipient->notifications()->sole()->read_at);
    }

    public function test_raw_text_self_mentions_and_external_lookalike_links_do_not_notify(): void
    {
        $author = User::factory()->create(['username' => 'author']);
        $recipient = User::factory()->create(['username' => 'friend']);
        $this->actingAs($author)->postJson(route('threads.store'), ['content' => '@friend email@friend.com'])->assertCreated();
        $this->postJson(route('threads.store'), ['content_format' => 'html', 'content' => '<p>'.$this->mention($author).' <a href="https://example.com/people/'.$recipient->id.'">@friend</a></p>'])->assertCreated();
        $this->assertSame(0, $recipient->notifications()->count());
        $this->assertSame(0, $author->notifications()->count());
        $this->assertDatabaseCount('content_mentions', 1);
    }

    public function test_five_other_people_limit_is_atomic_for_posts_and_chains(): void
    {
        $author = User::factory()->create(['username' => 'author']);
        $people = collect(range(1, 6))->map(fn (int $index): User => User::factory()->create(['username' => 'friend'.$index]));
        $content = $people->map(fn (User $person): string => $this->mention($person))->implode(' ');
        $this->actingAs($author)->postJson(route('threads.store'), ['content' => $content, 'content_format' => 'html'])->assertUnprocessable()->assertJsonValidationErrors('content');
        $this->assertDatabaseCount('posts', 0);
        $this->assertDatabaseCount('notifications', 0);
        $this->postJson(route('threads.store'), ['content' => '<p>First</p>', 'content_format' => 'html', 'continuations' => [$content]])->assertUnprocessable();
        $this->assertDatabaseCount('posts', 0);
        $allowed = $people->take(5)->push($author)->map(fn (User $person): string => $this->mention($person))->implode(' ');
        $this->postJson(route('threads.store'), ['content' => $allowed, 'content_format' => 'html'])->assertCreated();
        $this->assertDatabaseCount('notifications', 5);
    }

    public function test_suggestions_and_server_writes_respect_blocks_locks_and_recipient_policy(): void
    {
        $author = User::factory()->create(['username' => 'author']);
        $blocked = User::factory()->create(['username' => 'blocked']);
        $locked = User::factory()->create(['username' => 'locked', 'is_locked' => true]);
        $nobody = User::factory()->create(['username' => 'nobody', 'mention_policy' => 'nobody']);
        $following = User::factory()->create(['username' => 'following', 'mention_policy' => 'following']);
        DB::table('user_safety_blocks')->insert(['blocker_id' => $blocked->id, 'blocked_id' => $author->id, 'created_at' => now()]);
        $this->actingAs($author);
        foreach ([$blocked, $locked, $nobody, $following] as $person) {
            $this->getJson(route('mentions.suggestions', ['q' => $person->username]))->assertOk()->assertJsonCount(0, 'users');
            $this->postJson(route('threads.store'), ['content_format' => 'html', 'content' => $this->mention($person)])->assertUnprocessable();
        }
        $author->following()->attach($following);
        $this->getJson(route('mentions.suggestions', ['q' => 'following']))->assertJsonCount(0, 'users');
        $following->following()->attach($author);
        $this->getJson(route('mentions.suggestions', ['q' => 'following']))->assertJsonCount(1, 'users');
        $this->postJson(route('threads.store'), ['content_format' => 'html', 'content' => $this->mention($following)])->assertCreated();
        $this->patch(route('safety.mentions'), ['mention_policy' => 'invalid'])->assertSessionHasErrors('mention_policy');
        $this->patch(route('safety.mentions'), ['mention_policy' => 'nobody', 'user_id' => $following->id])->assertRedirect();
        $this->assertSame('nobody', $author->fresh()->mention_policy);
        $this->assertSame('following', $following->fresh()->mention_policy);
    }

    public function test_mentions_never_grant_private_access_and_old_notifications_recheck_visibility(): void
    {
        $author = User::factory()->create(['username' => 'private', 'is_private' => true]);
        $recipient = User::factory()->create(['username' => 'reader']);
        $response = $this->actingAs($author)->postJson(route('threads.store'), ['content_format' => 'html', 'content' => $this->mention($recipient)])->assertCreated();
        $this->assertSame(0, $recipient->notifications()->count());
        $this->actingAs($recipient)->get(route('threads.show', $response->json('id')))->assertNotFound();

        $recipient->following()->attach($author);
        $response = $this->actingAs($author)->postJson(route('threads.store'), ['content_format' => 'html', 'content' => $this->mention($recipient)])->assertCreated();
        $notification = $recipient->notifications()->sole();
        $this->actingAs($recipient)->getJson(route('notifications.index', ['summary' => 1]))->assertJsonPath('unread', 1);
        $recipient->following()->detach($author);
        $this->getJson(route('notifications.index', ['summary' => 1]))->assertJsonPath('unread', 0);
        $this->post(route('notifications.open', $notification->id))->assertNotFound();
        $this->assertNull($notification->fresh()->read_at);
    }

    public function test_hidden_ancestors_blocks_and_deleted_posts_hide_mentions_in_all_inbox_views(): void
    {
        $author = User::factory()->create(['username' => 'author']);
        $recipient = User::factory()->create(['username' => 'reader']);
        $root = $this->thread($author);
        $response = $this->actingAs($author)->postJson(route('threads.store'), ['content_format' => 'html', 'content' => $this->mention($recipient), 'parent_id' => $root->id])->assertCreated();
        $notification = $recipient->notifications()->sole();
        $root->delete();
        $this->actingAs($recipient)->getJson(route('notifications.index', ['summary' => 1]))->assertJsonPath('unread', 0);
        $this->get(route('notifications.index'))->assertOk()->assertDontSee('đã nhắc đến bạn');
        $this->post(route('notifications.open', $notification->id))->assertNotFound();
        $root->restore();
        $this->getJson(route('notifications.index', ['summary' => 1]))->assertJsonPath('unread', 1);
        $this->put(route('safety.block', $author))->assertRedirect();
        $this->getJson(route('notifications.index', ['summary' => 1]))->assertJsonPath('unread', 0);
    }

    public function test_comment_mentions_render_safely_and_reply_plus_mention_produces_one_notification(): void
    {
        $author = User::factory()->create(['username' => 'author']);
        $recipient = User::factory()->create(['username' => 'reader']);
        $post = $this->thread($author);
        $root = Comment::factory()->create(['post_id' => $post->id, 'user_id' => $recipient->id]);
        $this->actingAs($author)->post(route('comments.store', $post), ['body' => '@reader hello <script>alert(1)</script>', 'mention_ids' => [$recipient->id], 'parent_id' => $root->id])->assertRedirect()->assertSessionHasNoErrors();
        $comment = Comment::where('parent_id', $root->id)->sole();
        $this->assertSame(1, $recipient->notifications()->count());
        $this->assertSame(Mentioned::class, $recipient->notifications()->sole()->type);
        $this->getJson(route('comments.replies', $root))->assertOk()->assertSee('stream-mention')->assertDontSee('<script>', false);
        $this->patch(route('comments.update', $comment), ['body' => '@reader changed', 'mention_ids' => [$recipient->id]])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame(1, $recipient->notifications()->count());
        $recipient->update(['username' => 'renamed_reader']);
        $this->getJson(route('comments.replies', $root))->assertOk()->assertSee('@renamed_reader');
        $this->patch(route('comments.update', $comment), ['body' => 'Mention removed'])->assertRedirect()->assertSessionHasNoErrors();
        $this->actingAs($recipient)->getJson(route('notifications.index', ['summary' => 1]))->assertJsonPath('unread', 0);
    }

    public function test_private_connection_lists_require_accepted_following_and_hide_blocked_people(): void
    {
        $owner = User::factory()->create(['username' => 'private', 'is_private' => true]);
        $viewer = User::factory()->create(['username' => 'viewer']);
        $other = User::factory()->create(['username' => 'other']);
        $other->following()->attach($owner);
        $url = route('profile.connections', [$owner, 'followers']);
        $this->getJson($url)->assertNotFound();
        $this->actingAs($viewer)->postJson(route('users.follow', $owner))->assertJsonPath('requested', true);
        $this->getJson($url)->assertNotFound();
        $this->actingAs($owner)->post(route('safety.follow-request', $viewer), ['decision' => 'accept'])->assertRedirect();
        $this->actingAs($viewer)->getJson($url)->assertOk()->assertSee($other->name);
        $this->put(route('safety.block', $other))->assertRedirect();
        $this->getJson($url)->assertOk()->assertDontSee($other->name);
        $this->postJson(route('users.follow', $owner))->assertJsonPath('following', false);
        $this->getJson($url)->assertNotFound();
    }

    public function test_connections_paginate_and_pwa_metadata_and_image_dialogs_are_present(): void
    {
        $owner = User::factory()->create(['username' => 'owner']);
        $owner->followers()->attach(User::factory()->count(21)->create()->pluck('id'));
        $this->getJson(route('profile.connections', [$owner, 'followers']))->assertOk()->assertJsonPath('nextPageUrl', route('profile.connections', [$owner, 'followers']).'?page=2');
        $this->get(route('profile.connections', [$owner, 'followers']))->assertOk();
        $this->get(route('home'))->assertOk()->assertSee('app.webmanifest')->assertSee('data-lightbox', false)->assertSee('data-pull-refresh', false);
        $manifest = json_decode(file_get_contents(public_path('app.webmanifest')), true, flags: JSON_THROW_ON_ERROR);
        $this->assertSame('standalone', $manifest['display']);
        foreach ([192, 512] as $size) {
            $dimensions = getimagesize(public_path('pwa-icon-'.$size.'.png'));
            $this->assertSame([$size, $size], array_slice($dimensions, 0, 2));
        }
    }

    public function test_push_subscriptions_reject_arbitrary_endpoints_and_are_bound_to_current_account(): void
    {
        $this->enablePush();
        $owner = User::factory()->create(['username' => 'owner']);
        $other = User::factory()->create(['username' => 'other']);
        $payload = $this->subscription();
        $this->postJson(route('push.store'), $payload)->assertUnauthorized();
        $this->actingAs($owner);
        $this->postJson(route('push.store'), [...$payload, 'account_id' => $other->id])->assertConflict();
        foreach (['https://127.0.0.1/private', 'http://fcm.googleapis.com/send/a', 'https://fcm.googleapis.com.example.org/send/a', 'https://user:pass@fcm.googleapis.com/send/a', 'https://fcm.googleapis.com:8443/send/a'] as $endpoint) {
            $this->postJson(route('push.store'), array_replace($payload, ['endpoint' => $endpoint]))->assertUnprocessable();
        }
        $this->postJson(route('push.store'), $payload)->assertOk();
        $device = DB::table('push_subscriptions')->sole();
        $this->assertNotSame($payload['keys']['auth'], $device->auth_token);
        $this->actingAs($other)->deleteJson(route('push.destroy'), ['endpoint' => $payload['endpoint']])->assertOk();
        $this->assertDatabaseCount('push_subscriptions', 1);
        $this->postJson(route('push.store'), $payload)->assertOk();
        $this->assertDatabaseCount('push_subscriptions', 1);
        $this->assertDatabaseHas('push_subscriptions', ['user_id' => $other->id]);
        $this->assertNotSame($device->browser_token, DB::table('push_subscriptions')->sole()->browser_token);
        $this->post(route('logout'))->assertRedirect();
        $this->assertDatabaseCount('push_subscriptions', 0);
    }

    public function test_push_context_and_delivery_recheck_account_permission_and_expired_endpoints(): void
    {
        $this->enablePush();
        $author = User::factory()->create(['username' => 'author']);
        $recipient = User::factory()->create(['username' => 'reader']);
        $this->actingAs($recipient)->postJson(route('push.store'), $this->subscription())->assertOk();
        $token = DB::table('push_subscriptions')->sole()->browser_token;
        $this->actingAs($author)->postJson(route('threads.store'), ['content_format' => 'html', 'content' => $this->mention($recipient)])->assertCreated();
        $notification = $recipient->notifications()->sole();
        $this->getJson(route('push.context', ['notification' => $notification->id]), ['X-Push-Token' => $token])->assertJsonPath('allowed', false);
        $this->actingAs($recipient)->getJson(route('push.context', ['notification' => $notification->id]), ['X-Push-Token' => $token])->assertJsonPath('allowed', true);
        $report = new MessageSentReport(new PushRequest('POST', $this->subscription()['endpoint']), new PushResponse(410), false);
        $this->mock(WebPush::class)->shouldReceive('sendOneNotification')->once()->withArgs(function ($subscription, string $payload, array $options) use ($token): bool {
            $data = json_decode($payload, true);

            return $data['token'] === $token && ! str_contains($payload, 'reader') && $options['TTL'] === 300;
        })->andReturn($report);
        app()->call([new SendWebPush($recipient->id, $notification->id), 'handle']);
        $this->assertDatabaseCount('push_subscriptions', 0);
        $this->put(route('safety.block', $author))->assertRedirect();
        app()->call([new SendWebPush($recipient->id, $notification->id), 'handle']);
    }

    private function enablePush(): void
    {
        config(['services.webpush.public_key' => 'test-public', 'services.webpush.private_key' => 'test-private', 'services.webpush.subject' => 'mailto:admin@example.com']);
        Bus::fake([SendWebPush::class]);
    }

    public function test_comment_mentions_keep_identity_when_a_username_is_reused_and_reject_ambiguous_selection(): void
    {
        $author = User::factory()->create(['username' => 'author']);
        $original = User::factory()->create(['username' => 'minh-anh']);
        $post = $this->thread($author);
        $this->actingAs($author)->post(route('comments.store', $post), ['body' => '@minh-anh hello', 'mention_ids' => [$original->id]])->assertRedirect()->assertSessionHasNoErrors();
        $comment = Comment::sole();
        $original->update(['username' => 'new-minh']);
        $replacement = User::factory()->create(['username' => 'minh-anh']);
        $this->patch(route('comments.update', $comment), ['body' => '@minh-anh edited', 'mention_ids' => [$original->id]])->assertRedirect()->assertSessionHasNoErrors();
        $this->get(route('posts.show', $post->slug))->assertOk()->assertSee('@new-minh')->assertSee(route('mentions.profile', $original), false);
        $this->patchJson(route('comments.update', $comment), ['body' => '@minh-anh hello', 'mention_ids' => [$original->id, $replacement->id]])->assertUnprocessable()->assertJsonValidationErrors('body');
        $this->assertSame(0, $replacement->notifications()->count());
    }

    public function test_comment_limit_is_atomic_and_a_handle_prefix_is_not_a_selected_mention(): void
    {
        $author = User::factory()->create(['username' => 'author']);
        $post = $this->thread($author);
        $people = collect(range(1, 6))->map(fn (int $index): User => User::factory()->create(['username' => 'person'.$index]));
        $this->actingAs($author)->postJson(route('comments.store', $post), ['body' => $people->map->handle()->implode(' '), 'mention_ids' => $people->pluck('id')->all()])->assertUnprocessable()->assertJsonValidationErrors('body');
        $this->assertDatabaseCount('comments', 0);
        $this->post(route('comments.store', $post), ['body' => '@person1-extra', 'mention_ids' => [$people->first()->id]])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertDatabaseCount('content_mentions', 0);
        $this->assertDatabaseCount('notifications', 0);
    }

    public function test_mention_rate_limit_rejects_the_whole_write_and_muted_authors_do_not_notify(): void
    {
        $author = User::factory()->create(['username' => 'author']);
        $recipient = User::factory()->create(['username' => 'reader']);
        RateLimiter::increment('mention-notifications:'.$author->id, 60, 20);
        $this->actingAs($author)->postJson(route('threads.store'), ['content_format' => 'html', 'content' => $this->mention($recipient)])->assertUnprocessable();
        $this->assertDatabaseCount('posts', 0);
        $this->assertDatabaseCount('notifications', 0);
        $recipient->mutedUsers()->attach($author);
        $this->postJson(route('threads.store'), ['content_format' => 'html', 'content' => $this->mention($recipient)])->assertCreated();
        $this->assertDatabaseCount('notifications', 0);
    }

    public function test_push_delivery_skips_expired_sessions_without_contacting_the_provider(): void
    {
        $this->enablePush();
        $author = User::factory()->create(['username' => 'author']);
        $recipient = User::factory()->create(['username' => 'reader']);
        $this->actingAs($recipient)->postJson(route('push.store'), $this->subscription())->assertOk();
        $this->actingAs($author)->postJson(route('threads.store'), ['content_format' => 'html', 'content' => $this->mention($recipient)])->assertCreated();
        $notification = $recipient->notifications()->sole();
        config(['session.driver' => 'database']);
        $this->mock(WebPush::class)->shouldNotReceive('sendOneNotification');
        app()->call([new SendWebPush($recipient->id, $notification->id), 'handle']);
        $this->assertDatabaseCount('push_subscriptions', 1);
    }

    /** @return array{endpoint: string, keys: array{p256dh: string, auth: string}} */
    private function subscription(): array
    {
        return ['endpoint' => 'https://fcm.googleapis.com/fcm/send/test-endpoint', 'keys' => ['p256dh' => rtrim(strtr(base64_encode("\x04".str_repeat('a', 64)), '+/', '-_'), '='), 'auth' => rtrim(strtr(base64_encode(str_repeat('b', 16)), '+/', '-_'), '=')]];
    }

    private function mention(User $user): string
    {
        return '<a href="'.route('mentions.profile', $user).'">'.$user->handle().'</a>';
    }

    private function thread(User $author): Post
    {
        return Post::factory()->published()->create(['user_id' => $author->id, 'slug' => 'thread-'.Str::uuid(), 'content' => 'Test thread']);
    }
}

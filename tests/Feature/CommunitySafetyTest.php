<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Comment;
use App\Models\ContentReport;
use App\Models\Media;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class CommunitySafetyTest extends TestCase
{
    use RefreshDatabase;

    public function test_block_hides_both_profiles_search_results_and_posts_and_prevents_interactions(): void
    {
        $alice = User::factory()->create(['username' => 'alice_safety']);
        $bob = User::factory()->create(['username' => 'bob_safety']);
        $alicePost = $this->thread($alice, 'Alice secret conversation');
        $bobPost = $this->thread($bob, 'Bob secret conversation');
        $alice->following()->attach($bob->id);
        $bob->following()->attach($alice->id);
        DB::table('follow_requests')->insert(['requester_id' => $alice->id, 'target_id' => $bob->id, 'created_at' => now()]);

        $this->actingAs($alice)->put(route('safety.block', $bob))->assertRedirect(route('home'));
        $this->assertDatabaseCount('follows', 0);
        $this->assertDatabaseCount('follow_requests', 0);

        foreach ([[$alice, $bob, $bobPost], [$bob, $alice, $alicePost]] as [$viewer, $target, $post]) {
            $this->actingAs($viewer)->get(route('home'))->assertOk()->assertDontSee($post->content);
            $this->get(route('profile.show', $target->username))->assertNotFound();
            $this->get(route('search.index', ['q' => $target->username, 'type' => 'people']))
                ->assertOk()->assertViewHas('people', fn ($people): bool => $people->isEmpty());
            $this->get(route('threads.show', $post))->assertNotFound();
            $this->get(route('posts.show', $post->slug))->assertNotFound();
            foreach (['threads.like', 'threads.bookmark', 'threads.repost'] as $route) {
                $this->postJson(route($route, $post))->assertNotFound();
            }
            $this->postJson(route('threads.store'), ['content' => 'Forbidden reply', 'parent_id' => $post->id])->assertNotFound();
            $this->postJson(route('threads.store'), ['content' => 'Forbidden quote', 'repost_of_id' => $post->id])->assertNotFound();
            $this->postJson(route('users.follow', $target))->assertNotFound();
        }

        $this->assertDatabaseCount('posts', 2);
        $this->assertDatabaseCount('post_likes', 0);
    }

    public function test_unblocking_is_idempotent_and_does_not_restore_follows(): void
    {
        $alice = User::factory()->create();
        $bob = User::factory()->create();
        $post = $this->thread($bob, 'Visible after unblock');
        $alice->following()->attach($bob->id);
        $this->actingAs($alice)->put(route('safety.block', $bob))->assertRedirect();
        $this->put(route('safety.block', $bob))->assertRedirect();
        $this->assertDatabaseCount('user_safety_blocks', 1);
        $this->get(route('safety.index'))->assertOk()->assertSee('Bỏ chặn');
        $this->delete(route('safety.block', $bob))->assertRedirect(route('safety.index'));
        $this->delete(route('safety.block', $bob))->assertRedirect();
        $this->get(route('threads.show', $post))->assertOk();
        $this->assertFalse($alice->isFollowing($bob));
    }

    public function test_managed_avatar_shows_in_blocked_list_without_allowing_blocked_users_to_view_it(): void
    {
        Storage::fake('local');
        $ownerAvatar = UploadedFile::fake()->image('owner.png')->store('avatars', 'local');
        $targetAvatar = UploadedFile::fake()->image('target.png')->store('avatars', 'local');
        $owner = User::factory()->create(['avatar_path' => $ownerAvatar]);
        $target = User::factory()->create(['avatar_path' => $targetAvatar]);
        $stranger = User::factory()->create();

        $this->get(route('safety.avatar', $target))->assertRedirect(route('login'));
        $this->actingAs($owner)->put(route('safety.block', $target))->assertRedirect();
        $this->get(route('safety.index'))->assertOk()->assertSee(route('safety.avatar', $target), false);
        $avatar = $this->get(route('safety.avatar', $target))->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->assertStringContainsString('no-store', $avatar->headers->get('Cache-Control'));
        $this->get(route('avatar.show', $target))->assertNotFound();

        $this->actingAs($target)->get(route('safety.avatar', $owner))->assertNotFound();
        $this->get(route('avatar.show', $owner))->assertNotFound();
        $this->get(route('profile.show', $owner->id))->assertNotFound();
        $this->actingAs($stranger)->get(route('safety.avatar', $target))->assertNotFound();
    }

    public function test_managed_avatar_works_for_muted_users_and_keeps_initials_when_no_photo_exists(): void
    {
        Storage::fake('local');
        $avatarPath = UploadedFile::fake()->image('muted.png')->store('avatars', 'local');
        $owner = User::factory()->create();
        $target = User::factory()->create(['avatar_path' => $avatarPath]);
        $withoutPhoto = User::factory()->create(['name' => 'Fallback Person', 'avatar_path' => null]);

        $this->actingAs($owner)->put(route('safety.mute', $target))->assertRedirect();
        $this->put(route('safety.block', $withoutPhoto))->assertRedirect();
        $this->get(route('safety.index'))->assertOk()->assertSee(route('safety.avatar', $target), false)
            ->assertDontSee(route('safety.avatar', $withoutPhoto), false)->assertSee('avatar-letter', false);
        $this->get(route('safety.avatar', $target))->assertOk();
        $this->delete(route('safety.mute', $target))->assertRedirect();
        $this->get(route('safety.avatar', $target))->assertNotFound();
    }

    public function test_muting_hides_posts_and_quotes_from_both_home_tabs_without_unfollowing(): void
    {
        $viewer = User::factory()->create();
        $author = User::factory()->create();
        $other = User::factory()->create();
        $original = $this->thread($author, 'Muted original content');
        $quote = $this->thread($other, 'Quote of muted author', ['type' => 'quote', 'repost_of_id' => $original->id]);
        $viewer->following()->attach([$author->id, $other->id]);

        $this->actingAs($viewer)->put(route('safety.mute', $author))->assertRedirect();
        $this->put(route('safety.mute', $author))->assertRedirect();
        foreach ([[], ['tab' => 'following']] as $parameters) {
            $this->get(route('home', $parameters))->assertOk()
                ->assertDontSee($original->content)->assertDontSee($quote->content);
        }
        $this->assertTrue($viewer->isFollowing($author));
        $this->assertDatabaseCount('user_mutes', 1);
        $this->get(route('threads.show', $original))->assertOk()->assertSee($original->content);
        $this->get(route('profile.show', $author->id))->assertOk()->assertSee($original->content);
        $this->get(route('safety.index'))->assertOk()->assertSee('Bỏ tắt tiếng');
        $this->delete(route('safety.mute', $author))->assertRedirect();
        $this->get(route('home'))->assertOk()->assertSee($original->content);
    }

    public function test_muted_accounts_can_still_be_unmuted_after_blocking_them(): void
    {
        $viewer = User::factory()->create();
        $other = User::factory()->create();
        $this->actingAs($viewer)->put(route('safety.mute', $other))->assertRedirect();
        $this->put(route('safety.block', $other))->assertRedirect();
        $this->delete(route('safety.mute', $other))->assertRedirect();
        $this->assertDatabaseCount('user_mutes', 0);
        $this->assertDatabaseCount('user_safety_blocks', 1);
    }

    public function test_private_posts_and_media_are_hidden_from_guests_and_unapproved_followers(): void
    {
        Storage::fake('local');
        $owner = User::factory()->create(['is_private' => true, 'username' => 'private_owner']);
        $viewer = User::factory()->create();
        $post = $this->thread($owner, 'Sensitive private post');
        Storage::disk('local')->put('media/private.png', 'private-image');
        $media = Media::create(['user_id' => $owner->id, 'path' => 'media/private.png', 'original_name' => 'private.png', 'mime_type' => 'image/png', 'size' => 13]);
        $post->media()->attach($media);

        $this->get(route('home'))->assertOk()->assertDontSee($post->content);
        $this->get(route('profile.show', $owner->username))->assertOk()->assertSee('chế độ riêng tư')->assertDontSee($post->content);
        $this->get(route('threads.show', $post))->assertNotFound();
        $this->get(route('posts.show', $post->slug))->assertNotFound();
        $this->get(route('media.show', $media))->assertNotFound();
        $this->get(route('sitemap'))->assertOk()->assertDontSee($post->slug);

        $this->actingAs($viewer)->postJson(route('users.follow', $owner))->assertOk()
            ->assertJsonPath('following', false)->assertJsonPath('requested', true)->assertJsonPath('follow_label', 'Đã gửi yêu cầu');
        $this->get(route('threads.show', $post))->assertNotFound();
        $this->get(route('media.show', $media))->assertNotFound();
        $this->get(route('search.index', ['q' => $post->content]))->assertOk()->assertViewHas('threads', fn ($threads): bool => $threads->isEmpty());
        $this->get(route('profile.show', $owner->username))->assertOk()->assertSee('đang chờ');

        $this->actingAs($owner)->get(route('safety.index'))->assertOk()->assertSee($viewer->name);
        $this->post(route('safety.follow-request', $viewer), ['decision' => 'accept'])->assertRedirect();
        $this->actingAs($viewer)->get(route('threads.show', $post))->assertOk()->assertSee($post->content);
        $this->get(route('media.show', $media))->assertOk();
        $this->get(route('profile.show', $owner->username))->assertOk()->assertSee($post->content);
    }

    public function test_follow_requests_can_be_cancelled_rejected_and_only_the_owner_can_decide(): void
    {
        $owner = User::factory()->create(['is_private' => true]);
        $requester = User::factory()->create();
        $intruder = User::factory()->create();

        $this->actingAs($requester)->postJson(route('users.follow', $owner))->assertJsonPath('requested', true);
        $this->postJson(route('users.follow', $owner))->assertJsonPath('requested', false);
        $this->assertDatabaseCount('follow_requests', 0);
        $this->postJson(route('users.follow', $owner))->assertJsonPath('requested', true);

        $this->actingAs($intruder)->post(route('safety.follow-request', $requester), ['decision' => 'accept'])->assertNotFound();
        $this->assertFalse($requester->isFollowing($owner));
        $this->actingAs($owner)->post(route('safety.follow-request', $requester), ['decision' => 'reject'])->assertRedirect();
        $this->assertDatabaseCount('follow_requests', 0);
        $this->assertFalse($requester->isFollowing($owner));
    }

    public function test_topics_and_navigation_do_not_expose_private_only_categories_or_tags(): void
    {
        $owner = User::factory()->create(['is_private' => true]);
        $post = $this->thread($owner, 'Hidden topic post');
        $category = Category::factory()->create(['name' => 'Secret private category']);
        $tag = Tag::factory()->create(['name' => 'Secret private hashtag']);
        $post->categories()->attach($category);
        $post->tags()->attach($tag);

        foreach (['home', 'search.index', 'threads.index'] as $route) {
            $this->get(route($route))->assertOk()->assertDontSee($category->name)->assertDontSee($tag->name);
        }
    }

    public function test_privacy_settings_keep_existing_followers_and_accept_pending_requests_when_public(): void
    {
        $owner = User::factory()->create();
        $existing = User::factory()->create();
        $pending = User::factory()->create();
        $existing->following()->attach($owner->id);

        $this->actingAs($owner)->patch(route('safety.privacy'), ['is_private' => true])->assertRedirect();
        $this->assertTrue($owner->fresh()->is_private);
        $this->assertTrue($existing->isFollowing($owner));
        $this->actingAs($pending)->postJson(route('users.follow', $owner))->assertJsonPath('requested', true);
        $this->actingAs($owner)->patch(route('safety.privacy'), ['is_private' => false])->assertRedirect();
        $this->assertFalse($owner->fresh()->is_private);
        $this->assertTrue($pending->isFollowing($owner));
        $this->assertDatabaseCount('follow_requests', 0);
    }

    public function test_unfollowing_a_private_account_immediately_revokes_post_access(): void
    {
        $owner = User::factory()->create(['is_private' => true]);
        $viewer = User::factory()->create();
        $post = $this->thread($owner, 'Approved followers only');
        $viewer->following()->attach($owner->id);

        $this->actingAs($viewer)->get(route('threads.show', $post))->assertOk();
        $this->postJson(route('users.follow', $owner))->assertJsonPath('following', false)->assertJsonPath('requested', false);
        $this->get(route('threads.show', $post))->assertNotFound();
        $this->get(route('home'))->assertOk()->assertDontSee($post->content);
    }

    public function test_private_ancestor_hides_public_replies_quotes_and_reposts_even_by_direct_link(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $root = $this->thread($owner, 'Original private ancestor');
        $reply = $this->thread($other, 'Public reply to private ancestor', ['parent_id' => $root->id, 'type' => 'reply']);
        $nested = $this->thread($other, 'Nested private ancestor reply', ['parent_id' => $reply->id, 'type' => 'reply']);
        $quote = $this->thread($other, 'Public quote of private ancestor', ['repost_of_id' => $root->id, 'type' => 'quote']);

        $this->actingAs($owner)->patch(route('safety.privacy'), ['is_private' => true])->assertRedirect();
        $this->actingAs($other);
        foreach ([$root, $reply, $nested, $quote] as $post) {
            $this->get(route('threads.show', $post))->assertNotFound();
        }
        $this->get(route('home'))->assertOk()->assertDontSee($quote->content);
    }

    public function test_even_approved_followers_cannot_repost_or_quote_private_posts(): void
    {
        $owner = User::factory()->create(['is_private' => true]);
        $viewer = User::factory()->create();
        $viewer->following()->attach($owner->id);
        $post = $this->thread($owner, 'Do not reshare private post');

        $this->actingAs($viewer)->postJson(route('threads.repost', $post))->assertForbidden();
        $this->postJson(route('threads.store'), ['content' => 'Quote', 'repost_of_id' => $post->id])->assertForbidden();
        $this->postJson(route('threads.store'), ['content' => 'Allowed reply', 'parent_id' => $post->id])->assertOk();
    }

    public function test_blocks_hide_saved_liked_activity_and_legacy_comment_branches(): void
    {
        $viewer = User::factory()->create();
        $other = User::factory()->create();
        $neutral = User::factory()->create();
        $post = $this->thread($other, 'Formerly saved blocked post');
        $viewerPost = $this->thread($viewer, 'Viewer post');
        $viewer->bookmarks()->attach($post->id);
        $viewer->likedPosts()->attach($post->id);
        $other->likedPosts()->attach($viewerPost->id);
        $legacy = $this->thread($neutral, 'Legacy neutral article');
        $comment = Comment::factory()->create(['post_id' => $legacy->id, 'user_id' => $other->id, 'body' => 'Blocked comment body']);

        $this->actingAs($viewer)->put(route('safety.block', $other))->assertRedirect();
        $this->get(route('saved'))->assertOk()->assertDontSee($post->content);
        $this->get(route('liked.index'))->assertOk()->assertDontSee($post->content);
        $this->get(route('activity.index'))->assertOk()->assertDontSee($other->name);
        $this->get(route('threads.show', $legacy))->assertOk()->assertDontSee($comment->body);
        $this->getJson(route('comments.replies', $comment))->assertNotFound();
    }

    public function test_only_self_can_change_privacy_and_profile_mass_assignment_cannot_bypass_it(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $this->actingAs($owner)->patch(route('safety.privacy'), ['is_private' => 'invalid'])->assertSessionHasErrors('is_private');
        $this->patch(route('safety.privacy'), ['is_private' => true, 'user_id' => $other->id])->assertRedirect();
        $this->assertTrue($owner->fresh()->is_private);
        $this->assertFalse($other->fresh()->is_private);
        $this->actingAs($other)->patch(route('account.update'), ['name' => $other->name, 'is_private' => true])->assertSessionHasNoErrors();
        $this->assertFalse($other->fresh()->is_private);
    }

    public function test_reports_are_validated_deduplicated_and_cannot_forge_reporter_or_status(): void
    {
        $reporter = User::factory()->create();
        $owner = User::factory()->create();
        $post = $this->thread($owner, 'Reported content');

        $this->actingAs($reporter)->get(route('reports.thread', $post))->assertOk()->assertSee('Spam');
        $this->post(route('reports.thread', $post), ['reason' => 'invalid'])->assertSessionHasErrors('reason');
        $this->post(route('reports.thread', $post), ['reason' => 'spam', 'details' => '<script>unsafe()</script>', 'reporter_id' => $owner->id, 'status' => 'resolved'])
            ->assertRedirect(route('home'))->assertSessionHasNoErrors();
        $this->post(route('reports.thread', $post), ['reason' => 'hate'])->assertRedirect();

        $report = ContentReport::sole();
        $this->assertSame($reporter->id, $report->reporter_id);
        $this->assertSame('pending', $report->status);
        $this->assertSame('spam', $report->reason);
        $this->post(route('reports.user', $owner), ['reason' => 'harassment'])->assertRedirect();
        $this->assertDatabaseCount('content_reports', 2);
        $this->get(route('home'))->assertOk()->assertDontSee('unsafe()');
    }

    public function test_only_admin_can_review_reports_and_hiding_a_reported_thread_hides_its_descendants(): void
    {
        $owner = User::factory()->create();
        $reporter = User::factory()->create();
        $admin = User::factory()->create(['role' => 'admin']);
        $post = $this->thread($owner, 'Thread for moderator');
        $reply = $this->thread($reporter, 'Reply to hidden thread', ['parent_id' => $post->id, 'type' => 'reply']);
        $this->actingAs($reporter)->post(route('reports.thread', $post), ['reason' => 'hate', 'details' => '<script>unsafe()</script>'])->assertRedirect();
        $report = ContentReport::sole();

        $this->get(route('admin.moderation.index'))->assertForbidden();
        $this->patch(route('admin.moderation.update', $report), ['admin_note' => 'Đã xem xét', 'reporter_message' => 'Đã xử lý báo cáo', 'action_reason' => 'Vi phạm nội quy', 'action' => 'hide_thread'])->assertForbidden();
        $this->actingAs($admin)->get(route('admin.moderation.index'))->assertOk()
            ->assertSee('&lt;script&gt;unsafe()&lt;/script&gt;', false)->assertDontSee('<script>unsafe()</script>', false);
        $this->patch(route('admin.moderation.update', $report), ['admin_note' => 'Đã xem xét', 'reporter_message' => 'Đã xử lý báo cáo', 'action_reason' => 'Vi phạm nội quy', 'action' => 'hide_thread', 'admin_note' => 'Confirmed violation'])->assertRedirect();
        $this->assertSoftDeleted($post);
        $this->assertSame('resolved', $report->fresh()->status);
        $this->assertSame($admin->id, $report->fresh()->handled_by);
        $this->assertNotNull($report->fresh()->handled_at);
        $this->actingAs($reporter)->get(route('threads.show', $post))->assertNotFound();
        $this->get(route('threads.show', $reply))->assertNotFound();
        $this->actingAs($admin)->get(route('admin.moderation.index', ['status' => 'resolved']))->assertOk()->assertSee('Confirmed violation');
    }

    public function test_admin_can_lock_reported_members_but_cannot_lock_another_admin(): void
    {
        $reporter = User::factory()->create();
        $member = User::factory()->create();
        $admin = User::factory()->create(['role' => 'admin']);
        $otherAdmin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($reporter)->post(route('reports.user', $member), ['reason' => 'harassment'])->assertRedirect();
        $report = ContentReport::sole();
        $this->actingAs($admin)->patch(route('admin.moderation.update', $report), ['admin_note' => 'Đã xem xét', 'reporter_message' => 'Đã xử lý báo cáo', 'action_reason' => 'Vi phạm nội quy', 'action' => 'lock_user'])->assertRedirect();
        $this->assertTrue($member->fresh()->is_locked);

        $this->actingAs($reporter)->post(route('reports.user', $otherAdmin), ['reason' => 'spam'])->assertRedirect();
        $adminReport = ContentReport::where('reported_user_id', $otherAdmin->id)->sole();
        $this->actingAs($admin)->patch(route('admin.moderation.update', $adminReport), ['admin_note' => 'Đã xem xét', 'reporter_message' => 'Đã xử lý báo cáo', 'action_reason' => 'Vi phạm nội quy', 'action' => 'lock_user'])->assertForbidden();
        $this->assertFalse($otherAdmin->fresh()->is_locked);
        $this->assertSame('pending', $adminReport->fresh()->status);
    }

    public function test_admin_can_dismiss_reports_and_cannot_process_them_twice(): void
    {
        $reporter = User::factory()->create();
        $owner = User::factory()->create();
        $admin = User::factory()->create(['role' => 'admin']);
        $post = $this->thread($owner, 'Dismissed report content');

        $this->actingAs($reporter)->post(route('reports.thread', $post), ['reason' => 'spam'])->assertRedirect();
        $report = ContentReport::sole();
        $this->actingAs($admin)->patch(route('admin.moderation.update', $report), ['admin_note' => 'Đã xem xét', 'reporter_message' => 'Đã xử lý báo cáo', 'action_reason' => 'Vi phạm nội quy', 'action' => 'dismiss'])->assertRedirect();
        $this->assertSame('dismissed', $report->fresh()->status);
        $this->assertFalse($post->fresh()->trashed());
        $this->patch(route('admin.moderation.update', $report), ['admin_note' => 'Đã xem xét', 'reporter_message' => 'Đã xử lý báo cáo', 'action_reason' => 'Vi phạm nội quy', 'action' => 'hide_thread'])->assertUnprocessable();
    }

    public function test_safety_actions_require_authentication_and_reject_self_targets(): void
    {
        $user = User::factory()->create();
        $this->get(route('safety.index'))->assertRedirect(route('login'));
        $this->put(route('safety.block', $user))->assertRedirect(route('login'));
        $this->put(route('safety.mute', $user))->assertRedirect(route('login'));
        $this->post(route('reports.user', $user), ['reason' => 'spam'])->assertRedirect(route('login'));
        $this->get(route('admin.moderation.index'))->assertRedirect(route('login'));

        $this->actingAs($user)->put(route('safety.block', $user))->assertUnprocessable();
        $this->put(route('safety.mute', $user))->assertUnprocessable();
        $this->post(route('reports.user', $user), ['reason' => 'spam'])->assertUnprocessable();
        $this->assertDatabaseCount('user_safety_blocks', 0);
        $this->assertDatabaseCount('user_mutes', 0);
        $this->assertDatabaseCount('content_reports', 0);
    }

    private function thread(User $author, string $content, array $overrides = []): Post
    {
        return Post::factory()->published()->create(array_replace([
            'user_id' => $author->id, 'slug' => 'thread-'.Str::uuid(),
            'title' => $content, 'content' => $content,
        ], $overrides));
    }
}

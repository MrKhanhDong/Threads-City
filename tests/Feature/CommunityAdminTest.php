<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\ContentReport;
use App\Models\Post;
use App\Models\User;
use App\Notifications\ModerationDecision;
use App\Notifications\ReportHandled;
use App\Services\UserNotifications;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommunityAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_guests_and_verified_readers_cannot_moderate_or_read_history(): void
    {
        $this->get(route('admin.history.index'))->assertRedirect(route('login'));
        $reader = User::factory()->create(['is_verified' => true]);
        $post = Post::factory()->published()->create();
        $this->actingAs($reader)->get(route('admin.community.index'))->assertForbidden();
        $this->get(route('admin.history.index'))->assertForbidden();
        $this->patch(route('admin.community.update', $post->id), ['action' => 'hide', 'reason' => 'Spam'])->assertForbidden();
        $this->assertDatabaseCount('moderation_logs', 0);
        $this->assertFalse($post->fresh()->trashed());
    }

    public function test_admin_can_hide_and_restore_without_editing_content_and_cannot_restore_owner_deletions(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $post = Post::factory()->published()->create(['content' => 'Nội dung nguyên gốc']);
        $this->actingAs($admin)->patch(route('admin.community.update', $post->id), ['action' => 'hide'])->assertSessionHasErrors('reason');
        $this->patch(route('admin.community.update', $post->id), ['action' => 'hide', 'reason' => 'Spam'])->assertSessionHasNoErrors();
        $hidden = Post::withTrashed()->findOrFail($post->id);
        $this->assertTrue($hidden->trashed());
        $this->assertNotNull($hidden->moderation_hidden_at);
        $this->assertSame('Nội dung nguyên gốc', $hidden->content);
        $this->assertDatabaseHas('moderation_logs', ['admin_id' => $admin->id, 'target_type' => 'post', 'target_id' => $post->id, 'action' => 'hide_thread', 'reason' => 'Spam']);
        $this->assertSame(ModerationDecision::class, $post->author->notifications()->first()->type);
        $this->patch(route('admin.community.update', $post->id), ['action' => 'restore', 'reason' => 'Xử lý nhầm'])->assertSessionHasNoErrors();
        $this->assertFalse($post->fresh()->trashed());
        $this->assertNull($post->fresh()->moderation_hidden_at);
        $post->delete();
        $this->patch(route('admin.community.update', $post->id), ['action' => 'restore', 'reason' => 'Thử khôi phục'])->assertUnprocessable();
        $this->assertDatabaseCount('moderation_logs', 2);
    }

    public function test_admin_cannot_edit_or_delete_someone_elses_post_through_old_routes(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $post = Post::factory()->published()->create();
        $this->actingAs($admin)->get(route('admin.posts.edit', $post))->assertForbidden();
        $this->delete(route('admin.posts.destroy', $post))->assertForbidden();
        $this->delete(route('threads.destroy', $post))->assertForbidden();
        $this->assertFalse($post->fresh()->trashed());
    }

    public function test_temporary_lock_expires_for_authentication_and_queries_without_changing_role(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $reader = User::factory()->create();
        $expiry = now()->addHour()->format('Y-m-d H:i:s');
        $this->actingAs($admin)->patch(route('admin.users.update', $reader), ['is_locked' => 1, 'reason' => 'Quấy rối', 'lock_expires_at' => $expiry, 'role' => 'admin'])->assertSessionHasNoErrors();
        $this->assertTrue($reader->fresh()->is_locked);
        $this->assertSame('reader', $reader->fresh()->role);
        $this->assertFalse(User::notLocked()->whereKey($reader->id)->exists());
        $this->patch(route('admin.users.update', $admin), ['is_locked' => 1, 'reason' => 'Thử khóa'])->assertForbidden();
        $this->actingAs($reader->fresh())->get(route('account.edit'))->assertRedirect(route('login'));
        $this->post(route('login.store'), ['login' => $reader->email, 'password' => 'password'])->assertSessionHasErrors('login');
        $this->travel(61)->minutes();
        $this->assertFalse($reader->fresh()->is_locked);
        $this->assertTrue(User::notLocked()->whereKey($reader->id)->exists());
        $this->post(route('login.store'), ['login' => $reader->email, 'password' => 'password'])->assertSessionHasNoErrors();
        $this->assertAuthenticatedAs($reader);
    }

    public function test_lock_validation_unlock_and_verification_are_audited(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $reader = User::factory()->create();
        $this->actingAs($admin)->patch(route('admin.users.update', $reader), ['is_locked' => 1])->assertSessionHasErrors('reason');
        $this->patch(route('admin.users.update', $reader), ['is_locked' => 1, 'reason' => 'Spam', 'lock_expires_at' => now()->subDay()->format('Y-m-d H:i:s')])->assertSessionHasErrors('lock_expires_at');
        $this->patch(route('admin.users.update', $reader), ['is_locked' => 1, 'reason' => 'Spam'])->assertSessionHasNoErrors();
        $this->patch(route('admin.users.update', $reader), ['is_locked' => 0, 'reason' => 'Khiếu nại hợp lệ'])->assertSessionHasNoErrors();
        $this->assertFalse($reader->fresh()->is_locked);
        $this->patch(route('admin.users.verification', $reader), ['is_verified' => 1, 'reason' => 'Xác minh danh tính'])->assertSessionHasNoErrors();
        $this->assertTrue($reader->fresh()->is_verified);
        $this->assertFalse($reader->fresh()->isAdmin());
        $this->assertDatabaseCount('moderation_logs', 3);
    }

    public function test_report_resolution_notifies_both_parties_and_rejects_repeat_processing(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $reporter = User::factory()->create();
        $post = Post::factory()->published()->create();
        $report = ContentReport::create(['reporter_id' => $reporter->id, 'post_id' => $post->id, 'reason' => 'spam', 'snapshot' => 'Nội dung báo cáo']);
        $payload = ['action' => 'hide_thread', 'admin_note' => 'Ghi chú mật nội bộ', 'reporter_message' => 'Đã ẩn nội dung vi phạm', 'action_reason' => 'Spam đã xác nhận'];
        $this->actingAs($admin)->patch(route('admin.moderation.update', $report), ['action' => 'hide_thread'])->assertSessionHasErrors(['admin_note', 'reporter_message']);
        $this->patch(route('admin.moderation.update', $report), $payload)->assertSessionHasNoErrors();
        $this->assertSame('resolved', $report->fresh()->status);
        $this->assertSame($admin->id, $report->fresh()->handled_by);
        $this->assertNotNull(Post::withTrashed()->find($post->id)->moderation_hidden_at);
        $this->assertSame(ReportHandled::class, $reporter->notifications()->first()->type);
        $this->assertSame(ModerationDecision::class, $post->author->notifications()->first()->type);
        $this->assertSame('Spam đã xác nhận', $post->author->notifications()->first()->data['reason']);
        $this->assertStringNotContainsString('Ghi chú mật', json_encode($post->author->notifications()->first()->data));
        $this->patch(route('admin.moderation.update', $report), $payload)->assertUnprocessable();
        $this->assertDatabaseCount('moderation_logs', 2);
    }

    public function test_report_lock_uses_duration_and_cannot_lock_admin(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $reader = User::factory()->create();
        $reporter = User::factory()->create();
        $report = ContentReport::create(['reporter_id' => $reporter->id, 'reported_user_id' => $reader->id, 'reason' => 'harassment', 'snapshot' => 'Tài khoản vi phạm']);
        $payload = ['action' => 'lock_user', 'admin_note' => 'Quấy rối', 'action_reason' => 'Quấy rối', 'reporter_message' => 'Đã xử lý', 'lock_expires_at' => now()->addDay()->format('Y-m-d H:i:s')];
        $this->actingAs($admin)->patch(route('admin.moderation.update', $report), $payload)->assertSessionHasNoErrors();
        $this->assertTrue($reader->fresh()->is_locked);
        $this->assertNotNull($reader->fresh()->lock_expires_at);
        $protected = ContentReport::create(['reporter_id' => $reporter->id, 'reported_user_id' => $admin->id, 'reason' => 'other', 'snapshot' => 'Admin']);
        $this->patch(route('admin.moderation.update', $protected), $payload)->assertForbidden();
        $this->assertSame('pending', $protected->fresh()->status);
    }

    public function test_comment_moderation_preserves_context_and_requires_reason(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $comment = Comment::factory()->create();
        $this->actingAs($admin)->patch(route('admin.comments.update', $comment), ['status' => 'hidden'])->assertSessionHasErrors('reason');
        $this->patch(route('admin.comments.update', $comment), ['status' => 'hidden', 'reason' => 'Spam'])->assertSessionHasNoErrors();
        $this->assertSame('hidden', $comment->fresh()->status);
        $this->delete(route('admin.comments.destroy', $comment))->assertForbidden();
        $this->patch(route('admin.comments.update', $comment), ['status' => 'approved', 'reason' => 'Khôi phục sau xem xét'])->assertSessionHasNoErrors();
        $this->assertSame('approved', $comment->fresh()->status);
        $this->assertDatabaseCount('moderation_logs', 2);
    }

    public function test_admin_pages_filters_and_history_render_real_records(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $post = Post::factory()->published()->create(['content' => '<script>alert(1)</script> Nội dung kiểm duyệt']);
        $reply = Post::factory()->published()->create(['parent_id' => $post->id, 'type' => 'reply']);
        $this->actingAs($admin)->get(route('admin.community.index', ['kind' => 'reply']))->assertOk()->assertViewHas('posts', fn ($posts) => $posts->total() === 1)->assertSee('Ngữ cảnh');
        $this->get(route('admin.community.index'))->assertOk()->assertDontSee('<script>alert(1)</script>', false);
        $this->patch(route('admin.community.update', $post->id), ['action' => 'hide', 'reason' => '<script>unsafe()</script>'])->assertSessionHasNoErrors();
        $this->get(route('admin.community.index', ['status' => 'hidden']))->assertOk()->assertViewHas('posts', fn ($posts) => $posts->total() === 1);
        $this->get(route('admin.history.index', ['target_type' => 'post', 'target_id' => $post->id]))->assertOk()->assertSee('&lt;script&gt;unsafe()', false)->assertDontSee('<script>unsafe()</script>', false);
        foreach (['admin.index', 'admin.users.index', 'admin.comments.index', 'admin.moderation.index', 'admin.settings.edit'] as $name) {
            $this->get(route($name))->assertOk();
        }
        $this->get(route('admin.index'))->assertSee('Báo cáo vi phạm')->assertDontSee('Viết bài mới');
    }

    public function test_moderation_notifications_are_private_visible_and_can_be_read(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $owner->notify(new ModerationDecision('Bài của bạn bị ẩn', 'Spam'));
        $notification = $owner->notifications()->first();
        $this->assertSame(1, app(UserNotifications::class)->forUser($owner)->count());
        $this->actingAs($other)->post(route('notifications.open', $notification->id))->assertNotFound();
        $this->actingAs($owner)->get(route('notifications.index'))->assertOk()->assertSee('Bài của bạn bị ẩn');
        $this->getJson(route('notifications.index', ['preview' => 1]))->assertOk()->assertJsonPath('unread', 1);
        $this->post(route('notifications.open', $notification->id))->assertRedirect(route('notifications.index'));
        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_rules_and_report_labels_are_saved_and_shown_safely(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->patch(route('admin.settings.update'), [
            'site_name' => 'Threads City', 'site_tagline' => 'Kết nối', 'site_description' => 'Cộng đồng',
            'community_rules' => '<script>unsafe()</script>', 'report_reason_labels' => ['spam' => 'Nội dung rác'],
        ])->assertSessionHasNoErrors();
        $this->get(route('community.rules'))->assertOk()->assertSee('&lt;script&gt;', false)->assertDontSee('<script>unsafe()</script>', false);
        $this->assertSame('Nội dung rác', ContentReport::reasons()['spam']);
        $this->assertArrayHasKey('hate', ContentReport::reasons());
    }
}

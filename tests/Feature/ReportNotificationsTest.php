<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\ContentReport;
use App\Models\Post;
use App\Models\User;
use App\Notifications\CommentReplied;
use App\Notifications\ReportHandled;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ReportNotificationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_decisions_keep_report_feedback_private_and_notify_affected_authors(): void
    {
        $reporter = User::factory()->create();
        $admin = User::factory()->create(['role' => 'admin']);
        $unread = 0;

        foreach (['resolve', 'dismiss', 'hide_thread', 'lock_user'] as $action) {
            $report = $this->reportThread($reporter);
            $message = 'Phản hồi dành cho người gửi: '.$action;
            $this->actingAs($admin)->patch(route('admin.moderation.update', $report), ['action_reason' => 'Vi phạm nội quy',
                'action' => $action, 'reporter_message' => $message, 'admin_note' => 'Internal staff only',
            ])->assertRedirect()->assertSessionHasNoErrors();

            $notification = $reporter->notifications()->where('data->report_id', $report->id)->sole();
            $this->assertSame(ReportHandled::class, $notification->type);
            $this->assertSame(['report_id' => $report->id], $notification->data);
            $this->assertNull($notification->read_at);
            $this->assertSame(0, $admin->notifications()->count());
            $this->assertSame(in_array($action, ['hide_thread', 'lock_user'], true) ? 1 : 0, $report->post->author->notifications()->count());
            foreach ($report->post->author->notifications as $ownerNotification) {
                $this->assertStringNotContainsString('Internal staff only', json_encode($ownerNotification->data));
            }

            $data = $this->actingAs($reporter)->getJson(route('notifications.index'))->assertOk()
                ->assertJsonPath('unread', ++$unread)->json();
            $this->assertStringContainsString($message, $data['html']);
            $this->assertStringContainsString($report->fresh()->outcomeMessage(), $data['html']);
            $this->assertStringNotContainsString('Internal staff only', $data['html']);
            $this->get(route('reports.show', $report))->assertOk()->assertSee($message)
                ->assertSee($report->fresh()->outcomeMessage())->assertDontSee('Internal staff only');
        }
    }

    public function test_opening_a_result_marks_it_read_and_opens_the_owned_report_even_after_post_removal(): void
    {
        $reporter = User::factory()->create();
        $admin = User::factory()->create(['role' => 'admin']);
        $report = $this->reportThread($reporter);

        $this->actingAs($admin)->patch(route('admin.moderation.update', $report), ['admin_note' => 'Đã xem xét', 'action_reason' => 'Vi phạm nội quy',
            'action' => 'hide_thread', 'reporter_message' => 'Cảm ơn bạn đã góp phần bảo vệ cộng đồng.',
        ])->assertSessionHasNoErrors();
        $notification = $reporter->notifications()->sole();

        $this->actingAs($reporter)->getJson(route('notifications.index', ['summary' => 1]))->assertJsonPath('unread', 1);
        $this->post(route('notifications.open', $notification->id))->assertRedirect(route('reports.show', $report));
        $this->assertNotNull($notification->fresh()->read_at);
        $this->getJson(route('notifications.index', ['summary' => 1]))->assertJsonPath('unread', 0);
        $this->get(route('reports.show', $report))->assertOk()->assertSee('đã được ẩn')->assertSee('Cảm ơn bạn');
        $this->get(route('reports.index'))->assertOk()->assertSee('Đã xử lý');
        $this->get(route('notifications.index'))->assertOk()->assertSee('Cảm ơn bạn')->assertSee('Đã đọc');
    }

    public function test_other_users_cannot_read_report_results_or_forge_access_through_a_notification(): void
    {
        $reporter = User::factory()->create();
        $other = User::factory()->create();
        $admin = User::factory()->create(['role' => 'admin']);
        $report = $this->reportThread($reporter);
        $this->actingAs($admin)->patch(route('admin.moderation.update', $report), ['admin_note' => 'Đã xem xét', 'reporter_message' => 'Đã xử lý báo cáo', 'action_reason' => 'Vi phạm nội quy', 'action' => 'resolve'])->assertRedirect();
        $notification = $reporter->notifications()->sole();
        $other->notify(new ReportHandled($report->id));
        $forged = $other->notifications()->sole();

        $this->actingAs($other)->get(route('reports.show', $report))->assertNotFound();
        $this->get(route('reports.index'))->assertOk()->assertViewHas('reports', fn ($reports): bool => $reports->isEmpty());
        $this->getJson(route('notifications.index'))->assertJsonPath('unread', 0);
        $this->post(route('notifications.open', $notification->id))->assertNotFound();
        $this->post(route('notifications.open', $forged->id))->assertNotFound();
        $this->assertNull($notification->fresh()->read_at);
    }

    public function test_failed_and_repeated_decisions_do_not_create_notifications(): void
    {
        $reporter = User::factory()->create();
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($reporter)->post(route('reports.user', $admin), ['reason' => 'spam'])->assertRedirect();
        $report = ContentReport::sole();

        $this->actingAs($admin)->patch(route('admin.moderation.update', $report), ['admin_note' => 'Đã xem xét', 'reporter_message' => 'Đã xử lý báo cáo', 'action_reason' => 'Vi phạm nội quy', 'action' => 'resolve', 'reporter_message' => str_repeat('x', 1001)])
            ->assertSessionHasErrors('reporter_message');
        $this->patch(route('admin.moderation.update', $report), ['admin_note' => 'Đã xem xét', 'reporter_message' => 'Đã xử lý báo cáo', 'action_reason' => 'Vi phạm nội quy', 'action' => 'lock_user'])->assertForbidden();
        $this->assertSame('pending', $report->fresh()->status);
        $this->assertDatabaseCount('notifications', 0);

        $this->patch(route('admin.moderation.update', $report), ['admin_note' => 'Đã xem xét', 'reporter_message' => 'Đã xử lý báo cáo', 'action_reason' => 'Vi phạm nội quy', 'action' => 'dismiss'])->assertRedirect();
        $this->patch(route('admin.moderation.update', $report), ['admin_note' => 'Đã xem xét', 'reporter_message' => 'Đã xử lý báo cáo', 'action_reason' => 'Vi phạm nội quy', 'action' => 'resolve'])->assertUnprocessable();
        $this->assertDatabaseCount('notifications', 1);
    }

    public function test_comment_and_report_notifications_share_unread_counts_and_private_mark_all(): void
    {
        $reporter = User::factory()->create();
        $other = User::factory()->create();
        $admin = User::factory()->create(['role' => 'admin']);
        $root = Comment::factory()->create(['user_id' => $reporter->id]);
        $reply = Comment::factory()->create(['post_id' => $root->post_id, 'parent_id' => $root->id]);
        $reporter->notify(new CommentReplied($reply->id));
        $other->notify(new CommentReplied($reply->id));
        $report = $this->reportThread($reporter);
        $this->actingAs($admin)->patch(route('admin.moderation.update', $report), ['admin_note' => 'Đã xem xét', 'reporter_message' => 'Đã xử lý báo cáo', 'action_reason' => 'Vi phạm nội quy', 'action' => 'resolve'])->assertRedirect();

        $this->actingAs($reporter)->getJson(route('notifications.index'))->assertOk()->assertJsonPath('unread', 2);
        $this->get(route('notifications.index'))->assertOk()->assertSee($reply->user->name)->assertSee('Báo cáo #'.$report->id);
        $this->postJson(route('notifications.read-all'))->assertOk();
        $this->getJson(route('notifications.index', ['summary' => 1]))->assertJsonPath('unread', 0);
        $this->assertSame(0, $reporter->unreadNotifications()->count());
        $this->assertNull($other->notifications()->sole()->read_at);
    }

    public function test_report_history_includes_old_decisions_without_exposing_internal_notes(): void
    {
        $reporter = User::factory()->create();
        $report = $this->reportThread($reporter);
        $report->forceFill(['status' => 'resolved', 'action' => 'hide_thread', 'admin_note' => 'Old internal evidence', 'handled_at' => now()])->save();

        $this->actingAs($reporter)->get(route('reports.index'))->assertOk()->assertSee('Đã xử lý');
        $this->get(route('reports.show', $report))->assertOk()->assertSee('đã được ẩn')->assertDontSee('Old internal evidence');
        $this->assertDatabaseCount('notifications', 0);
    }

    public function test_public_feedback_is_escaped_in_notifications_and_report_results(): void
    {
        $reporter = User::factory()->create();
        $admin = User::factory()->create(['role' => 'admin']);
        $report = $this->reportThread($reporter);
        $message = '<script>alert("unsafe")</script>';
        $this->actingAs($admin)->patch(route('admin.moderation.update', $report), ['action_reason' => 'Vi phạm nội quy',
            'action' => 'resolve', 'reporter_message' => $message, 'admin_note' => 'Secret review note',
        ])->assertRedirect();

        $data = $this->actingAs($reporter)->getJson(route('notifications.index'))->assertOk()->json();
        $this->assertStringContainsString(e($message), $data['html']);
        $this->assertStringNotContainsString($message, $data['html']);
        $this->get(route('reports.show', $report))->assertOk()->assertSee(e($message), false)->assertDontSee($message, false)->assertDontSee('Secret review note');
        $this->get(route('notifications.index'))->assertOk()->assertSee(e($message), false)->assertDontSee($message, false);
    }

    public function test_deleted_reporters_do_not_prevent_admin_from_processing_the_report(): void
    {
        $reporter = User::factory()->create();
        $admin = User::factory()->create(['role' => 'admin']);
        $report = $this->reportThread($reporter);
        $reporter->delete();

        $this->actingAs($admin)->patch(route('admin.moderation.update', $report), ['admin_note' => 'Đã xem xét', 'reporter_message' => 'Đã xử lý báo cáo', 'action_reason' => 'Vi phạm nội quy', 'action' => 'resolve'])->assertRedirect();
        $this->assertSame('resolved', $report->fresh()->status);
        $this->assertDatabaseCount('notifications', 0);
    }

    private function reportThread(User $reporter): ContentReport
    {
        $post = Post::factory()->published()->create(['slug' => 'thread-'.Str::uuid()]);
        $this->actingAs($reporter)->post(route('reports.thread', $post), ['reason' => 'spam'])->assertRedirect();

        return ContentReport::where('post_id', $post->id)->sole();
    }
}

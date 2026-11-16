<?php

namespace Tests\Feature;

use App\Models\ContentReport;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ThreadReportingTest extends TestCase
{
    use RefreshDatabase;

    public function test_modal_report_is_visible_to_admin_and_deduplicated_with_the_safety_form(): void
    {
        $reporter = User::factory()->create();
        $post = Post::factory()->published()->create(['content' => 'Nội dung cần quản trị xem xét']);
        $this->actingAs($reporter)->postJson(route('threads.report', $post), ['reason' => 'hate_speech', 'notes' => 'Chi tiết báo cáo'])->assertOk()->assertJsonPath('success', true);
        $report = ContentReport::sole();
        $this->assertSame('hate', $report->reason);
        $this->assertSame('Chi tiết báo cáo', $report->details);
        $this->assertSame('pending', $report->status);
        $this->post(route('reports.thread', $post), ['reason' => 'spam'])->assertRedirect();
        $this->assertDatabaseCount('content_reports', 1);
        $this->assertDatabaseCount('post_reports', 0);
        $this->actingAs(User::factory()->create(['role' => 'admin']))->get(route('admin.moderation.index'))->assertOk()->assertSee('Nội dung cần quản trị xem xét')->assertSee('Chi tiết báo cáo');
    }

    public function test_modal_reasons_work_and_invalid_or_self_reports_do_not_claim_success(): void
    {
        $post = Post::factory()->published()->create();
        foreach (['spam', 'hate_speech', 'false_info', 'sensitive', 'other'] as $reason) {
            $this->actingAs(User::factory()->create())->postJson(route('threads.report', $post), ['reason' => $reason])->assertOk()->assertJsonPath('success', true);
        }
        $this->postJson(route('threads.report', $post), ['reason' => 'invalid'])->assertUnprocessable();
        $this->actingAs($post->author)->postJson(route('threads.report', $post), ['reason' => 'spam'])->assertUnprocessable();
        $this->assertDatabaseCount('content_reports', 5);
    }

    public function test_legacy_reports_are_imported_once_without_overwriting_existing_admin_decisions(): void
    {
        $reporter = User::factory()->create();
        $post = Post::factory()->published()->create();
        DB::table('post_reports')->insert(['user_id' => $reporter->id, 'post_id' => $post->id, 'reason' => 'hate_speech', 'notes' => 'Báo cáo cũ', 'resolved' => false, 'created_at' => now(), 'updated_at' => now()]);
        $migration = require database_path('migrations/2026_11_16_131931_import_legacy_post_reports_into_content_reports.php');
        $migration->up();
        $report = ContentReport::sole();
        $this->assertSame('Báo cáo cũ', $report->details);
        $this->assertSame('hate', $report->reason);
        $report->forceFill(['status' => 'dismissed'])->save();
        $migration->up();
        $this->assertSame('dismissed', $report->fresh()->status);
        $this->assertDatabaseCount('content_reports', 1);
        $this->assertDatabaseCount('post_reports', 1);
    }
}

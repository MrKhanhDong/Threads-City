<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\Post;
use App\Models\PostDailyView;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ThreadInsightsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(CarbonImmutable::parse('2026-10-20 15:30:00', config('app.timezone')));
    }

    protected function tearDown(): void
    {
        $this->travelBack();
        parent::tearDown();
    }

    public function test_periods_include_each_calendar_day_and_compare_the_adjacent_previous_period(): void
    {
        $owner = User::factory()->create();
        $post = Post::factory()->published()->create(['user_id' => $owner->id, 'published_at' => now()->subYear(), 'views_count' => 99999]);
        foreach ([0, 6, 7, 29, 30, 89, 90, 179, 180] as $offset) {
            PostDailyView::factory()->create(['post_id' => $post->id, 'view_date' => today()->subDays($offset), 'views' => 10]);
        }
        PostDailyView::factory()->create(['post_id' => $post->id, 'view_date' => today()->addDay(), 'views' => 900]);
        $this->actingAs($owner);
        foreach ([7 => [20, 10], 30 => [40, 10], 90 => [60, 20]] as $days => [$current, $previous]) {
            $response = $this->get(route('insights.index', ['days' => $days]))->assertOk();
            $data = $response->viewData('insights');
            $this->assertCount($days, $data['dates']);
            $this->assertSame(today()->subDays($days - 1)->toDateString(), $data['dates'][0]);
            $this->assertSame(today()->toDateString(), $data['dates'][$days - 1]);
            $this->assertSame($current, $data['metrics']['views']['total']);
            $this->assertSame($previous, $data['metrics']['views']['previous']);
            $this->assertCount($days, $data['metrics']['views']['values']);
            $this->assertSame(0, $data['metrics']['views']['values'][1]);
            $this->assertSame(0, $data['posts']);
        }
    }

    public function test_likes_are_counted_by_timestamp_without_cached_totals_self_likes_or_future_likes(): void
    {
        $owner = User::factory()->create();
        $post = Post::factory()->published()->create(['user_id' => $owner->id, 'likes_count' => 777]);
        $actors = User::factory()->count(4)->create();
        foreach ([$actors[0]->id => today()->subDays(6), $actors[1]->id => now(), $actors[2]->id => today()->subDays(7), $actors[3]->id => now()->addHour(), $owner->id => now()] as $id => $date) {
            $post->likers()->attach($id, ['created_at' => $date]);
        }
        $response = $this->actingAs($owner)->get(route('insights.index', ['metric' => 'likes']))->assertOk();
        $likes = $response->viewData('insights')['metrics']['likes'];
        $this->assertSame(2, $likes['total']);
        $this->assertSame(1, $likes['previous']);
        $this->assertSame(100.0, $likes['change']);
        $this->assertSame(1, $likes['values'][0]);
        $this->assertSame(1, $likes['values'][6]);
        $this->assertSame(2, $response->viewData('threads')->first()->period_likes);
        $post->likers()->detach($actors[1]);
        $response = $this->get(route('insights.index', ['metric' => 'likes']))->assertOk();
        $this->assertSame(1, $response->viewData('insights')['metrics']['likes']['total']);
    }

    public function test_interactions_include_public_replies_comments_quotes_and_reposts_with_a_breakdown(): void
    {
        $owner = User::factory()->create();
        $actor = User::factory()->create();
        $post = Post::factory()->published()->create(['user_id' => $owner->id]);
        $post->likers()->attach($actor, ['created_at' => now()]);
        Post::factory()->published()->create(['user_id' => $actor->id, 'parent_id' => $post->id, 'type' => 'reply']);
        Post::factory()->published()->create(['user_id' => $actor->id, 'repost_of_id' => $post->id, 'type' => 'quote']);
        Post::factory()->published()->create(['user_id' => $actor->id, 'repost_of_id' => $post->id, 'type' => 'repost']);
        Post::factory()->published()->create(['user_id' => $owner->id, 'parent_id' => $post->id, 'type' => 'reply']);
        Post::factory()->create(['user_id' => $actor->id, 'parent_id' => $post->id, 'type' => 'reply']);
        Comment::factory()->create(['user_id' => $actor->id, 'post_id' => $post->id, 'status' => 'approved']);
        $hidden = Comment::factory()->create(['user_id' => $actor->id, 'post_id' => $post->id, 'status' => 'hidden']);
        Comment::factory()->create(['user_id' => $actor->id, 'post_id' => $post->id, 'parent_id' => $hidden->id, 'status' => 'approved']);
        $data = $this->actingAs($owner)->get(route('insights.index', ['metric' => 'interactions']))->assertOk()->viewData('insights');
        $this->assertSame(['likes' => 1, 'replies' => 1, 'reposts' => 2, 'comments' => 1], $data['breakdown']);
        $this->assertSame(5, $data['metrics']['interactions']['total']);
    }

    public function test_followers_show_current_retained_new_followers_and_no_invented_percentage(): void
    {
        $owner = User::factory()->create();
        $actors = User::factory()->count(4)->create();
        $owner->followers()->attach($actors[0], ['created_at' => now()]);
        $owner->followers()->attach($actors[1], ['created_at' => today()->subDays(7)]);
        $owner->followers()->attach($actors[2], ['created_at' => today()->subDays(40)]);
        $owner->followers()->attach($actors[3], ['created_at' => now()]);
        $owner->followers()->detach($actors[3]);
        $data = $this->actingAs($owner)->get(route('insights.index', ['metric' => 'followers']))->assertOk()->viewData('insights');
        $this->assertSame(3, $data['followers']);
        $this->assertSame(1, $data['metrics']['followers']['total']);
        $this->assertSame(1, $data['metrics']['followers']['previous']);
        $this->assertSame(0.0, $data['metrics']['followers']['change']);
        $this->assertNull($data['metrics']['likes']['change']);
    }

    public function test_private_foreign_future_and_deleted_posts_never_contribute_to_any_chart(): void
    {
        $owner = User::factory()->create();
        $actor = User::factory()->create();
        $hidden = [Post::factory()->create(['user_id' => $owner->id]), Post::factory()->scheduled()->create(['user_id' => $owner->id]), Post::factory()->published()->create(['user_id' => $owner->id, 'archived_at' => now()]), Post::factory()->published()->create(), Post::factory()->published()->create(['user_id' => $owner->id])];
        $hidden[4]->delete();
        foreach ($hidden as $post) {
            PostDailyView::factory()->create(['post_id' => $post->id, 'view_date' => today(), 'views' => 30]);
            $post->likers()->attach($actor, ['created_at' => now()]);
            Post::factory()->published()->create(['user_id' => $actor->id, 'parent_id' => $post->id, 'type' => 'reply']);
        }
        $data = $this->actingAs($owner)->get(route('insights.index', ['user_id' => $actor->id]))->assertOk()->viewData('insights');
        foreach (['views', 'likes', 'interactions'] as $key) {
            $this->assertSame(0, $data['metrics'][$key]['total']);
        }
        $this->assertSame(0, $data['posts']);
    }

    public function test_guest_access_invalid_filters_and_each_selected_chart_are_handled(): void
    {
        $this->get(route('insights.index'))->assertRedirect(route('login'));
        $this->actingAs(User::factory()->create());
        foreach ([0, 1, 8, 31, 365, -7, ['bad']] as $days) {
            $this->getJson(route('insights.index', ['days' => $days]))->assertUnprocessable()->assertJsonValidationErrors('days');
        }
        $this->getJson(route('insights.index', ['metric' => 'other']))->assertUnprocessable()->assertJsonValidationErrors('metric');
        foreach (['views', 'likes', 'followers', 'interactions'] as $key) {
            $html = $this->get(route('insights.index', ['days' => 30, 'metric' => $key]))->assertOk()->getContent();
            $this->assertSame(1, preg_match('~<section id="insight-panel-'.$key.'"[^>]*>~', $html, $matches));
            $this->assertStringNotContainsString(' hidden', $matches[0]);
            $this->assertSame(4, substr_count($html, 'data-insight-metric="'));
            $this->assertSame(8, substr_count($html, 'role="img"'));
            $this->assertStringNotContainsString('NaN', $html);
            $this->assertStringContainsString('Xem dữ liệu theo ngày', $html);
        }
    }

    public function test_post_ranking_and_pagination_use_period_counts_and_preserve_filters(): void
    {
        $owner = User::factory()->create();
        $posts = Post::factory()->published()->count(11)->create(['user_id' => $owner->id, 'views_count' => 5000]);
        PostDailyView::factory()->create(['post_id' => $posts[5]->id, 'view_date' => today(), 'views' => 50]);
        PostDailyView::factory()->create(['post_id' => $posts[0]->id, 'view_date' => today()->subDays(50), 'views' => 9999]);
        $page = $this->actingAs($owner)->get(route('insights.index', ['days' => 30, 'metric' => 'likes']))->assertOk()->viewData('threads');
        $this->assertSame($posts[5]->id, $page->first()->id);
        $this->assertSame(50, (int) $page->first()->period_views);
        $this->assertStringContainsString('days=30', $page->nextPageUrl());
        $this->assertStringContainsString('metric=likes', $page->nextPageUrl());
        $this->get(route('insights.index', ['days' => 30, 'metric' => 'likes', 'page' => 2]))->assertOk()->assertViewHas('threads', fn ($threads): bool => $threads->count() === 1);
    }

    public function test_thread_views_use_the_daily_recorder_and_do_not_count_owner_or_admin(): void
    {
        $post = Post::factory()->published()->create(['slug' => 'thread-'.Str::uuid()]);
        $this->get(route('threads.show', $post))->assertOk();
        $this->get(route('threads.show', $post))->assertOk();
        $this->get(route('posts.show', $post->slug))->assertOk();
        $this->assertSame(1, $post->fresh()->views_count);
        $this->assertDatabaseHas('post_daily_views', ['post_id' => $post->id, 'view_date' => today()->toDateString(), 'views' => 1]);
        $this->travel(1)->days();
        $this->get(route('threads.show', $post))->assertOk();
        $this->assertSame(2, $post->fresh()->views_count);
        $this->actingAs($post->author)->get(route('threads.show', $post))->assertOk();
        $this->actingAs(User::factory()->create(['role' => 'admin']))->get(route('threads.show', $post))->assertOk();
        $this->assertSame(2, $post->fresh()->views_count);
    }

    public function test_print_report_includes_identity_period_and_all_charts_regardless_of_selected_metric(): void
    {
        $owner = User::factory()->create(['name' => 'Nguyễn Minh Anh', 'username' => 'minh_anh']);
        $actor = User::factory()->create();
        $post = Post::factory()->published()->create(['user_id' => $owner->id]);
        foreach (range(0, 89) as $day) {
            PostDailyView::factory()->create(['post_id' => $post->id, 'view_date' => today()->subDays($day), 'views' => ($day * 7) % 19 + 1]);
        }
        $post->likers()->attach($actor, ['created_at' => now()]);
        $owner->followers()->attach($actor, ['created_at' => now()->subDays(2)]);
        $this->actingAs($owner);
        foreach ([7, 30, 90] as $days) {
            $html = $this->get(route('insights.index', ['days' => $days, 'metric' => 'interactions']))->assertOk()->getContent();
            $this->assertSame(1, preg_match('~<article class="insights-print-report"[^>]*>(.*?)</article>~s', $html, $matches));
            $report = $matches[1];
            $this->assertStringContainsString('Nguyễn Minh Anh', $report);
            $this->assertStringContainsString('@minh_anh', $report);
            $this->assertStringContainsString(today()->subDays($days - 1)->format('d/m/Y').' - '.today()->format('d/m/Y'), $report);
            $this->assertSame(4, substr_count($report, 'role="img"'));
            $this->assertStringNotContainsString('data-insight-panel', $report);
            $this->assertStringNotContainsString('Bài viết của bạn', $report);
            $this->assertStringNotContainsString('giới tính', $report);
            $this->assertStringNotContainsString('@else', $report);
            $this->assertStringContainsString('Phạm vi thống kê', $report);
            if ($days === 90 && getenv('THREADS_INSIGHTS_PREVIEW')) {
                file_put_contents(storage_path('framework/testing/insights-report-preview.html'), $html);
            }
        }
    }
}

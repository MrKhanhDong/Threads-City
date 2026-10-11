<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\Post;
use App\Models\PostDailyView;
use App\Services\BlogAnalytics;
use Tests\TestCase;

class AnalyticsTest extends TestCase
{
    public function test_public_views_increment_daily_and_lifetime_counts_only_once_per_session_per_day(): void
    {
        $post=$this->published();
        $this->get(route('posts.show',$post->slug))->assertOk();
        $this->get(route('posts.show',$post->slug))->assertOk();
        $this->assertDatabaseHas('post_daily_views',['post_id'=>$post->id,'view_date'=>now()->toDateString(),'views'=>1]);
        $this->assertSame(1,(int)$post->fresh()->views_count);
        $this->assertSame(1,PostDailyView::count());
        $this->flushSession();
        $this->get(route('posts.show',$post->slug))->assertOk();
        $this->assertDatabaseHas('post_daily_views',['post_id'=>$post->id,'views'=>2]);
        $this->assertSame(2,(int)$post->fresh()->views_count);
    }

    public function test_same_reader_session_is_counted_again_after_vietnam_midnight(): void
    {
        $post=$this->published();
        $this->travelTo(now()->startOfDay()->addHours(23)->addMinutes(59));
        $firstDay=now()->toDateString();
        $this->get(route('posts.show',$post->slug))->assertOk();
        $this->travel(2)->minutes();
        $secondDay=now()->toDateString();
        $this->assertNotSame($firstDay,$secondDay);
        $this->get(route('posts.show',$post->slug))->assertOk();
        $this->assertDatabaseHas('post_daily_views',['post_id'=>$post->id,'view_date'=>$firstDay,'views'=>1]);
        $this->assertDatabaseHas('post_daily_views',['post_id'=>$post->id,'view_date'=>$secondDay,'views'=>1]);
        $this->assertSame(2,(int)$post->fresh()->views_count);
    }

    public function test_admin_public_read_and_preview_are_not_recorded(): void
    {
        $post=$this->published();
        $this->actingAs($this->admin())->get(route('posts.show',$post->slug))->assertOk();
        $this->get(route('admin.posts.preview',$post))->assertOk();
        $this->assertSame(0,PostDailyView::count());
        $this->assertSame(0,(int)$post->fresh()->views_count);
    }

    public function test_hidden_and_trashed_posts_do_not_record_views(): void
    {
        $post=$this->published();
        $post->update(['status'=>'draft']);
        $this->get(route('posts.show',$post->slug))->assertNotFound();
        $post->update(['status'=>'published']); $post->delete();
        $this->get(route('posts.show',$post->slug))->assertNotFound();
        $this->assertSame(0,PostDailyView::count());
    }

    public function test_chart_fills_missing_days_with_zero_and_applies_inclusive_range(): void
    {
        $post=$this->published();
        $today=now()->startOfDay();
        foreach([0=>3,2=>5,6=>2,7=>99] as $ago=>$views) PostDailyView::create(['post_id'=>$post->id,'view_date'=>$today->copy()->subDays($ago)->toDateString(),'views'=>$views]);
        $this->actingAs($this->admin())->get('/admin?range=7')->assertOk()->assertViewHas('analytics',function($a) {
            return $a['daily']->count()===7 && $a['total']===10 && $a['today']===3 && $a['daily'][1]['views']===0 && $a['peak']['views']===5 && $a['topPosts']->first()->period_views==10;
        })->assertSee('data-views-chart',false)->assertSee('Xem bảng số liệu 7 ngày');
    }

    public function test_all_three_ranges_render_with_matching_day_counts(): void
    {
        $this->actingAs($this->admin());
        foreach([7,30,90] as $days) $this->get('/admin?range='.$days)->assertOk()->assertViewHas('analytics',fn($a)=>$a['days']===$days && $a['daily']->count()===$days && $a['total']===0);
        $this->get('/admin')->assertViewHas('analytics',fn($a)=>$a['days']===30);
    }

    public function test_analytics_rejects_invalid_ranges_and_array_inputs(): void
    {
        $this->actingAs($this->admin());
        foreach(['0','365','-1','bad','7.5'] as $range) $this->getJson('/admin?range='.$range)->assertUnprocessable()->assertJsonValidationErrors('range');
        $this->getJson('/admin?range[]=7')->assertUnprocessable();
    }

    public function test_top_posts_are_ranked_in_period_not_by_old_lifetime_total(): void
    {
        $posts=Post::published()->take(3)->get();
        $posts[0]->forceFill(['views_count'=>999])->save();
        PostDailyView::create(['post_id'=>$posts[0]->id,'view_date'=>now()->toDateString(),'views'=>2]);
        PostDailyView::create(['post_id'=>$posts[1]->id,'view_date'=>now()->toDateString(),'views'=>7]);
        PostDailyView::create(['post_id'=>$posts[2]->id,'view_date'=>now()->subDays(60)->toDateString(),'views'=>100]);
        $report=app(BlogAnalytics::class)->overview(30);
        $this->assertSame([$posts[1]->id,$posts[0]->id],$report['topPosts']->pluck('id')->all());
        $this->assertSame(9,$report['total']);
    }

    public function test_trash_is_excluded_from_chart_ranking_and_pending_but_restore_recovers_history(): void
    {
        $post=$this->published();
        PostDailyView::create(['post_id'=>$post->id,'view_date'=>now()->toDateString(),'views'=>10]);
        $existingPending = Comment::where('status', 'pending')->where('post_id', $post->id)->count();
        $comment=Comment::create(['post_id'=>$post->id,'user_id'=>$this->reader()->id,'body'=>'History pending','status'=>'pending']);
        $before=app(BlogAnalytics::class)->overview(7);
        $post->delete();
        $report=app(BlogAnalytics::class)->overview(7);
        $this->assertSame(0,$report['total']);
        $this->assertTrue($report['topPosts']->isEmpty());
        $this->assertSame($before['pendingCount']-1-$existingPending,$report['pendingCount']);
        $this->assertFalse($report['pendingComments']->contains('id',$comment->id));
        $this->actingAs($this->admin())->patch(route('admin.posts.restore',$post->id))->assertRedirect();
        $restored=app(BlogAnalytics::class)->overview(7);
        $this->assertSame(10,$restored['total']);
        $this->assertSame($before['pendingCount'],$restored['pendingCount']);
    }

    public function test_upgrade_does_not_invent_daily_dates_for_old_lifetime_views(): void
    {
        $post=$this->published(); $post->forceFill(['views_count'=>100])->save();
        $this->assertSame(0,app(BlogAnalytics::class)->overview(30)['total']);
        $this->get(route('posts.show',$post->slug))->assertOk();
        $this->assertSame(101,(int)$post->fresh()->views_count);
        $this->assertSame(1,app(BlogAnalytics::class)->overview(30)['total']);
    }

    public function test_dashboard_empty_state_and_comment_content_are_safe(): void
    {
        $this->actingAs($this->admin())->get('/admin')->assertOk()->assertSee('Chưa có lượt xem trong kỳ')->assertSee('Chưa có bài được đọc');
        $post=$this->published();
        Comment::create(['post_id'=>$post->id,'user_id'=>$this->reader()->id,'body'=>'<script>dashboardAttack()</script>','status'=>'pending']);
        $this->get('/admin')->assertOk()->assertSee('&lt;script&gt;dashboardAttack()',false)->assertDontSee('<script>dashboardAttack()',false);
    }
}

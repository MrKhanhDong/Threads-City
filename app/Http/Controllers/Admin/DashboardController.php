<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\CommentReport;
use App\Models\ContentReport;
use App\Models\Post;
use App\Models\PostDailyView;
use App\Models\User;
use Carbon\CarbonPeriod;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $data = $request->validate(['days' => 'nullable|integer|in:7,30,90']);
        $days = (int) ($data['days'] ?? 7);
        $start = now()->startOfDay()->subDays($days - 1);
        $end = now()->startOfDay();
        $daily = PostDailyView::whereBetween('view_date', [$start->toDateString(), $end->toDateString()])->selectRaw('view_date, SUM(views) AS total')->groupBy('view_date')->pluck('total', 'view_date');
        $chart = [];
        foreach (CarbonPeriod::create($start, $end) as $date) {
            $chart[] = ['date' => $date->format('d/m'), 'views' => (int) ($daily[$date->toDateString()] ?? 0)];
        }
        $counts = [
            'users' => User::count(),
            'new_users' => User::where('created_at', '>=', $start)->count(),
            'locked_users' => User::where('is_locked', true)->where(fn (Builder $users): Builder => $users->whereNull('lock_expires_at')->orWhere('lock_expires_at', '>', now()))->count(),
            'threads' => Post::whereNull('parent_id')->where('type', '!=', 'repost')->count(),
            'replies' => Post::whereNotNull('parent_id')->count(),
            'hidden' => Post::onlyTrashed()->whereNotNull('moderation_hidden_at')->count(),
            'content_reports' => ContentReport::where('status', 'pending')->count(),
            'total' => Post::count(),
            'published' => Post::published()->count(),
            'drafts' => Post::where('status', 'draft')->count(),
            'readers' => User::where('role', 'reader')->count(),
            'comments' => Comment::whereHas('post')->count(),
            'pending_comments' => Comment::whereHas('post')->where('status', 'pending')->count(),
            'reports' => CommentReport::where('resolved', false)->whereHas('comment.post')->count() + ContentReport::where('status', 'pending')->count(),
            'trash' => Post::onlyTrashed()->count(),
        ];
        $topPosts = Post::withSum(['dailyViews as period_views' => fn (Builder $query): Builder => $query->whereBetween('view_date', [$start->toDateString(), $end->toDateString()])], 'views')->having('period_views', '>', 0)->orderByDesc('period_views')->limit(5)->get();

        return view('admin.dashboard', ['counts' => $counts, 'chart' => $chart, 'days' => $days, 'topPosts' => $topPosts, 'latestPosts' => Post::latest('updated_at')->limit(5)->get()]);
    }
}

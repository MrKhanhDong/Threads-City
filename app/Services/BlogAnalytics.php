<?php

namespace App\Services;

use App\Models\Comment;
use App\Models\Post;
use App\Models\PostDailyView;
use Carbon\CarbonImmutable;

class BlogAnalytics
{
    public function overview(int $days): array
    {
        if (!in_array($days, [7, 30, 90], true)) throw new \InvalidArgumentException('Invalid analytics range.');
        $end = CarbonImmutable::now(config('app.timezone'))->startOfDay();
        $start = $end->subDays($days - 1);
        $from = $start->toDateString();
        $to = $end->toDateString();

        $counts = PostDailyView::whereHas('post')->whereBetween('view_date', [$from, $to])
            ->selectRaw('view_date, SUM(views) AS total')->groupBy('view_date')->toBase()->pluck('total', 'view_date');
        $daily = collect(range(0, $days - 1))->map(function ($offset) use ($start, $counts) {
            $date = $start->addDays($offset);
            return ['date' => $date->toDateString(), 'label' => $date->format('d/m'),
                'full_label' => $date->format('d/m/Y'), 'views' => (int)($counts[$date->toDateString()] ?? 0)];
        });
        $maximum = max(4, (int)(ceil($daily->max('views') / 4) * 4));
        $points = $daily->map(fn ($row, $index) => [
            ...$row, 'x' => (int)round(52 + $index * 790 / ($days - 1)),
            'y' => (int)round(178 - $row['views'] * 150 / $maximum),
        ]);
        $ticks = collect([0, 0.25, 0.5, 0.75, 1])->map(fn ($part) => (int)round(($days - 1) * $part))->unique()
            ->map(fn ($index) => $points[$index])->values();
        $peak = $daily->sortByDesc('views')->first();
        $topPosts = Post::with('category')->withSum([
            'dailyViews as period_views' => fn ($q) => $q->whereBetween('view_date', [$from, $to]),
        ], 'views')->orderByDesc('period_views')->orderBy('id')->limit(5)->get()
            ->filter(fn ($post) => (int)$post->period_views > 0)->values();
        $pendingQuery = Comment::where('status', 'pending')->whereHas('post');

        return [
            'days' => $days, 'from' => $start->format('d/m/Y'), 'to' => $end->format('d/m/Y'),
            'daily' => $daily, 'points' => $points, 'ticks' => $ticks, 'maximum' => $maximum,
            'line' => $points->map(fn ($point) => $point['x'].','.$point['y'])->implode(' '),
            'total' => (int)$daily->sum('views'), 'today' => $daily->last()['views'],
            'average' => round($daily->sum('views') / $days, 1),
            'peak' => $peak['views'] > 0 ? $peak : null, 'topPosts' => $topPosts,
            'pendingCount' => (clone $pendingQuery)->count(),
            'pendingComments' => $pendingQuery->with(['post', 'user'])->latest()->take(5)->get(),
        ];
    }
}

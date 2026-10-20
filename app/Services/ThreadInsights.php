<?php

namespace App\Services;

use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class ThreadInsights
{
    /** @return array<string, mixed> */
    public function summarize(User $user, int $days): array
    {
        $end = CarbonImmutable::now();
        $start = $end->startOfDay()->subDays($days - 1);
        $previousStart = $start->subDays($days);
        $owned = $this->ownedPosts($user)->select('posts.id');
        $views = DB::table('post_daily_views')->whereIn('post_id', clone $owned)
            ->whereBetween('view_date', [$previousStart->toDateString(), $end->toDateString()])
            ->selectRaw('view_date as day, SUM(views) as total')->groupBy('view_date')->pluck('total', 'day')->all();
        $likes = DB::table('post_likes')->whereIn('post_id', clone $owned)->where('user_id', '!=', $user->id)
            ->whereBetween('created_at', [$previousStart, $end])->selectRaw('DATE(created_at) as day, COUNT(*) as total')->groupByRaw('DATE(created_at)')->pluck('total', 'day')->all();
        $replies = Post::published()->whereIn('parent_id', clone $owned)->where('user_id', '!=', $user->id)
            ->whereBetween('published_at', [$previousStart, $end])->selectRaw('DATE(published_at) as day, COUNT(*) as total')->groupByRaw('DATE(published_at)')->pluck('total', 'day')->all();
        $reposts = Post::published()->whereIn('repost_of_id', clone $owned)->whereIn('type', ['repost', 'quote'])->where('user_id', '!=', $user->id)
            ->whereBetween('published_at', [$previousStart, $end])->selectRaw('DATE(published_at) as day, COUNT(*) as total')->groupByRaw('DATE(published_at)')->pluck('total', 'day')->all();
        $comments = Comment::whereIn('post_id', clone $owned)->whereIn('id', app(CommentThreads::class)->visible()->select('id'))->where('user_id', '!=', $user->id)
            ->whereBetween('created_at', [$previousStart, $end])->selectRaw('DATE(created_at) as day, COUNT(*) as total')->groupByRaw('DATE(created_at)')->pluck('total', 'day')->all();
        $followers = DB::table('follows')->where('following_id', $user->id)->whereBetween('created_at', [$previousStart, $end])
            ->selectRaw('DATE(created_at) as day, COUNT(*) as total')->groupByRaw('DATE(created_at)')->pluck('total', 'day')->all();
        $maps = compact('views', 'likes', 'replies', 'reposts', 'comments', 'followers');
        $dates = [];
        $series = [];
        $previous = [];
        foreach ($maps as $key => $map) {
            $series[$key] = [];
            $previous[$key] = [];
            for ($index = 0; $index < $days; $index++) {
                $date = $start->addDays($index)->toDateString();
                $dates[$index] = $date;
                $series[$key][] = (int) ($map[$date] ?? 0);
                $previous[$key][] = (int) ($map[$previousStart->addDays($index)->toDateString()] ?? 0);
            }
        }
        $series['interactions'] = $previous['interactions'] = array_fill(0, $days, 0);
        foreach (['likes', 'replies', 'reposts', 'comments'] as $key) {
            foreach ($series[$key] as $index => $value) {
                $series['interactions'][$index] += $value;
                $previous['interactions'][$index] += $previous[$key][$index];
            }
        }
        $metrics = [];
        foreach (['views' => 'Lượt xem', 'likes' => 'Lượt thích', 'followers' => 'Người theo dõi mới', 'interactions' => 'Lượt tương tác'] as $key => $label) {
            $total = array_sum($series[$key]);
            $previousTotal = array_sum($previous[$key]);
            $metrics[$key] = ['label' => $label, 'total' => $total, 'previous' => $previousTotal, 'change' => $previousTotal > 0 ? round(($total - $previousTotal) / $previousTotal * 100, 1) : null, 'values' => $series[$key], 'chart' => $this->chart($series[$key])];
        }
        $breakdown = [];
        foreach (['likes', 'replies', 'reposts', 'comments'] as $key) {
            $breakdown[$key] = array_sum($series[$key]);
        }

        return ['days' => $days, 'start' => $start, 'end' => $end, 'previous_start' => $previousStart, 'previous_end' => $start->subDay(), 'dates' => $dates, 'metrics' => $metrics, 'breakdown' => $breakdown,
            'followers' => $user->followers()->count(), 'following' => $user->following()->count(), 'posts' => $this->ownedPosts($user)->whereBetween('published_at', [$start, $end])->count()];
    }

    public function postsInPeriod(User $user, CarbonImmutable $start, CarbonImmutable $end): Builder
    {
        return $this->ownedPosts($user)->select(['posts.id', 'posts.title', 'posts.slug', 'posts.published_at'])
            ->withSum(['dailyViews as period_views' => fn (Builder $views): Builder => $views->whereBetween('view_date', [$start->toDateString(), $end->toDateString()])], 'views')
            ->withCount([
                'likers as period_likes' => fn (Builder $likers): Builder => $likers->where('users.id', '!=', $user->id)->whereBetween('post_likes.created_at', [$start, $end]),
                'replies as period_replies' => fn (Builder $replies): Builder => $replies->where('user_id', '!=', $user->id)->whereBetween('published_at', [$start, $end]),
                'reposts as period_reposts' => fn (Builder $reposts): Builder => $reposts->published()->where('user_id', '!=', $user->id)->whereBetween('published_at', [$start, $end]),
                'comments as period_comments' => fn (Builder $comments): Builder => $comments->where('user_id', '!=', $user->id)->whereIn('id', app(CommentThreads::class)->visible()->select('id'))->whereBetween('created_at', [$start, $end]),
            ]);
    }

    private function ownedPosts(User $user): Builder
    {
        return Post::published()->where('user_id', $user->id)->whereNull('parent_id')->where('type', '!=', 'repost')
            ->where(fn (Builder $posts): Builder => $posts->whereNull('repost_of_id')->orWhereHas('repostOf', fn (Builder $original): Builder => $original->published()));
    }

    /** @param array<int, int> $values
     * @return array{line: string, area: string, points: array<int, array{x: float, y: float}>, ticks: array<int, array{y: float, value: int, label: string}>, maximum: int}
     */
    private function chart(array $values): array
    {
        $rawStep = max(1, max($values) / 4);
        $magnitude = 10 ** floor(log10($rawStep));
        $step = 10 * $magnitude;
        foreach ([1, 2, 5, 10] as $factor) {
            if ($factor * $magnitude >= $rawStep) {
                $step = $factor * $magnitude;
                break;
            }
        }
        $maximum = (int) ($step * 4);
        $points = [];
        foreach ($values as $index => $value) {
            $points[] = ['x' => round(45 + $index * 620 / (count($values) - 1), 2), 'y' => round(190 - $value * 170 / $maximum, 2)];
        }
        $line = implode(' ', array_map(fn (array $point, int $index): string => ($index === 0 ? 'M' : 'L').$point['x'].','.$point['y'], $points, array_keys($points)));
        $ticks = [];
        for ($index = 0; $index <= 4; $index++) {
            $value = (int) ($index * $step);
            $label = $value >= 1000000 ? round($value / 1000000, 1).'tr' : ($value >= 1000 ? round($value / 1000, 1).'k' : (string) $value);
            $ticks[] = ['y' => (float) (190 - $index * 42.5), 'value' => $value, 'label' => $label];
        }

        return ['line' => $line, 'area' => $line.' L665,190 L45,190 Z', 'points' => $points, 'ticks' => $ticks, 'maximum' => $maximum];
    }
}

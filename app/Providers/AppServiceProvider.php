<?php

namespace App\Providers;

use App\Models\Category;
use App\Models\Setting;
use App\Models\Tag;
use App\Models\TrendingTopic;
use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->scoped('blog.context', fn (): array => [
            'site' => array_merge(['site_name' => config('blog.name'), 'site_tagline' => config('blog.tagline'), 'site_description' => config('blog.description'), 'about' => '', 'contact_email' => ''], Setting::pluck('value', 'key')->all()),
            'navigationCategories' => Category::withCount(['posts' => fn (Builder $query): Builder => $query->published()])->orderBy('name')->get(),
        ]);
        $this->app->scoped('blog.saved', fn (): array => request()->user()?->bookmarks()->pluck('posts.id')->all() ?? []);
    }

    public function boot(): void
    {
        if ($this->app->environment('production') || isset($_ENV['VERCEL']) || isset($_SERVER['VERCEL'])) {
            URL::forceScheme('https');
        }

        Paginator::useBootstrapFive();
        View::composer(['layouts.admin', 'admin.*'], function (\Illuminate\View\View $view): void {
            $view->with(app('blog.context'))->with('savedIds', app('blog.saved'));
        });

        View::composer('layouts.threads', function (\Illuminate\View\View $view): void {
            if (request()->header('X-Threads-Live') === '1') {
                $view->with(['suggestedUsers' => collect(), 'trendingTopics' => collect(), 'sidebarFollowingIds' => []]);

                return;
            }

            $currentUserId = auth()->id();

            $suggestedUsers = Cache::remember('threads_suggested_users_v4', 300, function (): array {
                return User::query()
                    ->whereIn('username', ['binhphatlien', 'dieu_nhiii', 'mrkhanhdong', 'minhphuong'])
                    ->orderByRaw("FIELD(username, 'binhphatlien', 'dieu_nhiii', 'mrkhanhdong', 'minhphuong')")
                    ->take(4)
                    ->get()
                    ->map(fn (User $u): array => [
                        'id' => $u->id,
                        'name' => $u->name,
                        'username' => $u->username,
                        'bio' => $u->bio,
                        'avatar_path' => $u->avatar_path,
                        'is_admin' => $u->isAdmin(),
                    ])
                    ->all();
            });

            // If logged in, filter out the current user from suggested list in-memory without extra DB query
            if ($currentUserId) {
                $suggestedUsers = array_values(array_filter($suggestedUsers, fn ($u): bool => (int) data_get($u, 'id') !== (int) $currentUserId));
            }

            // Cache trending topics for 5 minutes
            $trendingTopics = Cache::remember('threads_trending_topics_v4', 300, function (): array {
                $topics = TrendingTopic::query()
                    ->active()
                    ->orderBy('order')
                    ->orderByDesc('id')
                    ->take(6)
                    ->get();

                if ($topics->isEmpty()) {
                    $topTags = Tag::withCount('posts')
                        ->has('posts')
                        ->orderByDesc('posts_count')
                        ->take(4)
                        ->get();

                    return $topTags->map(fn (Tag $t): array => [
                        'tag' => $t->name,
                        'title' => '#'.$t->name,
                        'desc' => 'Chủ đề đang được thảo luận sôi nổi trên Threads',
                        'count' => $t->posts_count.' bài viết',
                        'display_count' => (string) $t->posts_count,
                    ])->all();
                }

                return $topics->map(fn (TrendingTopic $t): array => [
                    'tag' => $t->tag ?? $t->title,
                    'title' => $t->title,
                    'desc' => $t->desc ?? '',
                    'count' => $t->count ?? '',
                    'display_count' => $t->display_count ?? (string) $t->count,
                ])->all();
            });

            $sidebarFollowingIds = $currentUserId
                ? DB::table('follows')->where('follower_id', $currentUserId)->pluck('following_id')->flip()->toArray()
                : [];

            $view->with([
                'suggestedUsers' => $suggestedUsers,
                'trendingTopics' => $trendingTopics,
                'sidebarFollowingIds' => $sidebarFollowingIds,
            ]);
        });
        RateLimiter::for('login', function (Request $request): Limit {
            $email = $request->input('email');

            return Limit::perMinute(5)->by($request->ip().':'.(is_string($email) ? mb_strtolower($email) : 'invalid'));
        });
        foreach (['register' => 5, 'password-reset' => 3, 'comments' => 5, 'uploads' => 30] as $name => $attempts) {
            RateLimiter::for($name, fn (Request $request): Limit => Limit::perMinute($attempts)->by(($request->user()?->id ?? $request->ip()).':'.$name));
        }
    }
}

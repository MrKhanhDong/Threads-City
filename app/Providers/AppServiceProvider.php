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
                $view->with(['suggestedUsers' => collect(), 'trendingTopics' => collect()]);

                return;
            }

            $currentUserId = auth()->id();
            $suggestedUsers = User::query()
                ->when($currentUserId, fn ($q) => $q->where('id', '!=', $currentUserId))
                ->whereIn('username', ['binhphatlien', 'dieu_nhiii', 'mrkhanhdong', 'minhphuong'])
                ->orderByRaw("FIELD(username, 'binhphatlien', 'dieu_nhiii', 'mrkhanhdong', 'minhphuong')")
                ->take(4)
                ->get();

            // Fetch trending topics directly from database
            $trendingTopics = TrendingTopic::query()
                ->active()
                ->orderBy('order')
                ->orderByDesc('id')
                ->take(6)
                ->get();

            if ($trendingTopics->isEmpty()) {
                $topTags = Tag::withCount('posts')
                    ->has('posts')
                    ->orderByDesc('posts_count')
                    ->take(4)
                    ->get();

                $trendingTopics = $topTags->map(function (Tag $t) {
                    return (object) [
                        'tag' => $t->name,
                        'title' => '#'.$t->name,
                        'desc' => 'Chủ đề đang được thảo luận sôi nổi trên Threads',
                        'count' => $t->posts_count.' bài viết',
                        'display_count' => (string) $t->posts_count,
                    ];
                });
            }

            $view->with([
                'suggestedUsers' => $suggestedUsers,
                'trendingTopics' => $trendingTopics,
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

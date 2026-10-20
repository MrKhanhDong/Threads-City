<?php

namespace App\Providers;

use App\Models\Category;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->scoped('blog.context', fn (): array => [
            'site' => array_merge(['site_name' => config('blog.name'), 'site_tagline' => config('blog.tagline'), 'site_description' => config('blog.description'), 'about' => config('blog.about'), 'contact_email' => ''], Setting::pluck('value', 'key')->all()),
            'navigationCategories' => Category::withCount(['posts' => fn (Builder $query): Builder => $query->published()])->orderBy('name')->get(),
        ]);
        $this->app->scoped('blog.saved', fn (): array => request()->user()?->bookmarks()->pluck('posts.id')->all() ?? []);
    }

    public function boot(): void
    {
        Paginator::useBootstrapFive();
        View::composer(['layouts.*', 'blog.*', 'auth.*', 'account.*', 'admin.*', 'components.*'], function (\Illuminate\View\View $view): void {
            $view->with(app('blog.context'))->with('savedIds', app('blog.saved'));
        });

<<<<<<< HEAD
        View::composer(['layouts.threads', 'threads.*', 'profile.*'], function (\Illuminate\View\View $view): void {
            $currentUserId = auth()->id();
            $suggestedUsers = User::query()
                ->when($currentUserId, fn ($q) => $q->where('id', '!=', $currentUserId))
                ->whereIn('username', ['binhphatlien', 'dieu_nhiii', 'mrkhanhdong', 'minhphuong'])
                ->orderByRaw("FIELD(username, 'binhphatlien', 'dieu_nhiii', 'mrkhanhdong', 'minhphuong')")
                ->take(4)
                ->get();

            $trendingTopics = collect([
                ['tag' => 'GMMTV', 'title' => 'JoongDunk và AouBoom dừng diễn cặp', 'desc' => 'GMMTV thông báo JoongDunk và AouBoom thống nhất dừng hoạt động với...', 'count' => '5K'],
                ['tag' => 'KOSMIK', 'title' => 'KOSMIK Concert 2026 mở bán vé', 'desc' => 'Sự kiện KOSMIK Live Concert 2026 mở bán vé cho đêm nhạc ngày 12/12/2026 tại Hà...', 'count' => '1K'],
                ['tag' => 'Biểu tình', 'title' => 'Biểu tình ở Pháp thành bạo loạn', 'desc' => 'Nhiều đối tượng quá khích đập phá, phóng hỏa cơ sở kinh doanh ở thủ đô Paris...', 'count' => '3K'],
                ['tag' => 'SayHi', 'title' => 'IVAN biểu diễn tại Tinh Hà Say Hi', 'desc' => 'Người hâm mộ bênh vực IVAN sau màn trình diễn tại hai đêm concert Tinh Hà Say...', 'count' => '6K'],
            ]);

            $view->with([
                'suggestedUsers' => $suggestedUsers,
                'trendingTopics' => $trendingTopics,
            ]);
        });
=======
>>>>>>> Phuong
        RateLimiter::for('login', function (Request $request): Limit {
            $email = $request->input('email');

            return Limit::perMinute(5)->by($request->ip().':'.(is_string($email) ? mb_strtolower($email) : 'invalid'));
        });
        foreach (['register' => 5, 'password-reset' => 3, 'comments' => 5, 'uploads' => 30] as $name => $attempts) {
            RateLimiter::for($name, fn (Request $request): Limit => Limit::perMinute($attempts)->by(($request->user()?->id ?? $request->ip()).':'.$name));
        }
    }
}

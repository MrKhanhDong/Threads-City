<?php

namespace App\Providers;

use App\Models\Category;
use App\Models\Setting;
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
            'navigationCategories' => Category::whereHas('posts', fn (Builder $query): Builder => $query->published()->visibleTo(request()->user()))
                ->withCount(['posts' => fn (Builder $query): Builder => $query->published()->visibleTo(request()->user())])->orderBy('name')->get(),
        ]);
        $this->app->scoped('blog.saved', fn (): array => request()->user()?->bookmarks()->pluck('posts.id')->all() ?? []);
    }

    public function boot(): void
    {
        Paginator::useBootstrapFive();
        View::composer(['layouts.*', 'blog.*', 'auth.*', 'account.*', 'admin.*', 'components.*'], function (\Illuminate\View\View $view): void {
            $view->with(app('blog.context'))->with('savedIds', app('blog.saved'));
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

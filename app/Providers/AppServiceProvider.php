<?php

namespace App\Providers;

use App\Models\Category;
use App\Models\Setting;
use App\Models\User;
use App\Services\PushSubscriptions;
use App\Services\SendWebPush;
use GuzzleHttp\Client;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Notifications\Events\NotificationSent;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Minishlink\WebPush\WebPush;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(WebPush::class, function (): WebPush {
            return new WebPush([
                'VAPID' => ['subject' => config('services.webpush.subject'), 'publicKey' => config('services.webpush.public_key'), 'privateKey' => config('services.webpush.private_key')],
            ], ['TTL' => 300], new Client(['timeout' => 10, 'connect_timeout' => 5, 'allow_redirects' => false]));
        });
        $this->app->scoped('blog.context', fn (): array => [
            'site' => array_merge(['site_name' => config('blog.name'), 'site_tagline' => config('blog.tagline'), 'site_description' => config('blog.description'), 'about' => config('blog.about'), 'contact_email' => ''], Setting::pluck('value', 'key')->all()),
            'navigationCategories' => Category::whereHas('posts', fn (Builder $query): Builder => $query->published()->visibleTo(request()->user()))
                ->withCount(['posts' => fn (Builder $query): Builder => $query->published()->visibleTo(request()->user())])->orderBy('name')->get(),
        ]);
        $this->app->scoped('blog.saved', fn (): array => request()->user()?->bookmarks()->pluck('posts.id')->all() ?? []);
    }

    public function boot(): void
    {
        Event::listen(NotificationSent::class, function (NotificationSent $event): void {
            if ($event->channel !== 'database' || ! $event->notifiable instanceof User || ! app(PushSubscriptions::class)->configured()) {
                return;
            }
            $userId = $event->notifiable->id;
            $notificationId = $event->notification->id;
            DB::afterCommit(function () use ($userId, $notificationId): void {
                rescue(fn () => Bus::dispatch(new SendWebPush($userId, $notificationId)));
            });
        });
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

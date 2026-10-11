<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        \Illuminate\Support\Facades\View::composer(['blog.*', 'admin.*', 'auth.*', 'account.*'], function ($view) {
            // Invalid array-shaped form inputs should produce validation errors,
            // not a second rendering error when Blade echoes old input.
            $old = request()->session()->getOldInput();
            $safe = array_filter($old, fn ($value) => is_scalar($value) || $value === null);
            if (isset($old['tags']) && is_array($old['tags'])) {
                $safe['tags'] = array_values(array_filter($old['tags'], 'is_scalar'));
            } else {
                unset($safe['tags']);
            }
            request()->session()->put('_old_input', $safe);
            $view->with(app(\App\Repositories\BlogRepository::class)->shared());
        });
    }
}

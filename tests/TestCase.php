<?php

namespace Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    protected $seed = true;

    // Each actingAs call switches test identities, unlike a continuing browser
    // session. Clear only the old identity's auth.session password snapshot.
    public function actingAs(\Illuminate\Contracts\Auth\Authenticatable $user, $guard = null)
    {
        $this->app['session']->forget('password_hash_'.($guard ?? 'web'));
        return parent::actingAs($user, $guard);
    }

    protected function beforeRefreshingDatabase()
    {
        $driver = config('database.default');
        $database = config("database.connections.{$driver}.database");
        $safe = ($driver === 'sqlite' && $database === ':memory:')
            || ($driver === 'mysql' && $database === 'threads_city_blog_test');
        if (!$this->app->environment('testing') || !$safe || config("database.connections.{$driver}.url")) {
            throw new RuntimeException('Refusing to refresh a non-test database. Use SQLite :memory: or threads_city_blog_test only.');
        }
    }

    protected function admin(): \App\Models\User { return \App\Models\User::where('role', 'admin')->firstOrFail(); }
    protected function reader(): \App\Models\User { return \App\Models\User::where('role', 'reader')->firstOrFail(); }
    protected function published(): \App\Models\Post { return \App\Models\Post::published()->firstOrFail(); }
}

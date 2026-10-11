<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Post;
use App\Models\Category;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DatabaseTest extends TestCase
{
    public function test_demo_seeder_is_idempotent_and_never_overwrites_edits_or_password(): void
    {
        $admin=$this->admin(); $admin->update(['password'=>'ChangedPassword12345']);
        $post=$this->published(); $post->update(['title'=>'User edited this']);
        $this->seed(DatabaseSeeder::class);
        $this->assertSame(2, User::count());
        $this->assertSame(5, Post::count());
        $this->assertSame(3, Category::count());
        $this->assertTrue(Hash::check('ChangedPassword12345', $admin->fresh()->password));
        $this->assertSame('User edited this', $post->fresh()->title);
    }
    public function test_existing_session_is_revoked_after_password_reset_elsewhere(): void
    {
        $reader=$this->reader();
        $this->actingAs($reader)->get(route('saved'))->assertOk();
        $reader->update(['password'=>'ResetPassword12345']);
        // Retain the real session snapshot, but reload user as a new HTTP request would.
        $this->app['auth']->guard()->setUser($reader->fresh());
        $this->get(route('saved'))->assertRedirect(route('login'));
        $this->assertGuest();
    }
}

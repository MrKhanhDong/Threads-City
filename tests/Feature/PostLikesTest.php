<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PostLikesTest extends TestCase
{
    use RefreshDatabase;

    public function test_like_is_idempotent_and_ignores_spoofed_identity(): void
    {
        $post = Post::factory()->published()->create();
        $reader = User::factory()->create();
        $other = User::factory()->create();
        $this->actingAs($reader);
        $this->post(route('likes.store', $post->id), ['user_id' => $other->id, 'post_id' => 999])->assertRedirect()->assertSessionHasNoErrors();
        $timestamp = DB::table('post_likes')->value('created_at');
        $this->travel(1)->minutes();
        $this->post(route('likes.store', $post->id))->assertRedirect();
        $this->assertDatabaseCount('post_likes', 1);
        $this->assertDatabaseHas('post_likes', ['post_id' => $post->id, 'user_id' => $reader->id, 'created_at' => $timestamp]);
        $this->assertSame(1, $post->likers()->count());
        $this->assertTrue($reader->likedPosts()->whereKey($post->id)->exists());
    }

    public function test_unlike_is_idempotent_and_only_removes_current_readers_like(): void
    {
        $post = Post::factory()->published()->create();
        $reader = User::factory()->create();
        $other = User::factory()->create();
        $post->likers()->attach([$reader->id, $other->id]);
        $this->actingAs($reader);
        $this->delete(route('likes.destroy', $post->id), ['user_id' => $other->id])->assertRedirect();
        $this->delete(route('likes.destroy', $post->id))->assertRedirect();
        $this->assertDatabaseCount('post_likes', 1);
        $this->assertDatabaseHas('post_likes', ['user_id' => $other->id, 'post_id' => $post->id]);
    }

    public function test_guest_unverified_and_locked_readers_cannot_like(): void
    {
        $post = Post::factory()->published()->create();
        $this->post(route('likes.store', $post->id))->assertRedirect(route('login'));
        $this->delete(route('likes.destroy', $post->id))->assertRedirect(route('login'));
        $this->actingAs(User::factory()->unverified()->create());
        $this->post(route('likes.store', $post->id))->assertRedirect(route('verification.notice'));
        $this->delete(route('likes.destroy', $post->id))->assertRedirect(route('verification.notice'));
        $this->actingAs(User::factory()->create(['is_locked' => true]));
        $this->post(route('likes.store', $post->id))->assertRedirect(route('login'));
        $this->assertGuest();
        $this->assertDatabaseCount('post_likes', 0);
    }

    public function test_draft_scheduled_trashed_and_missing_posts_cannot_be_liked(): void
    {
        $draft = Post::factory()->create();
        $future = Post::factory()->scheduled()->create();
        $trashed = Post::factory()->published()->create();
        $trashed->delete();
        $this->actingAs(User::factory()->create());
        foreach ([$draft->id, $future->id, $trashed->id, 999999] as $id) {
            $this->post(route('likes.store', $id))->assertNotFound();
            $this->delete(route('likes.destroy', $id))->assertNotFound();
        }
        $this->assertDatabaseCount('post_likes', 0);
    }

    public function test_due_scheduled_post_can_be_liked(): void
    {
        $post = Post::factory()->scheduled()->create(['published_at' => now()->subMinute()]);
        $this->actingAs(User::factory()->create())->post(route('likes.store', $post->id))->assertRedirect();
        $this->assertDatabaseCount('post_likes', 1);
    }

    public function test_public_count_and_personal_like_state_render_without_exposing_reader_names(): void
    {
        $post = Post::factory()->published()->create();
        $reader = User::factory()->create(['name' => 'Độc giả riêng tư']);
        $post->likers()->attach($reader);
        $this->get(route('posts.show', $post->slug))->assertOk()->assertSee('Thích · 1')->assertDontSee($reader->name);
        $this->actingAs($reader)->get(route('posts.show', $post->slug))->assertOk()->assertSee('Đã thích · 1')->assertSee('aria-pressed="true"', false);
        $this->delete(route('likes.destroy', $post->id));
        $this->get(route('posts.show', $post->slug))->assertOk()->assertSee('Thích · 0')->assertSee('aria-pressed="false"', false);
    }

    public function test_likes_survive_soft_delete_but_cascade_on_permanent_delete_or_reader_delete(): void
    {
        $post = Post::factory()->published()->create();
        $reader = User::factory()->create();
        $post->likers()->attach($reader);
        $post->delete();
        $this->assertDatabaseCount('post_likes', 1);
        $post->restore();
        $this->assertSame(1, $post->likers()->count());
        $reader->delete();
        $this->assertDatabaseCount('post_likes', 0);
        $post->likers()->attach(User::factory()->create());
        $post->forceDelete();
        $this->assertDatabaseCount('post_likes', 0);
    }

    public function test_admin_preview_does_not_offer_like_action(): void
    {
        $post = Post::factory()->create();
        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get(route('admin.posts.preview', $post))->assertOk()->assertDontSee('data-like-action', false);
    }
}

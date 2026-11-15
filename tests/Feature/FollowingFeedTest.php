<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FollowingFeedTest extends TestCase
{
    use RefreshDatabase;

    public function test_following_feed_contains_only_followed_authors_and_never_own_posts(): void
    {
        $viewer = User::factory()->create();
        $followed = User::factory()->create();
        $follower = User::factory()->create();
        $viewer->following()->attach($followed);
        $follower->following()->attach($viewer);
        $own = Post::factory()->published()->create(['user_id' => $viewer->id]);
        $allowed = Post::factory()->published()->create(['user_id' => $followed->id]);
        Post::factory()->published()->create(['user_id' => $follower->id]);
        Post::factory()->published()->create(['user_id' => $viewer->id, 'type' => 'repost', 'repost_of_id' => $allowed->id]);

        $this->actingAs($viewer)->get(route('home', ['tab' => 'following']))->assertOk()
            ->assertViewHas('threads', fn ($threads): bool => $threads->total() === 1 && $threads->first()->id === $allowed->id);
        $this->get(route('home'))->assertOk()
            ->assertViewHas('threads', fn ($threads): bool => $threads->contains('id', $own->id));
    }

    public function test_no_followed_authors_means_an_empty_feed_even_after_posting(): void
    {
        $viewer = User::factory()->create();
        Post::factory()->published()->create(['user_id' => $viewer->id]);
        Post::factory()->published()->create();

        $this->actingAs($viewer)->get(route('home', ['tab' => 'following']))->assertOk()
            ->assertViewHas('threads', fn ($threads): bool => $threads->isEmpty())
            ->assertSee('data-following-empty', false)->assertSee('Khám phá tài khoản');
        $response = $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])->getJson(route('home', ['tab' => 'following']))->assertOk()->assertJsonPath('hasMore', false);
        $this->assertStringContainsString('data-following-empty', $response->json('html'));
    }

    public function test_following_pagination_keeps_own_and_unfollowed_posts_out(): void
    {
        $viewer = User::factory()->create();
        $followed = User::factory()->create();
        $viewer->following()->attach($followed);
        Post::factory()->published()->count(16)->create(['user_id' => $followed->id]);
        $own = Post::factory()->published()->create(['user_id' => $viewer->id]);
        $outsider = Post::factory()->published()->create();

        $first = $this->actingAs($viewer)->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->getJson(route('home', ['tab' => 'following']))->assertOk()->assertJsonPath('hasMore', true);
        $this->assertStringContainsString('tab=following', $first->json('nextPageUrl'));
        foreach ([1, 2] as $page) {
            $response = $this->getJson(route('home', ['tab' => 'following', 'page' => $page]))->assertOk();
            $this->assertStringNotContainsString('data-thread-id="'.$own->id.'"', $response->json('html'));
            $this->assertStringNotContainsString('data-thread-id="'.$outsider->id.'"', $response->json('html'));
        }
    }

    public function test_live_following_updates_remove_own_and_unfollowed_posts_already_on_screen(): void
    {
        $viewer = User::factory()->create();
        $followed = User::factory()->create();
        $viewer->following()->attach($followed);
        $own = Post::factory()->published()->create(['user_id' => $viewer->id]);
        $outsider = Post::factory()->published()->create();
        $allowed = Post::factory()->published()->create(['user_id' => $followed->id]);

        $this->actingAs($viewer)->get(route('home', ['tab' => 'following']), [
            'X-Threads-Live' => '1', 'Accept' => 'text/html',
            'X-Threads-Known' => implode(',', [$own->id, $outsider->id, $allowed->id]),
        ])->assertOk()->assertJsonPath('removed', [(string) $own->id, (string) $outsider->id]);
    }

    public function test_following_requires_a_signed_in_account(): void
    {
        $this->get(route('home', ['tab' => 'following']))->assertRedirect(route('login'));
    }
}

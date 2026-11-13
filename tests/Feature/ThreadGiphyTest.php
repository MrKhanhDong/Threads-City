<?php

namespace Tests\Feature;

use App\Models\Media;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ThreadGiphyTest extends TestCase
{
    use RefreshDatabase;

    public function test_gif_only_posts_store_ids_and_render_on_feed_and_detail_without_copying_media(): void
    {
        $this->actingAs(User::factory()->create());
        $response = $this->postJson(route('threads.store'), ['giphy_ids' => ['xT4uQulxzV39haRFjG']])->assertOk();
        $post = Post::findOrFail($response->json('thread.id'));
        $this->assertSame(['giphy_ids' => ['xT4uQulxzV39haRFjG']], json_decode($post->excerpt, true));
        $this->assertDatabaseCount('media', 0);
        foreach ([route('home'), route('threads.show', $post)] as $url) {
            $this->get($url)->assertOk()->assertSee('data-giphy-id="xT4uQulxzV39haRFjG"', false);
        }
        $this->patchJson(route('threads.update', $post), ['content' => ''])->assertOk();
        $this->assertSame(['xT4uQulxzV39haRFjG'], json_decode($post->fresh()->excerpt, true)['giphy_ids']);
        $quote = $this->postJson(route('threads.store'), ['content' => 'Chia sẻ GIF', 'repost_of_id' => $post->id])->assertOk();
        $this->get(route('threads.show', $quote->json('thread.id')))->assertOk()->assertSee('data-giphy-id="xT4uQulxzV39haRFjG"', false);
    }

    public function test_invalid_duplicate_ids_and_combined_attachment_limit_are_rejected(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        foreach ([['https://example.com/a.gif'], ['<script>'], ['same', 'same'], [''], [str_repeat('a', 101)]] as $ids) {
            $this->postJson(route('threads.store'), ['content' => 'GIF', 'giphy_ids' => $ids])->assertUnprocessable();
        }
        $media = Media::factory()->create(['user_id' => $user->id]);
        $this->postJson(route('threads.store'), ['media_ids' => [$media->id], 'giphy_ids' => array_map(fn (int $number): string => 'gif'.$number, range(1, 20))])->assertUnprocessable();
        $this->assertDatabaseCount('posts', 0);
    }

    public function test_gif_metadata_coexists_with_location_and_guests_cannot_publish(): void
    {
        $this->postJson(route('threads.store'), ['giphy_ids' => ['hello']])->assertUnauthorized();
        $this->actingAs(User::factory()->create());
        $response = $this->postJson(route('threads.store'), ['giphy_ids' => ['hello'], 'location' => 'TP. Hồ Chí Minh'])->assertOk();
        $metadata = json_decode(Post::findOrFail($response->json('thread.id'))->excerpt, true);
        $this->assertSame('TP. Hồ Chí Minh', $metadata['location']);
        $this->assertSame(['hello'], $metadata['giphy_ids']);
    }
}

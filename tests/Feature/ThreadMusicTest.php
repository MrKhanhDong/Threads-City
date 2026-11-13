<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ThreadMusicTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        Http::preventStrayRequests();
    }

    /** @return array<string, mixed> */
    private function track(string $id = 'song123', bool $gated = false): array
    {
        return ['id' => $id, 'title' => 'Bài hát thử', 'user' => ['name' => 'Nghệ sĩ'], 'permalink' => '/artist/bai-hat-thu', 'duration' => 215, 'is_streamable' => true, 'is_stream_gated' => $gated, 'artwork' => ['150x150' => 'https://cdn.example.com/art.jpg']];
    }

    public function test_search_requires_login_validates_input_and_only_lists_streamable_music(): void
    {
        $this->getJson(route('music.search'))->assertUnauthorized();
        $this->actingAs(User::factory()->create());
        $this->getJson(route('music.search', ['q' => str_repeat('a', 101)]))->assertUnprocessable();
        Http::fake(['api.audius.co/v1/tracks/search*' => Http::response(['data' => [$this->track(), $this->track('paid123', true)]])]);
        $url = route('music.search', ['q' => 'Bài hát']);
        $this->getJson($url)->assertOk()->assertJsonCount(1, 'items')->assertJsonPath('items.0.title', 'Bài hát thử')->assertJsonPath('items.0.provider', 'audius');
        $this->getJson($url)->assertOk();
        Http::assertSentCount(1);
    }

    public function test_selected_music_uses_trusted_catalog_metadata_and_renders_without_media_upload(): void
    {
        $this->actingAs(User::factory()->create());
        Http::fake(['api.audius.co/v1/tracks/search*' => Http::response(['data' => [$this->track()]])]);
        $this->getJson(route('music.search', ['q' => 'Bài hát']))->assertOk();
        $response = $this->postJson(route('threads.store'), ['music_id' => 'song123', 'music' => ['title' => 'Fake title', 'stream_url' => 'javascript:alert(1)']])->assertOk();
        $post = Post::findOrFail($response->json('thread.id'));
        $metadata = json_decode($post->excerpt, true);
        $this->assertSame('Bài hát thử', $metadata['music']['title']);
        $this->assertSame('audius', $metadata['music']['provider']);
        $this->assertDatabaseCount('media', 0);
        Http::assertSentCount(1);
        foreach ([route('home'), route('threads.show', $post)] as $url) {
            $this->get($url)->assertOk()->assertSee('data-music-audio', false)->assertSee('https://api.audius.co/v1/tracks/song123/stream?app_name=ThreadsCity', false);
        }
        $this->patchJson(route('threads.update', $post), ['content' => ''])->assertOk();
        $this->assertSame($metadata, json_decode($post->fresh()->excerpt, true));
    }

    public function test_invalid_unavailable_and_gated_tracks_cannot_be_attached(): void
    {
        $this->actingAs(User::factory()->create());
        $this->postJson(route('threads.store'), ['music_id' => '../bad'])->assertUnprocessable();
        Http::fake(['api.audius.co/v1/tracks/paid123*' => Http::response(['data' => $this->track('paid123', true)])]);
        $this->postJson(route('threads.store'), ['music_id' => 'paid123'])->assertUnprocessable()->assertJsonValidationErrors('music_id');
        $this->assertDatabaseCount('posts', 0);
    }

    public function test_catalog_outages_report_a_recoverable_error_without_creating_a_post(): void
    {
        $this->actingAs(User::factory()->create());
        Http::fake(['api.audius.co/*' => Http::response([], 503)]);
        $this->getJson(route('music.search'))->assertStatus(503)->assertJsonPath('message', 'Kho nhạc đang bận. Vui lòng thử lại sau.');
        $this->postJson(route('threads.store'), ['content' => 'Bài viết có nhạc', 'music_id' => 'song123'])->assertUnprocessable()->assertJsonValidationErrors('music_id');
        $this->assertDatabaseCount('posts', 0);
    }

    public function test_opening_feed_does_not_contact_music_provider(): void
    {
        $this->get(route('home'))->assertOk();
        Http::assertNothingSent();
    }
}

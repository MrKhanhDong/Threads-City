<?php

namespace Tests\Feature;

use App\Models\Media;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ThreadMultimediaTest extends TestCase
{
    use RefreshDatabase;

    public function test_clips_and_voice_notes_upload_attach_and_support_range_requests(): void
    {
        Storage::fake('local');
        config(['services.cloudinary' => []]);
        $user = User::factory()->create();
        $this->actingAs($user);
        foreach (['video' => 'video/mp4', 'audio' => 'audio/webm'] as $kind => $mime) {
            $file = UploadedFile::fake()->create($kind === 'video' ? 'clip.mp4' : 'voice.webm', 20, $mime);
            file_put_contents($file->getPathname(), str_repeat('x', 20480));
            $response = $this->postJson(route('threads.upload-media'), ['file' => $file, 'kind' => $kind, 'duration' => 12.5, 'waveform' => [10, 40, 80, 20]])
                ->assertOk()->assertJsonPath('kind', $kind);
            $media = Media::findOrFail($response->json('id'));
            $this->assertSame(12500, $media->duration_ms);
            Storage::disk('local')->assertExists($media->path);
            $this->assertSame($kind === 'audio' ? [10, 40, 80, 20] : null, $media->waveform);
            $this->postJson(route('threads.store'), ['media_ids' => [$media->id]])->assertOk();
            $this->get(route('media.show', $media), ['Range' => 'bytes=0-9'])->assertStatus(206)->assertHeader('Content-Range', 'bytes 0-9/20480');
        }
    }

    public function test_invalid_media_and_foreign_attachments_are_rejected(): void
    {
        Storage::fake('local');
        config(['services.cloudinary' => []]);
        $this->actingAs(User::factory()->create());
        $this->postJson(route('threads.upload-media'), ['file' => UploadedFile::fake()->create('script.html', 1, 'text/html'), 'kind' => 'video', 'duration' => 1])->assertUnprocessable();
        $this->postJson(route('threads.upload-media'), ['file' => UploadedFile::fake()->create('long.mp4', 1, 'video/mp4'), 'kind' => 'video', 'duration' => 61])->assertUnprocessable();
        $this->postJson(route('threads.upload-media'), ['file' => UploadedFile::fake()->create('big.mp4', 25601, 'video/mp4'), 'kind' => 'video', 'duration' => 10])->assertUnprocessable();
        $this->postJson(route('threads.upload-media'), ['file' => UploadedFile::fake()->create('voice.webm', 1, 'audio/webm'), 'kind' => 'audio', 'duration' => 10, 'waveform' => [101]])->assertUnprocessable();
        $foreign = Media::factory()->create();
        $this->postJson(route('threads.store'), ['content' => 'Foreign upload', 'media_ids' => [$foreign->id]])->assertUnprocessable();
        $this->assertDatabaseCount('posts', 0);
    }

    public function test_poll_deadline_validation_single_vote_and_closed_results(): void
    {
        $author = User::factory()->create();
        $reader = User::factory()->create();
        $this->actingAs($author);
        foreach ([['A', ' a '], [' ', 'B'], ['A'], ['A', 'B', 'C', 'D', 'E']] as $options) {
            $this->postJson(route('threads.store'), ['poll_options' => $options])->assertUnprocessable();
        }
        $response = $this->postJson(route('threads.store'), ['content' => 'Bạn chọn gì?', 'poll_options' => ['A', 'B', 'C', 'D'], 'poll_duration_hours' => 1])->assertOk();
        $post = Post::findOrFail($response->json('thread.id'));
        $this->assertSame(1, json_decode($post->excerpt, true)['duration_hours']);
        $this->actingAs($reader);
        $this->postJson(route('threads.vote', $post), ['option_id' => 1])->assertOk()->assertJsonPath('poll.total_votes', 1);
        $this->postJson(route('threads.vote', $post), ['option_id' => 1])->assertOk()->assertJsonPath('poll.total_votes', 1);
        $this->postJson(route('threads.vote', $post), ['option_id' => 2])->assertOk()->assertJsonPath('poll.total_votes', 1)->assertJsonPath('poll.options.1.percentage', 100);
        $this->travel(2)->hours();
        $this->postJson(route('threads.vote', $post), ['option_id' => 1])->assertStatus(409)->assertJsonPath('poll.closed', true)->assertJsonPath('poll.total_votes', 1);
        $this->get(route('threads.show', $post))->assertOk()->assertSee('Đã kết thúc')->assertDontSee('class="poll-display-vote-btn btn-ajax-poll-vote"', false);
    }

    public function test_feed_renders_accessible_gallery_clip_voice_and_composer(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->published()->create(['user_id' => $user->id]);
        foreach (['image/jpeg', 'image/png', 'video/mp4', 'audio/webm'] as $mime) {
            $media = Media::factory()->create(['user_id' => $user->id, 'mime_type' => $mime, 'duration_ms' => 5000, 'waveform' => [10, 30, 80, 40, 10]]);
            $post->media()->attach($media);
        }
        $response = $this->actingAs($user)->get(route('home'))->assertOk()->assertSee('data-lightbox', false)->assertSee('data-clip', false)->assertSee('data-voice', false)->assertSee('pollDurationHours', false);
    }

    public function test_twenty_images_are_saved_and_all_render_on_the_feed_and_post_page(): void
    {
        $user = User::factory()->create();
        $media = Media::factory()->count(20)->create(['user_id' => $user->id, 'mime_type' => 'image/jpeg']);
        $response = $this->actingAs($user)->postJson(route('threads.store'), ['media_ids' => $media->modelKeys()])->assertOk();
        $post = Post::findOrFail($response->json('thread.id'));

        $this->assertEqualsCanonicalizing($media->modelKeys(), $post->media()->pluck('media.id')->all());
        foreach ([route('home'), route('threads.show', $post)] as $url) {
            $page = $this->get($url)->assertOk()->assertSee('data-media-count="20"', false)->assertSee('const MAX_MEDIA = 20;', false);
            $this->assertSame(20, substr_count($page->getContent(), 'class="gallery-media-link" data-lightbox'));
        }
    }

    public function test_attachment_limit_rejects_twenty_one_items_and_mixed_upload_bypasses(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        $media = Media::factory()->count(21)->create(['user_id' => $user->id, 'mime_type' => 'image/jpeg']);
        $this->actingAs($user)->postJson(route('threads.store'), ['media_ids' => $media->modelKeys()])
            ->assertUnprocessable()->assertJsonValidationErrors('media_ids');
        $this->postJson(route('threads.store'), [
            'media_ids' => $media->take(20)->modelKeys(),
            'images' => [UploadedFile::fake()->image('extra.jpg', 20, 20)],
        ])->assertUnprocessable()->assertJsonValidationErrors('media_ids');

        $files = array_map(fn (int $index): UploadedFile => UploadedFile::fake()->image("photo-{$index}.jpg", 20, 20), range(1, 21));
        $this->postJson(route('threads.store'), ['images' => $files])->assertUnprocessable()->assertJsonValidationErrors('images');
        $this->postJson(route('threads.upload-media'), ['images' => $files])->assertUnprocessable()->assertJsonValidationErrors('images');
        $this->assertDatabaseCount('posts', 0);
        $this->assertDatabaseCount('media', 21);
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_batch_upload_accepts_twenty_images(): void
    {
        Storage::fake('local');
        config(['services.cloudinary' => []]);
        $user = User::factory()->create();
        $files = array_map(fn (int $index): UploadedFile => UploadedFile::fake()->image("photo-{$index}.jpg", 20, 20), range(1, 20));
        $this->actingAs($user)->postJson(route('threads.upload-media'), ['images' => $files])
            ->assertOk()->assertJsonCount(20, 'items');
        $this->assertDatabaseCount('media', 20);
        $this->assertCount(20, Storage::disk('local')->allFiles());
    }
}

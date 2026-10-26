<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ThreadMediaUploadTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_upload_media_for_threads(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();

        $file = UploadedFile::fake()->image('test_photo.jpg', 600, 400);

        $response = $this->actingAs($user)
            ->postJson(route('threads.upload-media'), [
                'image' => $file,
            ]);

        $response->assertOk()
            ->assertJsonStructure(['id', 'url']);

        $mediaId = $response->json('id');
        $this->assertDatabaseHas('media', [
            'id' => $mediaId,
            'user_id' => $user->id,
        ]);
    }

    public function test_user_can_post_thread_with_media_ids(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();

        $file = UploadedFile::fake()->image('thread_image.png', 800, 600);
        $uploadRes = $this->actingAs($user)
            ->postJson(route('threads.upload-media'), [
                'image' => $file,
            ]);

        $mediaId = $uploadRes->json('id');

        $storeRes = $this->actingAs($user)
            ->postJson(route('threads.store'), [
                'content' => 'Check out this awesome photo! #cool',
                'media_ids' => [$mediaId],
                'type' => 'post',
            ]);

        $storeRes->assertOk();
        $threadId = $storeRes->json('thread.id');

        $this->assertDatabaseHas('posts', [
            'id' => $threadId,
            'user_id' => $user->id,
        ]);

        $this->assertDatabaseHas('post_media', [
            'post_id' => $threadId,
            'media_id' => $mediaId,
        ]);

        $showMediaRes = $this->actingAs($user)
            ->get(route('media.show', $mediaId));

        $showMediaRes->assertOk();
    }
}

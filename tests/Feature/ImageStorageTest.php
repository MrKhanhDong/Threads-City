<?php

namespace Tests\Feature;

use App\Http\Controllers\AccountController;
use App\Http\Controllers\Admin\MediaController;
use App\Models\Media;
use App\Models\User;
use App\Services\ImageStorage;
use Illuminate\Http\Client\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ImageStorageTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
        config(['services.cloudinary' => ['cloud_name' => 'test-cloud', 'api_key' => 'test-key', 'api_secret' => 'test-secret']]);
    }

    public function test_upload_uses_a_signed_request_and_returns_durable_url(): void
    {
        Http::fake(fn (Request $request) => Http::response(['secure_url' => 'https://res.cloudinary.com/test-cloud/image/upload/v123/'.collect($request->data())->pluck('contents', 'name')['public_id'].'.jpg']));
        $url = app(ImageStorage::class)->store(UploadedFile::fake()->image('photo.jpg'), 'media');

        $this->assertStringStartsWith('https://res.cloudinary.com/test-cloud/image/upload/v123/threads-city/media/', $url);
        Http::assertSent(function (Request $request): bool {
            $parameters = collect($request->data())->pluck('contents', 'name');
            $expected = sha1('public_id='.$parameters['public_id'].'&timestamp='.$parameters['timestamp'].'test-secret');

            return $request->url() === 'https://api.cloudinary.com/v1_1/test-cloud/image/upload'
                && $parameters['signature'] === $expected
                && $parameters['api_key'] === 'test-key'
                && ! $parameters->has('api_secret');
        });
    }

    public function test_partial_credentials_do_not_fall_back_to_temporary_storage(): void
    {
        config(['services.cloudinary.api_secret' => null]);
        $this->expectException(ValidationException::class);
        app(ImageStorage::class)->store(UploadedFile::fake()->image('photo.jpg'), 'media');
    }

    public function test_video_and_voice_storage_use_cloudinary_video_resource_and_can_be_deleted(): void
    {
        Http::fake(function (Request $request) {
            if (str_ends_with($request->url(), '/destroy')) {
                return Http::response(['result' => 'ok']);
            }
            $parameters = collect($request->data())->pluck('contents', 'name');

            return Http::response(['secure_url' => 'https://res.cloudinary.com/test-cloud/video/upload/v123/'.$parameters['public_id'].'.webm']);
        });
        $storage = app(ImageStorage::class);
        $url = $storage->store(UploadedFile::fake()->create('voice.webm', 1, 'audio/webm'), 'media');
        $this->assertStringContainsString('/video/upload/', $url);
        $this->assertTrue($storage->delete($url));
        Http::assertSent(fn (Request $request): bool => $request->url() === 'https://api.cloudinary.com/v1_1/test-cloud/video/upload');
        Http::assertSent(fn (Request $request): bool => $request->url() === 'https://api.cloudinary.com/v1_1/test-cloud/video/destroy');
    }

    public function test_vercel_requires_cloudinary_instead_of_using_temporary_files(): void
    {
        config(['services.cloudinary' => ['cloud_name' => null, 'api_key' => null, 'api_secret' => null]]);
        $previous = $_ENV['VERCEL'] ?? null;
        $_ENV['VERCEL'] = '1';
        try {
            $this->expectException(ValidationException::class);
            app(ImageStorage::class)->store(UploadedFile::fake()->image('photo.jpg'), 'media');
        } finally {
            if ($previous === null) {
                unset($_ENV['VERCEL']);
            } else {
                $_ENV['VERCEL'] = $previous;
            }
        }
    }

    public function test_provider_failure_is_a_validation_error(): void
    {
        Http::fake(['*' => Http::response(['error' => ['message' => 'Invalid credentials']], 401)]);
        $this->expectException(ValidationException::class);
        app(ImageStorage::class)->store(UploadedFile::fake()->image('photo.jpg'), 'media');
    }

    public function test_serverless_runtime_marker_never_allows_local_upload_fallback(): void
    {
        config(['services.cloudinary' => []]);
        Storage::fake('local');
        $previous = $_SERVER['VERCEL'] ?? null;
        $_SERVER['VERCEL'] = '1';
        try {
            try {
                app(ImageStorage::class)->store(UploadedFile::fake()->image('ephemeral.jpg'), 'media');
                $this->fail('Serverless uploads must use durable storage.');
            } catch (ValidationException $error) {
                $this->assertArrayHasKey('image', $error->errors());
                $this->assertSame([], Storage::disk('local')->allFiles());
                Http::assertNothingSent();
            }
        } finally {
            if ($previous === null) {
                unset($_SERVER['VERCEL']);
            } else {
                $_SERVER['VERCEL'] = $previous;
            }
        }
    }

    public function test_connection_failure_is_a_validation_error(): void
    {
        Http::fake(['*' => Http::failedConnection()]);
        $this->expectException(ValidationException::class);
        app(ImageStorage::class)->store(UploadedFile::fake()->image('photo.jpg'), 'media');
    }

    public function test_local_development_can_keep_using_local_files(): void
    {
        config(['services.cloudinary' => ['cloud_name' => null, 'api_key' => null, 'api_secret' => null]]);
        Storage::fake('local');
        $path = app(ImageStorage::class)->store(UploadedFile::fake()->image('photo.jpg'), 'media');
        Storage::disk('local')->assertExists($path);
        Http::assertNothingSent();
    }

    public function test_cloudinary_delete_uses_public_id_without_extension(): void
    {
        Http::fake(['*' => Http::response(['result' => 'ok'])]);
        $this->assertTrue(app(ImageStorage::class)->delete('https://res.cloudinary.com/test-cloud/image/upload/v123/threads-city/avatars/avatar-id.jpg'));
        Http::assertSent(fn (Request $request): bool => $request->url() === 'https://api.cloudinary.com/v1_1/test-cloud/image/destroy' && $request['public_id'] === 'threads-city/avatars/avatar-id');
    }

    public function test_avatar_route_redirects_to_cloudinary_without_local_file(): void
    {
        $url = 'https://res.cloudinary.com/test-cloud/image/upload/v123/threads-city/avatars/avatar-id.jpg';
        $user = new User(['avatar_path' => $url]);
        $response = app(AccountController::class)->avatar($user);
        $this->assertSame($url, $response->getTargetUrl());
    }

    public function test_media_route_redirects_for_owner_without_local_file(): void
    {
        $url = 'https://res.cloudinary.com/test-cloud/image/upload/v123/threads-city/media/photo-id.jpg';
        $user = new User;
        $user->id = 123;
        $request = \Illuminate\Http\Request::create('/media/1');
        $request->setUserResolver(fn () => $user);
        $media = new Media(['path' => $url, 'user_id' => 123]);
        $response = app(MediaController::class)->show($request, $media);
        $this->assertSame($url, $response->getTargetUrl());
    }

    public function test_external_urls_are_not_treated_as_cloudinary_uploads(): void
    {
        $storage = app(ImageStorage::class);
        $this->assertNull($storage->remoteUrl('https://example.com/photo.jpg'));
        $this->assertNull($storage->remoteUrl('https://res.cloudinary.com.evil.example/test-cloud/image/upload/v123/threads-city/media/photo.jpg'));
    }
}

<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ImageStorage
{
    public function store(UploadedFile $file, string $folder): string
    {
        $credentials = config('services.cloudinary');
        $configured = count(array_filter($credentials ?? []));
        if ($configured === 0 && ! env('VERCEL')) {
            $extension = match ($file->getMimeType()) {
                'video/mp4' => 'mp4',
                'audio/mp4', 'audio/x-m4a' => 'm4a',
                'video/webm', 'audio/webm' => 'webm',
                'audio/ogg' => 'ogg',
                'audio/wav', 'audio/x-wav' => 'wav',
                default => null,
            };
            $path = $extension ? $file->storeAs($folder, Str::random(40).'.'.$extension, 'local') : $file->store($folder, 'local');
            if (! $path) {
                throw ValidationException::withMessages(['image' => 'Không thể lưu ảnh. Vui lòng thử lại.']);
            }

            return $path;
        }

        if ($configured !== 3) {
            throw ValidationException::withMessages(['image' => 'Dịch vụ lưu ảnh chưa được cấu hình đầy đủ. Vui lòng liên hệ quản trị viên.']);
        }

        $parameters = ['public_id' => 'threads-city/'.$folder.'/'.Str::uuid(), 'timestamp' => time()];
        $stream = fopen($file->getRealPath(), 'rb');
        try {
            $response = Http::connectTimeout(10)->timeout(30)
                ->attach('file', $stream, $file->getClientOriginalName())
                ->post($this->endpoint('upload', str_starts_with($file->getMimeType(), 'image/') ? 'image' : 'video'), $this->signed($parameters));
        } catch (ConnectionException) {
            throw ValidationException::withMessages(['image' => 'Không kết nối được dịch vụ lưu ảnh. Vui lòng thử lại.']);
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
        }

        $url = $response->json('secure_url');
        if (! $response->successful() || ! is_string($url) || ! $this->remoteUrl($url) || strlen($url) > 255) {
            throw ValidationException::withMessages(['image' => 'Cloudinary không nhận được ảnh. Vui lòng kiểm tra cấu hình dịch vụ và thử lại.']);
        }

        return $url;
    }

    public function remoteUrl(?string $path): ?string
    {
        return $path && preg_match('~^https://res\.cloudinary\.com/[A-Za-z0-9_-]+/(?:image|video)/upload/v\d+/threads-city/(?:media|avatars)/[A-Za-z0-9_-]+\.(?:jpg|jpeg|png|webp|gif|mp4|webm|m4a|ogg|wav)$~D', $path) ? $path : null;
    }

    public function delete(string $path): bool
    {
        if (! $this->remoteUrl($path)) {
            return Storage::disk('local')->delete($path);
        }

        $cloud = preg_quote((string) config('services.cloudinary.cloud_name'), '~');
        if (! preg_match('~^https://res\.cloudinary\.com/'.$cloud.'/(image|video)/upload/v\d+/(.+)\.[a-z0-9]+$~D', $path, $matches)) {
            return false;
        }

        try {
            $response = Http::asForm()->connectTimeout(10)->timeout(15)
                ->post($this->endpoint('destroy', $matches[1]), $this->signed(['public_id' => $matches[2], 'timestamp' => time()]));

            return $response->successful() && in_array($response->json('result'), ['ok', 'not found'], true);
        } catch (ConnectionException) {
            return false;
        }
    }

    private function endpoint(string $action, string $resource = 'image'): string
    {
        return 'https://api.cloudinary.com/v1_1/'.rawurlencode((string) config('services.cloudinary.cloud_name')).'/'.$resource.'/'.$action;
    }

    private function signed(array $parameters): array
    {
        ksort($parameters);
        $signature = sha1(urldecode(http_build_query($parameters, '', '&', PHP_QUERY_RFC3986)).config('services.cloudinary.api_secret'));

        return $parameters + ['api_key' => config('services.cloudinary.api_key'), 'signature' => $signature];
    }
}

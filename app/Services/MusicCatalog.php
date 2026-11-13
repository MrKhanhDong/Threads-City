<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class MusicCatalog
{
    /** @return array{items: array<int, array<string, mixed>>, has_more: bool, next_offset: int} */
    public function search(string $query, int $offset = 0): array
    {
        return Cache::remember('music:audius:search:'.sha1($query.':'.$offset), 300, function () use ($query, $offset): array {
            $parameters = ['limit' => 20, 'offset' => $offset];
            if ($query !== '') {
                $parameters['query'] = $query;
            }
            $data = $this->fetch($query === '' ? 'tracks/trending' : 'tracks/search', $parameters);
            $items = [];
            foreach ($data as $track) {
                if (is_array($track) && ($item = $this->normalize($track))) {
                    $items[] = $item;
                    Cache::put('music:audius:track:'.$item['id'], $item, 300);
                }
            }

            return ['items' => $items, 'has_more' => count($data) >= 20, 'next_offset' => $offset + count($data)];
        });
    }

    /** @return array<string, mixed> */
    public function track(string $id): array
    {
        try {
            $track = Cache::remember('music:audius:track:'.$id, 300, fn (): ?array => $this->normalize($this->fetch('tracks/'.$id)));
        } catch (RuntimeException) {
            throw ValidationException::withMessages(['music_id' => 'Không xác nhận được bài hát. Vui lòng thử lại sau.']);
        }
        if (! $track) {
            throw ValidationException::withMessages(['music_id' => 'Bài hát này không còn phát được. Hãy chọn bài khác.']);
        }

        return $track;
    }

    /** @param array<string, mixed> $parameters
     * @return array<string|int, mixed>
     */
    private function fetch(string $path, array $parameters = []): array
    {
        try {
            $response = Http::acceptJson()->connectTimeout(3)->timeout(8)
                ->get('https://api.audius.co/v1/'.$path, ['app_name' => 'ThreadsCity', ...$parameters])->throw();
        } catch (ConnectionException|RequestException $error) {
            throw new RuntimeException('Kho nhạc đang bận. Vui lòng thử lại sau.', 0, $error);
        }
        $data = $response->json('data');
        if (! is_array($data)) {
            throw new RuntimeException('Kho nhạc trả về dữ liệu không hợp lệ.');
        }

        return $data;
    }

    /** @param array<string, mixed> $track
     * @return array<string, mixed>|null
     */
    private function normalize(array $track): ?array
    {
        $id = $track['id'] ?? null;
        if (! is_string($id) || ! preg_match('/\A[a-zA-Z0-9]{1,64}\z/', $id) || ! ($track['is_streamable'] ?? false) || ($track['is_stream_gated'] ?? false) || ($track['is_deleted'] ?? false)) {
            return null;
        }
        $permalink = $track['permalink'] ?? '';
        if (! is_string($permalink) || ! preg_match('~\A/[a-zA-Z0-9_/%.-]+\z~', $permalink)) {
            return null;
        }
        $artwork = $track['artwork']['150x150'] ?? null;
        if (! is_string($artwork) || ! filter_var($artwork, FILTER_VALIDATE_URL) || parse_url($artwork, PHP_URL_SCHEME) !== 'https') {
            $artwork = null;
        }

        return [
            'provider' => 'audius', 'id' => $id,
            'title' => mb_substr(strip_tags((string) ($track['title'] ?? 'Bài hát')), 0, 200),
            'artist' => mb_substr(strip_tags((string) ($track['user']['name'] ?? 'Nghệ sĩ')), 0, 200),
            'artwork' => $artwork, 'url' => 'https://audius.co'.$permalink,
            'stream_url' => 'https://api.audius.co/v1/tracks/'.$id.'/stream?app_name=ThreadsCity',
            'duration' => max(0, (int) ($track['duration'] ?? 0)),
        ];
    }
}

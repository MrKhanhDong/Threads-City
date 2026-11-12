<?php

namespace App\Services;

class PushSubscriptions
{
    public function configured(): bool
    {
        return filled(config('services.webpush.public_key')) && filled(config('services.webpush.private_key')) && filled(config('services.webpush.subject'));
    }

    public function allowedEndpoint(string $endpoint): bool
    {
        $parts = parse_url($endpoint);
        if ($parts === false || ($parts['scheme'] ?? '') !== 'https' || isset($parts['user']) || isset($parts['pass']) || isset($parts['fragment']) || (isset($parts['port']) && $parts['port'] !== 443)) {
            return false;
        }
        $host = strtolower($parts['host'] ?? '');

        return in_array($host, ['fcm.googleapis.com', 'updates.push.services.mozilla.com', 'web.push.apple.com'], true)
            || (str_ends_with($host, '.notify.windows.com') && preg_match('/^[a-z0-9.-]+$/D', $host));
    }

    public function validKey(string $encoded, int $length): bool
    {
        if (! preg_match('/^[A-Za-z0-9_-]+={0,2}$/D', $encoded)) {
            return false;
        }
        $decoded = base64_decode(strtr($encoded, '-_', '+/'), true);

        return $decoded !== false && strlen($decoded) === $length && ($length !== 65 || $decoded[0] === "\x04");
    }
}

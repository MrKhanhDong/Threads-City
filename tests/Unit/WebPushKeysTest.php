<?php

namespace Tests\Unit;

use App\Services\PushSubscriptions;
use GuzzleHttp\Psr7\HttpFactory;
use GuzzleHttp\Psr7\Response;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\VAPID;
use Minishlink\WebPush\WebPush;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class WebPushKeysTest extends TestCase
{
    public function test_library_generates_usable_vapid_keys_on_the_installed_php_runtime(): void
    {
        $subscriptions = new PushSubscriptions;
        $keys = VAPID::createVapidKeys();
        $this->assertTrue($subscriptions->validKey($keys['publicKey'], 65));
        $this->assertTrue($subscriptions->validKey($keys['privateKey'], 32));
        $this->assertFalse($subscriptions->validKey('not-a-valid-key', 65));
        $this->assertFalse($subscriptions->validKey(str_repeat('!', 22), 16));
    }

    public function test_only_supported_https_push_providers_are_accepted(): void
    {
        $subscriptions = new PushSubscriptions;
        foreach (['https://fcm.googleapis.com/fcm/send/id', 'https://updates.push.services.mozilla.com/wpush/v2/id', 'https://web.push.apple.com/id', 'https://wns2-test.notify.windows.com/w/?token=id'] as $endpoint) {
            $this->assertTrue($subscriptions->allowedEndpoint($endpoint), $endpoint);
        }
        foreach (['https://127.0.0.1/admin', 'https://fcm.googleapis.com.attacker.test/id', 'https://attacker.test/fcm.googleapis.com', 'http://fcm.googleapis.com/id', 'https://user:pass@web.push.apple.com/id', 'https://web.push.apple.com:8443/id'] as $endpoint) {
            $this->assertFalse($subscriptions->allowedEndpoint($endpoint), $endpoint);
        }
    }

    public function test_web_push_encrypts_and_signs_the_payload_without_using_the_network(): void
    {
        $serverKeys = VAPID::createVapidKeys();
        $browserKeys = VAPID::createVapidKeys();
        $client = new class implements ClientInterface
        {
            /** @var array<int, RequestInterface> */
            public array $requests = [];

            public function sendRequest(RequestInterface $request): ResponseInterface
            {
                $this->requests[] = $request;

                return new Response(201);
            }
        };
        $factory = new HttpFactory;
        $push = new WebPush(['VAPID' => ['subject' => 'mailto:admin@example.com', ...$serverKeys]], ['TTL' => 300], $client, $factory, $factory);
        $report = $push->sendOneNotification(Subscription::create([
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/test-endpoint',
            'keys' => ['p256dh' => $browserKeys['publicKey'], 'auth' => rtrim(strtr(base64_encode(random_bytes(16)), '+/', '-_'), '=')],
            'contentEncoding' => 'aes128gcm',
        ]), '{"token":"private-device-token"}');
        $this->assertTrue($report->isSuccess());
        $this->assertCount(1, $client->requests);
        $request = $client->requests[0];
        $this->assertSame('aes128gcm', $request->getHeaderLine('Content-Encoding'));
        $this->assertStringStartsWith('vapid ', $request->getHeaderLine('Authorization'));
        $this->assertStringNotContainsString('private-device-token', (string) $request->getBody());
    }
}

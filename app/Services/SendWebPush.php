<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;
use RuntimeException;

class SendWebPush implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 60;

    public function __construct(public int $userId, public string $notificationId) {}

    public function handle(PushSubscriptions $subscriptions, UserNotifications $inbox): void
    {
        $user = User::find($this->userId);
        if (! $subscriptions->configured() || ! $user || $user->is_locked || ! $inbox->forUser($user)->whereKey($this->notificationId)->whereNull('read_at')->exists()) {
            return;
        }
        foreach (DB::table('push_subscriptions')->where('user_id', $user->id)->get() as $device) {
            if (config('session.driver') === 'database' && ! DB::table('sessions')->where('id', $device->session_id)->where('user_id', $user->id)->where('last_activity', '>', now()->subMinutes((int) config('session.lifetime'))->timestamp)->exists()) {
                continue;
            }
            if (! $subscriptions->allowedEndpoint($device->endpoint)) {
                continue;
            }
            $report = app(WebPush::class)->sendOneNotification(
                Subscription::create(['endpoint' => $device->endpoint, 'keys' => ['p256dh' => $device->public_key, 'auth' => Crypt::decryptString($device->auth_token)], 'contentEncoding' => 'aes128gcm']),
                json_encode(['token' => $device->browser_token, 'notification' => $this->notificationId], JSON_THROW_ON_ERROR),
                ['TTL' => 300, 'urgency' => 'normal', 'topic' => substr(hash('sha256', $this->notificationId), 0, 32)],
            );
            if ($report->isSubscriptionExpired()) {
                DB::table('push_subscriptions')->where('id', $device->id)->where('browser_token', $device->browser_token)->delete();
            } elseif (! $report->isSuccess()) {
                throw new RuntimeException('Dịch vụ Web Push chưa nhận được thông báo.');
            }
        }
    }
}

<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

class Mentioned extends Notification
{
    public function __construct(public int $mentionId) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /** @return array{mention_id: int} */
    public function toArray(object $notifiable): array
    {
        return ['mention_id' => $this->mentionId];
    }
}

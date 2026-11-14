<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

class ModerationDecision extends Notification
{
    public function __construct(public string $message, public string $reason) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /** @return array<string, string> */
    public function toArray(object $notifiable): array
    {
        return ['message' => $this->message, 'reason' => $this->reason];
    }
}

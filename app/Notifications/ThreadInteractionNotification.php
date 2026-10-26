<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ThreadInteractionNotification extends Notification
{
    use Queueable;

    /**
     * @param array{
     *     action: string,
     *     actor_id: int,
     *     actor_name: string,
     *     actor_handle: string,
     *     actor_avatar?: ?string,
     *     thread_id?: ?int,
     *     content_snippet?: ?string,
     *     url: string
     * } $data
     */
    public function __construct(public array $data) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return $this->data;
    }
}

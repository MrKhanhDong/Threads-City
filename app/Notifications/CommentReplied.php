<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

class CommentReplied extends Notification
{
    public function __construct(public int $commentId) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return ['comment_id' => $this->commentId];
    }
}

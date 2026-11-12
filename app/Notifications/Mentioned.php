<?php

namespace App\Notifications;

use App\Models\Comment;
use App\Models\Post;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\DB;

class Mentioned extends Notification
{
    public function __construct(public int $mentionId) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /** @return array{mention_id: int, action: string, actor_id: int, actor_name: string, actor_handle: string, actor_avatar: ?string} */
    public function toArray(object $notifiable): array
    {
        $mention = DB::table('content_mentions')->find($this->mentionId);
        $source = $mention->post_id ? Post::find($mention->post_id) : Comment::find($mention->comment_id);
        $actor = $mention->post_id ? $source->author : $source->user;

        return ['mention_id' => $this->mentionId, 'action' => 'mention', 'actor_id' => $actor->id, 'actor_name' => $actor->name, 'actor_handle' => $actor->handle(), 'actor_avatar' => $actor->avatar_path ? route('avatar.show', $actor) : null];
    }
}

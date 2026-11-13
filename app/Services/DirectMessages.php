<?php

namespace App\Services;

use App\Models\ChatConversation;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class DirectMessages
{
    public const REACTIONS = ['❤️', '👍', '😂', '😮', '😢', '😡'];

    public const STICKERS = ['hello' => 'Chào bạn', 'love' => 'Yêu thương', 'laugh' => 'Vui quá', 'wow' => 'Bất ngờ', 'sad' => 'Buồn một chút', 'angry' => 'Giận rồi', 'sleep' => 'Ngủ ngon', 'thanks' => 'Cảm ơn'];

    public function visible(User $viewer): Builder
    {
        return ChatConversation::query()->where(fn (Builder $pairs): Builder => $pairs->where('first_user_id', $viewer->id)->orWhere('second_user_id', $viewer->id))
            ->whereHas('firstUser', fn (Builder $users): Builder => $users->discoverableTo($viewer)->where('is_locked', false))
            ->whereHas('secondUser', fn (Builder $users): Builder => $users->discoverableTo($viewer)->where('is_locked', false));
    }

    public function inbox(User $viewer): Builder
    {
        return $this->visible($viewer)->with(['firstUser', 'secondUser', 'latestMessage'])
            ->withCount(['messages as unread_count' => fn (Builder $messages): Builder => $messages->where('sender_id', '!=', $viewer->id)
                ->whereRaw('chat_messages.id > CASE WHEN chat_conversations.first_user_id = ? THEN chat_conversations.first_read_id ELSE chat_conversations.second_read_id END', [$viewer->id])])
            ->orderByDesc('last_message_at')->orderByDesc('id');
    }

    public function unreadCount(User $viewer): int
    {
        return (int) DB::query()->fromSub($this->inbox($viewer)->reorder(), 'inbox')->sum('unread_count');
    }

    public function authorize(ChatConversation $conversation, User $viewer): User
    {
        abort_unless(in_array($viewer->id, [$conversation->first_user_id, $conversation->second_user_id], true), 404);
        $partner = $conversation->partner($viewer);
        abort_if($partner->is_locked || $viewer->isBlockedWith($partner), 404);

        return $partner;
    }

    public function lockUsers(User $first, User $second): void
    {
        User::whereKey([$first->id, $second->id])->orderBy('id')->lockForUpdate()->get();
        abort_if($first->fresh()->is_locked || $second->fresh()->is_locked || $first->isBlockedWith($second), 404);
    }
}

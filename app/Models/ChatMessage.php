<?php

namespace App\Models;

use Database\Factories\ChatMessageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['chat_conversation_id', 'sender_id', 'client_token', 'reply_to_id', 'body', 'kind', 'attachment_path', 'attachment_mime', 'duration_ms', 'sticker'])]
class ChatMessage extends Model
{
    /** @use HasFactory<ChatMessageFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return ['reactions' => 'array', 'edited_at' => 'datetime', 'recalled_at' => 'datetime'];
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(ChatConversation::class, 'chat_conversation_id');
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function replyTo(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reply_to_id');
    }

    public function preview(): string
    {
        if ($this->recalled_at) {
            return 'Tin nhắn đã được thu hồi';
        }

        return $this->body !== null && $this->body !== '' ? $this->body : match ($this->kind) {
            'image' => 'Đã gửi một ảnh', 'voice' => 'Đã gửi tin nhắn thoại', 'sticker' => 'Đã gửi sticker', default => '',
        };
    }
}

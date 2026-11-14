<?php

namespace App\Services;

use App\Models\Post;
use App\Models\User;
use App\Notifications\ModerationDecision;

class CommunityModeration
{
    public function hide(Post $post, User $admin, string $reason): void
    {
        abort_if($post->trashed(), 422, 'Nội dung này đã bị ẩn hoặc đã được tác giả xóa.');
        $post->forceFill(['moderation_hidden_at' => now()])->save();
        $post->delete();
        app(ModerationAudit::class)->record($admin, 'post', $post->id, 'hide_thread', $reason, ['hidden' => false], ['hidden' => true]);
        $post->author?->notify(new ModerationDecision('Bài đăng / trả lời #'.$post->id.' của bạn đã bị ẩn.', $reason));
    }

    public function restore(Post $post, User $admin, string $reason): void
    {
        abort_unless($post->trashed() && $post->moderation_hidden_at, 422, 'Chỉ khôi phục nội dung do quản trị viên ẩn.');
        $post->forceFill(['moderation_hidden_at' => null])->save();
        $post->restore();
        app(ModerationAudit::class)->record($admin, 'post', $post->id, 'restore_thread', $reason, ['hidden' => true], ['hidden' => false]);
        $post->author?->notify(new ModerationDecision('Bài đăng / trả lời #'.$post->id.' của bạn đã được khôi phục.', $reason));
    }

    public function lock(User $user, User $admin, string $reason, ?string $expiresAt): void
    {
        abort_if($user->isAdmin(), 403, 'Không thể khóa tài khoản quản trị viên.');
        $before = ['is_locked' => $user->is_locked, 'lock_expires_at' => $user->lock_expires_at?->toIso8601String()];
        $user->forceFill(['is_locked' => true, 'lock_reason' => $reason, 'lock_expires_at' => $expiresAt])->save();
        app(ModerationAudit::class)->record($admin, 'user', $user->id, 'lock_user', $reason, $before, ['is_locked' => true, 'lock_expires_at' => $expiresAt]);
        $user->notify(new ModerationDecision('Tài khoản của bạn đã bị khóa'.($expiresAt ? ' đến '.$user->lock_expires_at->format('d/m/Y H:i') : ' vô thời hạn').'.', $reason));
    }
}

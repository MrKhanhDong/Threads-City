<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['reporter_id', 'post_id', 'reported_user_id', 'reason', 'details', 'snapshot'])]
class ContentReport extends Model
{
    /** @return array<string, string> */
    public static function reasons(): array
    {
        $custom = Setting::where('key', 'report_reason_labels')->value('value');

        return array_replace(self::REASONS, array_intersect_key(json_decode($custom ?? '{}', true) ?: [], self::REASONS));
    }

    public const REASONS = [
        'spam' => 'Spam hoặc lừa đảo',
        'harassment' => 'Quấy rối hoặc bắt nạt',
        'hate' => 'Ngôn từ thù địch',
        'other' => 'Vi phạm khác',
    ];

    protected function casts(): array
    {
        return ['handled_at' => 'datetime'];
    }

    public function outcomeMessage(): string
    {
        return match ($this->action) {
            'hide_thread' => 'Bài viết bạn báo cáo đã được ẩn.',
            'lock_user' => 'Tài khoản bạn báo cáo đã bị khóa.',
            'dismiss' => 'Báo cáo của bạn đã được xem xét và bác bỏ.',
            'resolve' => 'Báo cáo đã được xem xét. Nội dung được giữ nguyên.',
            default => $this->status === 'pending' ? 'Báo cáo đang chờ quản trị viên xem xét.' : 'Báo cáo của bạn đã được xem xét.',
        };
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class)->withTrashed();
    }

    public function reportedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_user_id');
    }

    public function handler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by');
    }
}

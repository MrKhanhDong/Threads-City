<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class ModerationAudit
{
    public const ACTIONS = [
        'hide_thread' => 'Ẩn bài đăng / trả lời',
        'restore_thread' => 'Khôi phục bài đăng / trả lời',
        'lock_user' => 'Khóa tài khoản',
        'unlock_user' => 'Mở khóa tài khoản',
        'verify_user' => 'Cấp tích xanh',
        'unverify_user' => 'Gỡ tích xanh',
        'moderate_comment' => 'Kiểm duyệt bình luận',
        'delete_comment' => 'Xóa bình luận',
        'resolve_comment_report' => 'Xử lý báo cáo bình luận',
        'resolve' => 'Giữ nguyên nội dung',
        'dismiss' => 'Bác bỏ báo cáo',
        'update_settings' => 'Cập nhật cài đặt cộng đồng',
    ];

    /** @param array<string, mixed> $before @param array<string, mixed> $after */
    public function record(User $admin, string $type, int $id, string $action, string $reason, array $before, array $after): void
    {
        DB::table('moderation_logs')->insert([
            'admin_id' => $admin->id, 'target_type' => $type, 'target_id' => $id,
            'action' => $action, 'reason' => $reason,
            'before_state' => json_encode($before, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
            'after_state' => json_encode($after, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
            'created_at' => now(),
        ]);
    }
}

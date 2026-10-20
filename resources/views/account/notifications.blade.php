@extends('layouts.threads')

@php($pageTitle = 'Thông báo')

@section('content')
<div class="py-3">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom border-dark">
        <div>
            <h4 class="text-white fs-5 fw-bold mb-0">Hoạt động & Thông báo</h4>
            <small class="text-secondary">{{ $unread > 0 ? $unread . ' thông báo chưa đọc' : 'Tất cả thông báo đã được xem' }}</small>
        </div>
        @if($unread > 0)
            <form method="post" action="{{ route('notifications.read-all') }}">
                @csrf
                <button type="submit" class="btn-threads-outline px-3 py-1 small">
                    Đánh dấu tất cả đã đọc
                </button>
            </form>
        @endif
    </div>

    <!-- Notifications List -->
    <div class="notifications-threads-list">
        @forelse($notifications as $notification)
            @php($reply = $comments->get((int) $notification->data['comment_id']))
            @if($reply)
                <form method="post" action="{{ route('notifications.open', $notification->id) }}" class="notification-item-card {{ $notification->read_at ? '' : 'unread' }}">
                    @csrf
                    <button type="submit" class="notification-item-btn">
                        @if($reply->user->avatar_path)
                            <img src="{{ route('avatar.show', $reply->user->id) }}" alt="{{ $reply->user->name }}" class="user-avatar-round">
                        @else
                            <div class="user-avatar-letter">{{ mb_substr($reply->user->name, 0, 1) }}</div>
                        @endif
                        <div class="notification-item-text">
                            <div class="text-white">
<<<<<<< HEAD
                                <strong>{{ $reply->user->name }}</strong> 
=======
                                <strong>{{ $reply->user->name }}</strong>
>>>>>>> Phuong
                                <span class="text-secondary">đã trả lời bình luận của bạn:</span>
                            </div>
                            <div class="text-secondary small mt-1 font-monospace" style="overflow-wrap: anywhere;">
                                "{{ Str::limit(strip_tags($reply->body), 100) }}"
                            </div>
                            <div class="notification-meta mt-1">
                                <span>{{ $notification->created_at->diffForHumans() }}</span>
                                @if(!$notification->read_at)
                                    <span class="unread-badge">Mới</span>
<<<<<<< HEAD
=======
                                @else
                                    <span>Đã đọc</span>
>>>>>>> Phuong
                                @endif
                            </div>
                        </div>
                    </button>
                </form>
            @endif
        @empty
            <div class="text-center py-5">
                <div class="mb-2 text-secondary" style="font-size: 36px;">🔔</div>
                <h6 class="text-white fw-bold mb-1">Chưa có thông báo nào</h6>
<<<<<<< HEAD
                <p class="text-secondary small">Khi có ai đó thích, trả lời hoặc tương tác với bài viết của bạn, thông báo sẽ hiển thị ở đây.</p>
=======
                <p class="text-secondary small">Khi có ai đó trả lời bình luận của bạn, thông báo sẽ hiển thị ở đây.</p>
                <a class="btn-threads-outline" href="{{ route('activity.index') }}">Xem tất cả hoạt động</a>
>>>>>>> Phuong
            </div>
        @endforelse
    </div>

    <!-- Pagination -->
    <div class="mt-4">
        {{ $notifications->links() }}
    </div>
</div>

<style>
    .notification-item-card {
        border-bottom: 1px solid var(--border-color);
        transition: background 0.15s;
    }
    .notification-item-card.unread {
        background: rgba(255, 255, 255, 0.03);
    }
    .notification-item-card:hover {
        background: rgba(255, 255, 255, 0.05);
    }
    .notification-item-btn {
        width: 100%;
        display: flex;
        align-items: flex-start;
        gap: 14px;
        padding: 14px 8px;
        background: none;
        border: none;
        text-align: left;
        cursor: pointer;
    }
    .notification-item-text {
        flex: 1;
        min-width: 0;
        font-size: 14px;
    }
    .notification-meta {
        font-size: 12px;
        color: var(--text-secondary);
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .unread-badge {
        background: var(--accent-blue);
        color: #fff;
        padding: 1px 6px;
        border-radius: 9999px;
        font-size: 10px;
        font-weight: 600;
    }
</style>
@endsection

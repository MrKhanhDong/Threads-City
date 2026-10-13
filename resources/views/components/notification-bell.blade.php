@auth
@php($unreadCount = app(\App\Services\CommentThreads::class)->notifications(auth()->user())->whereNull('read_at')->count())
<div class="dropdown notification-bell" data-notification-bell data-url="{{ route('notifications.index') }}">
    <button type="button" class="notification-button" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false" aria-label="Thông báo{{ $unreadCount ? ': '.$unreadCount.' chưa đọc' : '' }}">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9Z"/><path d="M10 21h4"/></svg>
        <span class="notification-count" data-notification-count @if(!$unreadCount) hidden @endif>{{ $unreadCount > 99 ? '99+' : $unreadCount }}</span>
    </button>
    <div class="dropdown-menu dropdown-menu-end notification-menu">
        <div class="notification-menu-heading"><strong>Thông báo</strong><a class="small text-link" href="{{ route('notifications.index') }}">Xem tất cả</a></div>
        <form method="post" action="{{ route('notifications.read-all') }}" data-notifications-read-all>@csrf<button type="submit" class="link-button small">Đánh dấu tất cả đã đọc</button></form>
        <p class="small muted" data-notifications-status role="status" aria-live="polite">Nhấn chuông để tải thông báo.</p>
        <div data-notification-items></div>
    </div>
</div>
@endauth

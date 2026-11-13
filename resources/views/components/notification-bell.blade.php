@auth
@php($unreadCount = app(\App\Services\UserNotifications::class)->forUser(auth()->user())->whereNull('read_at')->count())
<div class="dropdown notification-bell" data-notification-bell data-url="{{ route('notifications.index') }}">
    <button type="button" class="notification-button" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false" aria-label="Thông báo{{ $unreadCount ? ': '.$unreadCount.' chưa đọc' : '' }}">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9Z"/><path d="M10 21h4"/></svg>
        <span class="notification-count" data-notification-count @if(!$unreadCount) hidden @endif>{{ $unreadCount > 99 ? '99+' : $unreadCount }}</span>
    </button>
    <section id="notification-popup" class="dropdown-menu dropdown-menu-end notification-menu" aria-label="Thông báo mới nhất">
        <div class="notification-menu-heading">
            <strong>Thông báo</strong>
            <form method="post" action="{{ route('notifications.read-all') }}" data-notifications-read-all>@csrf<button type="submit" @disabled(!$unreadCount)>Đọc tất cả</button></form>
        </div>
        <p class="notification-status" data-notifications-status role="status" aria-live="polite">Đang tải thông báo…</p>
        <button type="button" class="notification-retry" data-notifications-retry hidden>Thử lại</button>
        <div data-notification-items></div>
        <a class="notification-view-all" href="{{ route('notifications.index') }}">Xem tất cả hoạt động <x-journal-icon name="arrow" :size="16"/></a>
    </section>
</div>
@endauth

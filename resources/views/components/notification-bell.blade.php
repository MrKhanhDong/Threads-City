@auth
@php($unreadCount = auth()->user()->unreadNotifications()->count())
<div class="dropdown notification-bell" data-notification-bell data-url="{{ route('notifications.index', ['preview' => 1]) }}">
    <button type="button" class="journal-menu-button notification-button" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false" aria-controls="notification-popup" aria-label="Thông báo{{ $unreadCount ? ': '.$unreadCount.' chưa đọc' : '' }}">
        <x-journal-icon name="heart"/>
        <span id="sidebarNotificationBadge" class="notification-dot" data-notification-count @if(!$unreadCount) hidden @endif aria-hidden="true"></span>
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

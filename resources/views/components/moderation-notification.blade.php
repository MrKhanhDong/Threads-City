@props(['notification', 'compact' => false])
<form data-live-key="notification-{{ $notification->id }}" method="post" action="{{ route('notifications.open', $notification->id) }}" @class(['notification-preview-row', 'moderation-notification', 'notification-item-card' => !$compact, 'is-unread' => !$notification->read_at])>
    @csrf
    <button type="submit" class="notification-preview-item">
        <span class="notification-preview-portrait"><span class="notification-preview-avatar moderation-notification-icon"><x-journal-icon name="info" :size="22"/></span></span>
        <span class="notification-preview-copy">
            <strong class="moderation-notification-title">Thông báo kiểm duyệt</strong>
            <span class="moderation-notification-message">{{ $compact ? \Illuminate\Support\Str::limit($notification->data['message'] ?? '', 150) : ($notification->data['message'] ?? '') }}</span>
            @if(!empty($notification->data['reason']))<span class="moderation-notification-reason"><span>Lý do:</span> {{ $compact ? \Illuminate\Support\Str::limit($notification->data['reason'], 180) : $notification->data['reason'] }}</span>@endif
            <small><time datetime="{{ $notification->created_at->toIso8601String() }}" title="{{ $notification->created_at->format('d/m/Y H:i') }}">{{ $notification->created_at->diffForHumans() }}</time> · {{ $notification->read_at ? 'Đã đọc' : 'Chưa đọc' }}</small>
        </span>
        @unless($notification->read_at)<span class="notification-preview-unread" aria-label="Chưa đọc"></span>@endunless
    </button>
</form>

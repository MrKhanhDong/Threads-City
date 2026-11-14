@props(['notification'])
<form data-live-key="notification-{{ $notification->id }}" method="post" action="{{ route('notifications.open', $notification->id) }}" class="notification-item {{ $notification->read_at ? '' : 'is-unread' }}">
    @csrf
    <button type="submit"><span><strong>Thông báo kiểm duyệt</strong><span class="preserve-lines d-block">{{ $notification->data['message'] ?? '' }}</span><span class="preserve-lines d-block">Lý do: {{ $notification->data['reason'] ?? '' }}</span><small>{{ $notification->created_at->format('d/m/Y H:i') }} · {{ $notification->read_at ? 'Đã đọc' : 'Chưa đọc' }}</small></span></button>
</form>

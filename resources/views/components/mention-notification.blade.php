@props(['notification', 'context'])
<form method="post" action="{{ route('notifications.open', $notification->id) }}" class="notification-item {{ $notification->read_at ? '' : 'is-unread' }}">
    @csrf
    <button type="submit"><x-avatar :user="$context['actor']"/><span><strong>{{ $context['actor']->name }}</strong> đã nhắc đến bạn trong một {{ $context['is_comment'] ? 'bình luận' : 'bài viết' }}.<small>{{ $notification->created_at->format('d/m/Y H:i') }} · {{ $notification->read_at ? 'Đã đọc' : 'Chưa đọc' }}</small></span></button>
</form>

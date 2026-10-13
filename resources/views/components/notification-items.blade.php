@forelse($notifications as $notification)
    @php($reply = $comments->get((int) $notification->data['comment_id']))
    @if($reply)
    <form method="post" action="{{ route('notifications.open', $notification->id) }}" class="notification-item {{ $notification->read_at ? '' : 'is-unread' }}">
        @csrf
        <button type="submit">
            <x-avatar :user="$reply->user" />
            <span><span><strong>{{ $reply->user->name }}</strong> đã trả lời bình luận của bạn trong <strong>{{ $reply->post->title }}</strong>.</span><small>{{ $notification->created_at->format('d/m/Y H:i') }} · {{ $notification->read_at ? 'Đã đọc' : 'Chưa đọc' }}</small></span>
        </button>
    </form>
    @endif
@empty
    <p class="muted small">Chưa có thông báo trả lời bình luận.</p>
@endforelse

@forelse($notifications as $notification)
    @if($notification->type === \App\Notifications\ModerationDecision::class)
        <x-moderation-notification :notification="$notification"/>
        @continue
    @endif
    @php($reply = $comments->get((int) ($notification->data['comment_id'] ?? 0)))
    @php($report = ($reportResults ?? collect())->get((int) ($notification->data['report_id'] ?? 0)))
    @if($notification->type === \App\Notifications\ReportHandled::class && $report)
    <form method="post" action="{{ route('notifications.open', $notification->id) }}" class="notification-item {{ $notification->read_at ? '' : 'is-unread' }}">
        @csrf
        <button type="submit"><span class="notification-result-icon" aria-hidden="true"><x-journal-icon name="info" :size="22"/></span><span><x-report-notification-copy :report="$report"/><small>{{ $notification->created_at->format('d/m/Y H:i') }} · {{ $notification->read_at ? 'Đã đọc' : 'Chưa đọc' }}</small></span></button>
    </form>
    @elseif($notification->type === \App\Notifications\Mentioned::class && ($mentionContexts ?? collect())->has((int) ($notification->data['mention_id'] ?? 0)))
        <x-mention-notification :notification="$notification" :context="$mentionContexts->get((int) $notification->data['mention_id'])"/>
    @elseif($reply)
    <form method="post" action="{{ route('notifications.open', $notification->id) }}" class="notification-item {{ $notification->read_at ? '' : 'is-unread' }}">
        @csrf
        <button type="submit">
            <x-avatar :user="$reply->user" />
            <span><span><strong>{{ $reply->user->name }}</strong> đã trả lời bình luận của bạn trong <strong>{{ $reply->post->title }}</strong>.</span><small>{{ $notification->created_at->format('d/m/Y H:i') }} · {{ $notification->read_at ? 'Đã đọc' : 'Chưa đọc' }}</small></span>
        </button>
    </form>
    @endif
@empty
    <p class="muted small">Chưa có thông báo nào.</p>
@endforelse

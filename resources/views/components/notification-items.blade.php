@forelse($notifications as $notification)
    @php
        $data = $notification->data ?? [];
        $actorName = $data['actor_name'] ?? 'Một người dùng';
        $description = match ($data['action'] ?? '') {
            'like' => 'đã thích bài viết của bạn.',
            'reply' => 'đã trả lời bài viết của bạn.',
            'follow' => 'đã bắt đầu theo dõi bạn.',
            'mention' => 'đã nhắc đến bạn trong một bài viết.',
            'repost' => 'đã đăng lại bài viết của bạn.',
            'quote' => 'đã trích dẫn bài viết của bạn.',
            default => 'đã tương tác với bạn.',
        };
    @endphp
    <a href="{{ route('notifications.open', $notification->id) }}" class="notification-preview-item {{ $notification->read_at ? '' : 'is-unread' }}">
        @if(!empty($data['actor_avatar']))
            <img class="notification-preview-avatar" src="{{ $data['actor_avatar'] }}" alt="" loading="lazy">
        @else
            <span class="notification-preview-avatar" aria-hidden="true">{{ mb_substr($actorName, 0, 1) }}</span>
        @endif
        <span class="notification-preview-copy">
            <span><strong>{{ $actorName }}</strong> {{ $description }}</span>
            @if(!empty($data['content_snippet']))<span class="notification-preview-snippet">{{ $data['content_snippet'] }}</span>@endif
            <small><time datetime="{{ $notification->created_at->toIso8601String() }}">{{ $notification->created_at->diffForHumans() }}</time> · {{ $notification->read_at ? 'Đã đọc' : 'Chưa đọc' }}</small>
        </span>
        @if(!$notification->read_at)<span class="notification-preview-unread" aria-hidden="true"></span>@endif
    </a>
@empty
    <div class="notification-preview-empty"><x-journal-icon name="heart" :size="30"/><strong>Chưa có thông báo nào</strong><p>Khi có tương tác mới, thông báo sẽ xuất hiện ở đây.</p></div>
@endforelse

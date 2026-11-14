@forelse($notifications as $notification)
    @if($notification->type === \App\Notifications\ModerationDecision::class)
        <x-moderation-notification :notification="$notification"/>
        @continue
    @endif
    @php
        $data = $notification->data ?? [];
        $reply = $comments->get((int) ($data['comment_id'] ?? 0));
        $report = ($reportResults ?? collect())->get((int) ($data['report_id'] ?? 0));
        $mention = ($mentionContexts ?? collect())->get((int) ($data['mention_id'] ?? 0));
        $actor = $mention['actor'] ?? $reply?->user;
        $actorName = $actor?->name ?? ($data['actor_name'] ?? 'Threads');
        $avatarUrl = $actor?->avatar_path ? route('avatar.show', $actor) : ($data['actor_avatar'] ?? null);
        $action = $data['action'] ?? 'reply';
        $actionCopy = match ($action) {
            'like' => 'đã thích bài viết của bạn.',
            'follow' => 'đã theo dõi bạn.',
            'repost' => 'đã đăng lại bài viết của bạn.',
            'quote' => 'đã trích dẫn bài viết của bạn.',
            'mention' => 'đã nhắc đến bạn.',
            default => 'đã trả lời bạn.',
        };
        $actionIcon = match ($action) {
            'like' => 'heart',
            'follow' => 'person',
            'repost', 'quote' => 'repost',
            default => 'message',
        };
    @endphp
    <form method="post" action="{{ route('notifications.open', $notification->id) }}" class="notification-preview-row {{ $notification->read_at ? '' : 'is-unread' }}">
        @csrf
        <button type="submit" class="notification-preview-item">
            <span class="notification-preview-portrait">
                @if($report)
                    <span class="notification-preview-avatar"><x-journal-icon name="info" :size="22"/></span>
                @elseif($avatarUrl)
                    <img class="notification-preview-avatar" src="{{ $avatarUrl }}" alt="" loading="lazy">
                @else
                    <span class="notification-preview-avatar">{{ mb_substr($actorName, 0, 1) }}</span>
                @endif
                @unless($report)<span class="notification-preview-action" aria-hidden="true"><x-journal-icon :name="$actionIcon" :size="11"/></span>@endunless
            </span>
            <span class="notification-preview-copy">
                <span>@if($report)<x-report-notification-copy :report="$report"/>@else<strong>{{ $actorName }}</strong> {{ $mention ? 'đã nhắc đến bạn trong một '.($mention['is_comment'] ? 'bình luận.' : 'bài viết.') : $actionCopy }}@endif</span>
                @if(!empty($data['content_snippet']))<span class="notification-preview-snippet">{{ \Illuminate\Support\Str::limit($data['content_snippet'], 100) }}</span>@endif
                <small><time datetime="{{ $notification->created_at->toIso8601String() }}">{{ $notification->created_at->diffForHumans() }}</time></small>
            </span>
            @unless($notification->read_at)<span class="notification-preview-unread" aria-label="Chưa đọc"></span>@endunless
        </button>
    </form>
@empty
    <div class="notification-preview-empty">
        <span class="notification-empty-icon" aria-hidden="true"><x-journal-icon name="heart" :size="28"/></span>
        <strong>Chưa có thông báo nào</strong>
        <p>Lượt thích, trả lời và lượt theo dõi mới<br>sẽ xuất hiện ở đây.</p>
    </div>
@endforelse

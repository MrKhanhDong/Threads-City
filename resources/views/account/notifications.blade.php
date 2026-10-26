@extends('layouts.threads')

@php
    $pageTitle = 'Hoạt động & Thông báo';
@endphp

@section('content')
<div class="py-3">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-3" data-live-region="notification-header">
        <div>
            <h4 class="text-white fs-5 fw-bold mb-0">Hoạt động</h4>
            <small class="text-secondary">{{ $unread > 0 ? $unread . ' thông báo chưa đọc' : 'Tất cả thông báo đã xem' }}</small>
        </div>
        @if($unread > 0)
            <form data-live-submit method="post" action="{{ route('notifications.read-all') }}">
                @csrf
                <button type="submit" class="btn-threads-outline px-3 py-1 small rounded-pill">
                    Đánh dấu tất cả đã đọc
                </button>
            </form>
        @endif
    </div>

    <!-- Filter Tabs (Threads Pill Style) -->
    <div class="notification-tabs-wrapper mb-3">
        <a href="{{ route('notifications.index', ['tab' => 'all']) }}" class="notif-tab-pill {{ ($tab ?? 'all') === 'all' ? 'active' : '' }}">
            Tất cả
        </a>
        <a href="{{ route('notifications.index', ['tab' => 'likes']) }}" class="notif-tab-pill {{ in_array(($tab ?? ''), ['like', 'likes'], true) ? 'active' : '' }}">
            Lượt thích
        </a>
        <a href="{{ route('notifications.index', ['tab' => 'replies']) }}" class="notif-tab-pill {{ in_array(($tab ?? ''), ['reply', 'replies'], true) ? 'active' : '' }}">
            Trả lời
        </a>
        <a href="{{ route('notifications.index', ['tab' => 'mentions']) }}" class="notif-tab-pill {{ in_array(($tab ?? ''), ['mention', 'mentions'], true) ? 'active' : '' }}">
            Nhắc tên
        </a>
        <a href="{{ route('notifications.index', ['tab' => 'follows']) }}" class="notif-tab-pill {{ in_array(($tab ?? ''), ['follow', 'follows'], true) ? 'active' : '' }}">
            Theo dõi
        </a>
        <a href="{{ route('notifications.index', ['tab' => 'reposts']) }}" class="notif-tab-pill {{ in_array(($tab ?? ''), ['repost', 'reposts'], true) ? 'active' : '' }}">
            Đăng lại
        </a>
    </div>

    <!-- Notifications List -->
    <div class="notifications-threads-list" data-live-region="notifications" data-live-list>
        @forelse($notifications as $notification)
            @php
                $data = $notification->data ?? [];
                $action = $data['action'] ?? 'reply';
                $actorName = $data['actor_name'] ?? 'Một người dùng';
                $actorHandle = $data['actor_handle'] ?? '';
                $actorAvatar = $data['actor_avatar'] ?? null;
                $actorId = $data['actor_id'] ?? null;
                $contentSnippet = $data['content_snippet'] ?? null;
                $targetUrl = route('notifications.open', $notification->id);
            @endphp

            <div data-live-key="notification-{{ $notification->id }}" class="notification-item-card {{ $notification->read_at ? '' : 'unread' }}">
                <a href="{{ $targetUrl }}" class="notification-item-link">
                    <!-- Left: Avatar with Action Badge -->
                    <div class="notification-avatar-wrap">
                        @if($actorAvatar)
                            <img src="{{ $actorAvatar }}" alt="{{ $actorName }}" class="user-avatar-round notif-avatar" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;">
                        @else
                            <div class="user-avatar-letter notif-avatar" style="width: 40px; height: 40px; border-radius: 50%;">{{ mb_substr($actorName, 0, 1) }}</div>
                        @endif

                        <!-- Action Badge Icon -->
                        <div class="notif-action-badge action-{{ $action }}">
                            @if($action === 'like')
                                <svg width="10" height="10" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
                                </svg>
                            @elseif($action === 'reply')
                                <svg width="10" height="10" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                                </svg>
                            @elseif($action === 'follow')
                                <svg width="10" height="10" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                                    <circle cx="8.5" cy="7" r="4"></circle>
                                    <line x1="20" y1="8" x2="20" y2="14" stroke="#fff" stroke-width="2.5"></line>
                                    <line x1="23" y1="11" x2="17" y2="11" stroke="#fff" stroke-width="2.5"></line>
                                </svg>
                            @elseif($action === 'mention')
                                <span class="notif-mention-at">@</span>
                            @elseif($action === 'repost' || $action === 'quote')
                                <svg width="10" height="10" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M17 1l4 4-4 4"></path>
                                    <path d="M3 11V9a4 4 0 0 1 4-4h14"></path>
                                    <path d="M7 23l-4-4 4-4"></path>
                                    <path d="M21 13v2a4 4 0 0 1-4 4H3"></path>
                                </svg>
                            @else
                                <svg width="10" height="10" viewBox="0 0 24 24" fill="currentColor">
                                    <circle cx="12" cy="12" r="10"></circle>
                                </svg>
                            @endif
                        </div>
                    </div>

                    <!-- Middle: Notification details -->
                    <div class="notification-body">
                        <div class="notification-headline">
                            <span class="text-white fw-bold">{{ $actorName }}</span>
                            @if($actorHandle)
                                <span class="text-secondary small">{{ $actorHandle }}</span>
                            @endif
                            <span class="notification-time">&bull; {{ $notification->created_at->diffForHumans(null, true) }}</span>
                        </div>

                        <div class="notification-action-desc text-secondary">
                            @if($action === 'like')
                                đã thích bài viết của bạn.
                            @elseif($action === 'reply')
                                đã trả lời bài viết của bạn:
                            @elseif($action === 'follow')
                                đã bắt đầu theo dõi bạn.
                            @elseif($action === 'mention')
                                đã nhắc đến bạn trong một bài viết:
                            @elseif($action === 'repost')
                                đã đăng lại bài viết của bạn.
                            @elseif($action === 'quote')
                                đã trích dẫn bài viết của bạn:
                            @else
                                đã tương tác với bạn.
                            @endif
                        </div>

                        @if($contentSnippet)
                            <div class="notification-snippet text-white-50 mt-1">
                                "{{ $contentSnippet }}"
                            </div>
                        @endif
                    </div>

                    <!-- Right: Unread dot or Action Button -->
                    <div class="notification-right-col">
                        @if($action === 'follow' && $actorId && auth()->id() !== $actorId)
                            @php $isFollowingActor = auth()->user()->isFollowing(\App\Models\User::find($actorId) ?? new \App\Models\User()); @endphp
                            <button type="button" 
                                class="btn-ajax-avatar-follow btn-notif-follow {{ $isFollowingActor ? 'following' : '' }}" 
                                data-user-id="{{ $actorId }}"
                                onclick="event.preventDefault(); event.stopPropagation();">
                                {{ $isFollowingActor ? 'Đang theo dõi' : 'Theo dõi lại' }}
                            </button>
                        @endif

                        @if(!$notification->read_at)
                            <span class="notif-unread-dot" title="Chưa đọc"></span>
                        @endif
                    </div>
                </a>
            </div>
        @empty
            <div class="text-center py-5">
                <div class="mb-3 text-secondary" style="font-size: 40px;">
                    @if(in_array(($tab ?? ''), ['like', 'likes'], true))
                        ❤️
                    @elseif(in_array(($tab ?? ''), ['reply', 'replies'], true))
                        💬
                    @elseif(in_array(($tab ?? ''), ['mention', 'mentions'], true))
                        🏷️
                    @elseif(in_array(($tab ?? ''), ['follow', 'follows'], true))
                        👥
                    @else
                        🔔
                    @endif
                </div>
                <h6 class="text-white fw-bold mb-1">Chưa có thông báo nào</h6>
                <p class="text-secondary small">
                    @if(in_array(($tab ?? ''), ['like', 'likes'], true))
                        Khi ai đó thích bài viết của bạn, thông báo sẽ hiển thị ở đây.
                    @elseif(in_array(($tab ?? ''), ['reply', 'replies'], true))
                        Khi ai đó trả lời bài viết của bạn, bạn sẽ thấy câu trả lời ở đây.
                    @elseif(in_array(($tab ?? ''), ['mention', 'mentions'], true))
                        Khi bạn được nhắc tên (@username), thông báo sẽ xuất hiện ở đây.
                    @elseif(in_array(($tab ?? ''), ['follow', 'follows'], true))
                        Khi có người theo dõi mới, họ sẽ xuất hiện tại đây.
                    @else
                        Khi có tương tác mới (thích, trả lời, nhắc tên, theo dõi), thông báo sẽ hiển thị tại đây.
                    @endif
                </p>
            </div>
        @endforelse
    </div>

    <!-- Pagination -->
    @if($notifications->hasPages())
        <div class="mt-4 d-flex justify-content-center">
            {{ $notifications->links() }}
        </div>
    @endif
</div>

<style>
    /* Horizontal Pill Tabs */
    .notification-tabs-wrapper {
        display: flex;
        gap: 8px;
        overflow-x: auto;
        padding-bottom: 6px;
        scrollbar-width: none;
    }
    .notification-tabs-wrapper::-webkit-scrollbar {
        display: none;
    }
    .notif-tab-pill {
        padding: 6px 16px;
        border-radius: 9999px;
        font-size: 14px;
        font-weight: 600;
        text-decoration: none;
        color: #777777;
        background: #181818;
        border: 1px solid #2a2a2a;
        white-space: nowrap;
        transition: all 0.15s ease;
    }
    .notif-tab-pill:hover {
        color: #ffffff;
        background: #222222;
        border-color: #383838;
    }
    .notif-tab-pill.active {
        color: #000000;
        background: #ffffff;
        border-color: #ffffff;
    }

    /* Notification List & Card */
    .notifications-threads-list {
        border-top: 1px solid var(--border-color);
    }
    .notification-item-card {
        border-bottom: 1px solid var(--border-color);
        transition: background 0.15s ease;
    }
    .notification-item-card.unread {
        background: rgba(0, 149, 246, 0.04);
    }
    .notification-item-card:hover {
        background: rgba(255, 255, 255, 0.04);
    }
    .notification-item-link {
        display: flex;
        align-items: flex-start;
        gap: 14px;
        padding: 14px 8px;
        text-decoration: none;
        color: inherit;
        width: 100%;
    }

    /* Avatar with Badge */
    .notification-avatar-wrap {
        position: relative;
        flex-shrink: 0;
    }
    .notif-avatar {
        width: 42px;
        height: 42px;
        border-radius: 50%;
        object-fit: cover;
    }
    .notif-action-badge {
        position: absolute;
        bottom: -2px;
        right: -3px;
        width: 18px;
        height: 18px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        border: 2px solid #101010;
        color: #ffffff;
    }
    .notif-action-badge.action-like {
        background: #ff2d55;
    }
    .notif-action-badge.action-reply {
        background: #0095f6;
    }
    .notif-action-badge.action-follow {
        background: #8b5cf6;
    }
    .notif-action-badge.action-mention {
        background: #10b981;
    }
    .notif-action-badge.action-repost,
    .notif-action-badge.action-quote {
        background: #00ba7c;
    }
    .notif-mention-at {
        font-size: 11px;
        font-weight: 800;
        line-height: 1;
    }

    /* Content Area */
    .notification-body {
        flex: 1;
        min-width: 0;
        font-size: 14px;
        line-height: 1.45;
    }
    .notification-headline {
        display: flex;
        align-items: baseline;
        gap: 6px;
        flex-wrap: wrap;
    }
    .notification-time {
        font-size: 12px;
        color: #777777;
    }
    .notification-action-desc {
        font-size: 14px;
        margin-top: 2px;
    }
    .notification-snippet {
        font-size: 13px;
        background: rgba(255, 255, 255, 0.03);
        border-left: 2px solid var(--accent-blue);
        padding: 4px 8px;
        border-radius: 4px;
        margin-top: 6px;
        word-break: break-word;
    }

    /* Right column & dot */
    .notification-right-col {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-shrink: 0;
        margin-left: auto;
    }
    .notif-unread-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background: var(--accent-blue, #0095f6);
        box-shadow: 0 0 6px var(--accent-blue, #0095f6);
    }
    .btn-notif-follow {
        padding: 5px 14px;
        font-size: 12px;
        font-weight: 600;
        border-radius: 10px;
        border: 1px solid #383838;
        background: #ffffff;
        color: #000000;
        cursor: pointer;
        transition: all 0.15s ease;
    }
    .btn-notif-follow:hover {
        opacity: 0.9;
    }
    .btn-notif-follow.following {
        background: transparent;
        color: #777777;
        border-color: #383838;
    }
</style>
@endsection

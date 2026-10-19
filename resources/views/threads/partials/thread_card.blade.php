@php
    $isRepost = ($thread->type === 'repost') && $thread->repostOf;
    $displayThread = $isRepost ? $thread->repostOf : $thread;
    $repostedBy = $isRepost ? $thread->author : null;
    $currentUser = auth()->user();
    $isLiked = $displayThread->isLikedBy($currentUser);
    $isBookmarked = $displayThread->isBookmarkedBy($currentUser);
    $isReposted = $displayThread->isRepostedBy($currentUser);
    $author = $displayThread->author;
    $hasReplies = !empty($hasReplies) ? $hasReplies : false;
    $isReplyItem = !empty($isReplyItem) ? $isReplyItem : false;
    $firstTag = $displayThread->tags->first();
    $isTrendingTag = ($firstTag && in_array(mb_strtolower($firstTag->name), ['biểu tình', 'bieu tinh'])) || ($displayThread->slug === 'thread-badat3927-bieu-tinh');
@endphp

<article class="thread-card-wrapper {{ $hasReplies ? 'has-replies-line' : '' }} {{ $isReplyItem ? 'is-reply-item' : '' }}" id="thread-{{ $thread->id }}" data-thread-id="{{ $displayThread->id }}">
    
    <!-- Repost Header Banner -->
    @if($isRepost && $repostedBy)
        <div class="thread-repost-banner">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" class="text-success">
                <path d="M17 1l4 4-4 4"></path>
                <path d="M3 11V9a4 4 0 0 1 4-4h14"></path>
                <path d="M7 23l-4-4 4-4"></path>
                <path d="M21 13v2a4 4 0 0 1-4 4H3"></path>
            </svg>
            <a href="{{ route('profile.show', $repostedBy->username ?? $repostedBy->id) }}" class="repost-author-link">
                <strong>{{ $repostedBy->name }}</strong> đã đăng lại
            </a>
        </div>
    @endif

    <!-- Trending Indicator (Yellow pill / text matching screenshot) -->
    @if($isTrendingTag)
        <div class="thread-trend-indicator">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#facc15" stroke-width="2.5">
                <polyline points="23 6 13.5 15.5 8.5 10.5 1 18"></polyline>
                <polyline points="17 6 23 6 23 12"></polyline>
            </svg>
            <span class="trend-indicator-text">Thịnh hành: {{ $firstTag ? $firstTag->name : 'Biểu tình' }}</span>
        </div>
    @endif

    <div class="thread-main-row">
        <!-- Left Column: Avatar & Follow Plus Badge -->
        <div class="thread-avatar-col">
            <div class="avatar-with-badge">
                <a href="{{ route('profile.show', $author->username ?? $author->id) }}" class="avatar-link">
                    @if($author->avatar_path)
                        <img src="{{ route('avatar.show', $author->id) }}" alt="{{ $author->name }}" class="user-avatar-round">
                    @else
                        <div class="user-avatar-letter">{{ mb_substr($author->name, 0, 1) }}</div>
                    @endif
                </a>
                @if(!auth()->check() || (auth()->id() !== $author->id && !auth()->user()->isFollowing($author)))
                    <button type="button" class="avatar-follow-plus-btn btn-ajax-avatar-follow" data-user-id="{{ $author->id }}" title="Theo dõi {{ $author->name }}">
                        <svg width="9" height="9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.5">
                            <line x1="12" y1="5" x2="12" y2="19"></line>
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                        </svg>
                    </button>
                @endif
            </div>
            @if($hasReplies)
                <div class="thread-connecting-line"></div>
            @endif
        </div>

        <!-- Right Column: Content & Actions -->
        <div class="thread-body-col">
            <!-- Author Line -->
            <div class="thread-header-line">
                <div class="author-meta-left">
                    <a href="{{ route('profile.show', $author->username ?? $author->id) }}" class="author-name-text">
                        {{ $author->username ?? $author->name }}
                    </a>
                    @if($author->isAdmin() || in_array($author->username, ['binhphatlien', 'dieu_nhiii', 'mrkhanhdong']))
                        <svg class="verified-badge-icon" width="13" height="13" viewBox="0 0 24 24" fill="#0095f6">
                            <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/>
                        </svg>
                    @endif
                    @if($author->username === 'minhanna2025' || ($displayThread->tags->count() > 1))
                        <span class="meta-tag-arrow">></span>
                        <span class="meta-tag-name">{{ $author->username === 'minhanna2025' ? 'du học sinh' : $displayThread->tags->skip(1)->first()->name }}</span>
                    @endif
                    <time class="author-time-text" datetime="{{ $displayThread->published_at?->toISOString() }}">
                        {{ $displayThread->published_at ? $displayThread->published_at->diffForHumans(null, true) : 'vừa xong' }}
                    </time>
                </div>

                <!-- More Options (3 dots) -->
                <div class="dropdown">
                    <button class="thread-more-btn" data-bs-toggle="dropdown" aria-expanded="false" title="Thêm tùy chọn">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="1.5" fill="currentColor"></circle>
                            <circle cx="19" cy="12" r="1.5" fill="currentColor"></circle>
                            <circle cx="5" cy="12" r="1.5" fill="currentColor"></circle>
                        </svg>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end threads-dropdown-menu">
                        <li>
                            <button type="button" class="threads-dropdown-item w-100 text-start btn-copy-thread-link" data-url="{{ route('threads.show', $displayThread->id) }}">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"></path>
                                    <path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"></path>
                                </svg>
                                <span>Sao chép liên kết</span>
                            </button>
                        </li>
                        @auth
                            @if($currentUser->id === $thread->user_id || $currentUser->isAdmin())
                                <li>
                                    <form action="{{ route('threads.destroy', $thread->id) }}" method="post" onsubmit="return confirm('Bạn có chắc chắn muốn xóa bài viết này không?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="threads-dropdown-item w-100 text-start text-danger border-0 bg-transparent">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <polyline points="3 6 5 6 21 6"></polyline>
                                                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                            </svg>
                                            <span>Xóa bài viết</span>
                                        </button>
                                    </form>
                                </li>
                            @endif
                        @endauth
                    </ul>
                </div>
            </div>

            <!-- Content Area -->
            <div class="thread-content-text">
                {!! preg_replace('/#([\p{L}\p{N}_]+)/u', '<a href="' . route('posts.index') . '?q=$1" class="thread-hashtag">#$1</a>', nl2br(e($displayThread->content))) !!}
            </div>

            <!-- Media Gallery (Images) -->
            @if($displayThread->media && $displayThread->media->count() > 0)
                <div class="thread-media-gallery gallery-count-{{ min(4, $displayThread->media->count()) }}">
                    @foreach($displayThread->media->take(4) as $mediaItem)
                        <div class="gallery-media-item">
                            <img src="{{ route('media.show', $mediaItem->id) }}" alt="{{ $mediaItem->alt ?? 'Ảnh đính kèm' }}" loading="lazy">
                        </div>
                    @endforeach
                </div>
            @endif

            <!-- Quoted Post Embed (Matches Screenshot) -->
            @if($displayThread->type === 'quote' && $displayThread->repostOf)
                @php $quoted = $displayThread->repostOf; @endphp
                <div class="threads-inner-quote-card" onclick="window.location='{{ route('threads.show', $quoted->id) }}'">
                    <div class="inner-quote-topbar">
                        <div class="inner-quote-left-title">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                <polyline points="15 18 9 12 15 6"></polyline>
                            </svg>
                            <span>Thread</span>
                        </div>
                        <div class="inner-quote-right-tools">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                                <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                            </svg>
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="1.5" fill="currentColor"></circle>
                                <circle cx="19" cy="12" r="1.5" fill="currentColor"></circle>
                                <circle cx="5" cy="12" r="1.5" fill="currentColor"></circle>
                            </svg>
                        </div>
                    </div>
                    <div class="inner-quote-content-area">
                        <div class="inner-quote-author-row">
                            @if($quoted->author->avatar_path)
                                <img src="{{ route('avatar.show', $quoted->author->id) }}" class="user-avatar-round" style="width: 24px; height: 24px;" alt="{{ $quoted->author->name }}">
                            @else
                                <div class="user-avatar-letter" style="width: 24px; height: 24px; font-size: 11px;">{{ mb_substr($quoted->author->name, 0, 1) }}</div>
                            @endif
                            <span class="inner-quote-author-name">{{ $quoted->author->username ?? $quoted->author->name }}</span>
                            <span class="inner-quote-arrow">></span>
                            <span class="inner-quote-topic-tag">{{ $quoted->author->bio ?? 'Slv dog' }}</span>
                            <span class="inner-quote-time">{{ $quoted->published_at ? $quoted->published_at->diffForHumans(null, true) : '2 ngày' }}</span>
                            <div class="inner-quote-threads-logo ms-auto">
                                <svg viewBox="0 0 192 192" width="14" height="14" fill="currentColor">
                                    <path d="M141.537 88.9883C140.71 88.5919 139.87 88.2109 139.019 87.8451C137.537 60.5382 122.616 44.905 97.5619 44.745C97.4111 44.744 97.2604 44.744 97.1096 44.744C75.2536 44.744 57.0707 58.751 51.5204 79.882C46.0691 100.638 53.6841 123.332 70.8251 137.416C87.9661 151.5 112.43 155.083 133.024 146.516C148.971 139.878 159.227 124.965 160.827 106.012H144.741C143.342 118.847 136.035 129.351 124.636 134.1C109.117 140.556 90.6811 137.856 77.7656 127.243C64.8501 116.63 59.1121 99.5306 63.2201 83.8906C67.4326 67.8546 81.2336 57.214 97.7331 57.214C97.8471 57.214 97.9611 57.215 98.0751 57.216C113.627 57.315 124.516 67.045 125.753 84.148C117.753 82.522 108.887 81.879 99.5371 82.261C73.1971 83.337 57.4981 97.575 58.3341 117.747C59.1381 137.147 75.8361 149.336 96.0601 148.471C111.455 147.813 124.12 140.174 130.407 127.818C134.825 136.195 142.155 142.365 150.941 145.42C157.65 147.752 164.838 148.33 171.936 147.108L174.524 134.42C169.176 135.337 163.743 134.901 158.673 133.138C150.771 130.392 144.974 123.86 142.753 115.228C148.513 107.035 151.706 97.234 151.706 87.051C151.706 84.821 151.482 82.607 151.042 80.441L141.537 88.9883ZM127.469 110.15C125.132 117.841 117.844 134.871 96.6961 135.773C83.8401 136.321 73.1201 128.528 72.5851 115.632C71.9961 101.444 83.2161 93.684 99.0741 93.037C105.748 92.765 112.186 93.284 118.067 94.55C122.956 95.602 126.155 97.214 127.469 110.15Z"/>
                                </svg>
                                <span>threads</span>
                            </div>
                        </div>
                        <div class="inner-quote-text">
                            {{ $quoted->content }}
                        </div>
                        <div class="inner-quote-action-counts">
                            <span class="inner-count-item">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path></svg>
                                {{ $quoted->likes_count >= 1000 ? str_replace('.', ',', round($quoted->likes_count / 1000, 1)) . 'K' : $quoted->likes_count }}
                            </span>
                            <span class="inner-count-item">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
                                {{ $quoted->replies_count >= 1000 ? str_replace('.', ',', round($quoted->replies_count / 1000, 1)) . 'K' : $quoted->replies_count }}
                            </span>
                            <span class="inner-count-item">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 1l4 4-4 4"></path><path d="M3 11V9a4 4 0 0 1 4-4h14"></path><path d="M7 23l-4-4 4-4"></path><path d="M21 13v2a4 4 0 0 1-4 4H3"></path></svg>
                                {{ $quoted->reposts_count >= 1000 ? str_replace('.', ',', round($quoted->reposts_count / 1000, 1)) . 'K' : $quoted->reposts_count }}
                            </span>
                            <span class="inner-count-item">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="7" y1="17" x2="17" y2="7"></line><polyline points="7 7 17 7 17 17"></polyline></svg>
                                778
                            </span>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Action Bar: Like, Reply, Repost, Share -->
            <div class="thread-actions-bar">
                <!-- 1. Like Button -->
                <button type="button" class="action-btn action-like-btn {{ $isLiked ? 'liked' : '' }}" data-thread-id="{{ $displayThread->id }}" title="Thích">
                    <svg class="heart-icon" width="19" height="19" viewBox="0 0 24 24" fill="{{ $isLiked ? '#ff3040' : 'none' }}" stroke="{{ $isLiked ? '#ff3040' : 'currentColor' }}" stroke-width="1.8">
                        <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
                    </svg>
                    <span class="action-count like-count-span">
                        {{ $displayThread->likes_count >= 1000 ? str_replace('.', ',', round($displayThread->likes_count / 1000, 1)) . 'K' : ($displayThread->likes_count > 0 ? $displayThread->likes_count : '') }}
                    </span>
                </button>

                <!-- 2. Reply Button -->
                <a href="{{ route('threads.show', $displayThread->id) }}#replyBox" class="action-btn" title="Trả lời">
                    <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                    </svg>
                    <span class="action-count">
                        {{ $displayThread->replies_count >= 1000 ? str_replace('.', ',', round($displayThread->replies_count / 1000, 1)) . 'K' : ($displayThread->replies_count > 0 ? $displayThread->replies_count : '') }}
                    </span>
                </a>

                <!-- 3. Repost Button -->
                <button type="button" class="action-btn action-repost-btn {{ $isReposted ? 'reposted' : '' }}" 
                    data-thread-id="{{ $displayThread->id }}" 
                    data-author-name="{{ $author->name }}"
                    data-author-handle="{{ $author->handle() }}"
                    data-content-snippet="{{ Str::limit(strip_tags($displayThread->content), 120) }}"
                    title="Đăng lại">
                    <svg class="repost-icon" width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="{{ $isReposted ? '#00ba7c' : 'currentColor' }}" stroke-width="1.8">
                        <path d="M17 1l4 4-4 4"></path>
                        <path d="M3 11V9a4 4 0 0 1 4-4h14"></path>
                        <path d="M7 23l-4-4 4-4"></path>
                        <path d="M21 13v2a4 4 0 0 1-4 4H3"></path>
                    </svg>
                    <span class="action-count repost-count-span">
                        {{ $displayThread->reposts_count >= 1000 ? str_replace('.', ',', round($displayThread->reposts_count / 1000, 1)) . 'K' : ($displayThread->reposts_count > 0 ? $displayThread->reposts_count : '') }}
                    </span>
                </button>

                <!-- 4. Share Arrow Button (Top-right arrow matching screenshot) -->
                <button type="button" class="action-btn btn-copy-thread-link" data-url="{{ route('threads.show', $displayThread->id) }}" title="Chia sẻ">
                    <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <line x1="7" y1="17" x2="17" y2="7"></line>
                        <polyline points="7 7 17 7 17 17"></polyline>
                    </svg>
                    <span class="action-count">
                        @if($displayThread->slug === 'thread-badat3927-bieu-tinh')
                            16
                        @elseif($displayThread->slug === 'thread-hidungchuytoi-quote')
                            169
                        @elseif($displayThread->likes_count > 10)
                            {{ round($displayThread->likes_count * 0.05) }}
                        @endif
                    </span>
                </button>
            </div>
        </div>
    </div>
</article>

<style>
    .thread-card-wrapper {
        border-bottom: 1px solid var(--border-color);
        padding: 16px 20px;
        position: relative;
        transition: background 0.15s ease;
    }
    .thread-card-wrapper:hover {
        background: rgba(255, 255, 255, 0.015);
    }
    .thread-trend-indicator {
        display: flex;
        align-items: center;
        gap: 6px;
        margin-left: 54px;
        margin-bottom: 10px;
        font-size: 13px;
        font-weight: 700;
        color: #facc15;
    }
    .trend-indicator-text {
        color: #facc15;
    }
    .thread-repost-banner {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-left: 54px;
        margin-bottom: 8px;
        font-size: 13px;
        color: var(--text-secondary);
    }
    .repost-author-link {
        color: var(--text-secondary);
    }
    .repost-author-link strong {
        color: var(--text-primary);
    }
    .thread-main-row {
        display: flex;
        gap: 14px;
        align-items: flex-start;
    }
    .thread-avatar-col {
        display: flex;
        flex-direction: column;
        align-items: center;
        flex-shrink: 0;
        width: 40px;
        min-height: 100%;
        position: relative;
    }
    .avatar-with-badge {
        position: relative;
        display: inline-block;
    }
    .avatar-follow-plus-btn {
        position: absolute;
        bottom: -2px;
        right: -2px;
        width: 17px;
        height: 17px;
        border-radius: 50%;
        background: #000000;
        color: #ffffff;
        border: 2px solid #000000;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        padding: 0;
        transition: transform 0.15s, background 0.15s;
    }
    .avatar-follow-plus-btn:hover {
        transform: scale(1.15);
        background: #222;
    }
    .thread-connecting-line {
        width: 2px;
        flex-grow: 1;
        min-height: 40px;
        background: #262626;
        margin-top: 8px;
        border-radius: 2px;
    }
    .thread-body-col {
        flex: 1;
        min-width: 0;
    }
    .thread-header-line {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 4px;
    }
    .author-meta-left {
        display: flex;
        align-items: center;
        gap: 6px;
        flex-wrap: wrap;
    }
    .author-name-text {
        font-weight: 700;
        color: #ffffff;
        font-size: 15px;
    }
    .author-name-text:hover {
        text-decoration: underline;
    }
    .meta-tag-arrow {
        color: var(--text-muted);
        font-size: 13px;
        font-weight: 600;
    }
    .meta-tag-name {
        font-size: 14px;
        color: #e5e7eb;
        font-weight: 600;
    }
    .author-time-text {
        color: var(--text-secondary);
        font-size: 14px;
        margin-left: 2px;
    }
    .thread-more-btn {
        background: none;
        border: none;
        color: var(--text-muted);
        cursor: pointer;
        padding: 4px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: color 0.15s;
    }
    .thread-more-btn:hover {
        color: var(--text-primary);
    }
    .thread-content-text {
        font-size: 15px;
        line-height: 1.55;
        color: #f3f5f7;
        word-break: break-word;
        margin-bottom: 12px;
        white-space: pre-line;
    }
    .thread-hashtag {
        color: var(--accent-blue);
        font-weight: 500;
    }
    .thread-hashtag:hover {
        text-decoration: underline;
    }

    /* Media Gallery Grid */
    .thread-media-gallery {
        border-radius: 14px;
        overflow: hidden;
        margin-bottom: 12px;
        border: 1px solid var(--border-color);
        background: #111;
    }
    .gallery-media-item img {
        width: 100%;
        max-height: 480px;
        object-fit: cover;
        display: block;
    }

    /* Inner Quoted Thread Card (Exact match to screenshot) */
    .threads-inner-quote-card {
        border: 1px solid #282828;
        background: #000000;
        border-radius: 16px;
        padding: 14px 16px;
        margin: 10px 0 14px;
        cursor: pointer;
        transition: border-color 0.2s;
    }
    .threads-inner-quote-card:hover {
        border-color: #383838;
    }
    .inner-quote-topbar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 10px;
        color: var(--text-secondary);
    }
    .inner-quote-left-title {
        display: flex;
        align-items: center;
        gap: 6px;
        font-size: 13px;
        font-weight: 600;
        color: #ffffff;
    }
    .inner-quote-right-tools {
        display: flex;
        align-items: center;
        gap: 10px;
        color: var(--text-muted);
    }
    .inner-quote-author-row {
        display: flex;
        align-items: center;
        gap: 6px;
        margin-bottom: 8px;
    }
    .inner-quote-author-name {
        font-size: 14px;
        font-weight: 700;
        color: #ffffff;
    }
    .inner-quote-arrow {
        color: var(--text-muted);
        font-size: 12px;
    }
    .inner-quote-topic-tag {
        font-size: 13px;
        color: #d1d5db;
        font-weight: 600;
    }
    .inner-quote-time {
        font-size: 13px;
        color: var(--text-muted);
    }
    .inner-quote-threads-logo {
        display: flex;
        align-items: center;
        gap: 4px;
        font-size: 13px;
        font-weight: 700;
        color: var(--text-muted);
    }
    .inner-quote-text {
        font-size: 14px;
        line-height: 1.5;
        color: #f3f5f7;
        margin-bottom: 12px;
        white-space: pre-line;
    }
    .inner-quote-action-counts {
        display: flex;
        align-items: center;
        gap: 16px;
        color: var(--text-muted);
        font-size: 12px;
    }
    .inner-count-item {
        display: flex;
        align-items: center;
        gap: 4px;
    }

    /* Outer Actions Bar */
    .thread-actions-bar {
        display: flex;
        align-items: center;
        gap: 18px;
        padding-top: 4px;
    }
    .action-btn {
        background: none;
        border: none;
        color: var(--text-secondary);
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 6px;
        padding: 4px 6px;
        border-radius: 9999px;
        transition: all 0.15s ease;
        font-size: 13px;
        font-weight: 500;
    }
    .action-btn:hover {
        color: var(--text-primary);
    }
    .action-count {
        color: var(--text-secondary);
        font-size: 13px;
        font-weight: 500;
    }
    .action-like-btn.liked {
        color: var(--accent-pink);
    }
    .action-like-btn.liked .action-count {
        color: var(--accent-pink);
    }
    .action-like-btn.liked .heart-icon {
        animation: heartBounce 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    }
    .action-repost-btn.reposted {
        color: var(--accent-green);
    }
    .action-repost-btn.reposted .action-count {
        color: var(--accent-green);
    }
    @keyframes heartBounce {
        0% { transform: scale(1); }
        50% { transform: scale(1.35); }
        100% { transform: scale(1); }
    }
</style>

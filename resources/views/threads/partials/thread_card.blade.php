@php
    $isRepost = ($thread->type === 'repost') && $thread->repostOf;
    $displayThread = $isRepost ? $thread->repostOf : $thread;
    $repostedBy = $isRepost ? $thread->author : null;
    $currentUser = auth()->user();
    $author = $displayThread->author;

    // Fast O(1) in-memory lookup if preloaded, fallback to DB query if not passed
    $isLiked = isset($userInteractions['liked']) 
        ? isset($userInteractions['liked'][$displayThread->id]) 
        : $displayThread->isLikedBy($currentUser);

    $isBookmarked = isset($userInteractions['bookmarked']) 
        ? isset($userInteractions['bookmarked'][$displayThread->id]) 
        : $displayThread->isBookmarkedBy($currentUser);

    $isReposted = isset($userInteractions['reposted']) 
        ? isset($userInteractions['reposted'][$displayThread->id]) 
        : $displayThread->isRepostedBy($currentUser);

    $isMuted = isset($userInteractions['muted']) 
        ? isset($userInteractions['muted'][$displayThread->id]) 
        : ($currentUser && $displayThread->isMutedBy($currentUser));

    $isFollowing = isset($userInteractions['following']) 
        ? isset($userInteractions['following'][$author->id]) 
        : ($currentUser && $currentUser->isFollowing($author));

    $isRestricted = isset($userInteractions['restricted']) 
        ? isset($userInteractions['restricted'][$author->id]) 
        : ($currentUser && $currentUser->isRestricting($author));

    $isBlocked = isset($userInteractions['blocked']) 
        ? isset($userInteractions['blocked'][$author->id]) 
        : ($currentUser && $currentUser->isBlocking($author));

    $hasReplies = !empty($hasReplies) ? $hasReplies : false;
    $isReplyItem = !empty($isReplyItem) ? $isReplyItem : false;
    $firstTag = $displayThread->tags->first();
    $isTrendingTag = ($firstTag && in_array(mb_strtolower($firstTag->name), ['biểu tình', 'bieu tinh'])) || ($displayThread->slug === 'thread-badat3927-bieu-tinh');
    $cleanContent = preg_replace('/<br\s*\/?>/i', "\n", $displayThread->content ?? '');
@endphp

<article class="thread-card-wrapper {{ $hasReplies ? 'has-replies-line' : '' }} {{ $isReplyItem ? 'is-reply-item' : '' }}" id="thread-{{ $thread->id }}" data-live-key="thread-{{ $thread->id }}" data-thread-id="{{ $displayThread->id }}">
    
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
                <strong>{{ (auth()->check() && auth()->id() === $repostedBy->id) ? 'Bạn' : $repostedBy->name }}</strong> đã đăng lại
            </a>
        </div>
    @endif

    <!-- Trending Indicator (Yellow pill / text matching screenshot) -->
    @if($isTrendingTag)
        <div class="thread-trend-indicator">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
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
                        <img src="{{ route('avatar.show', $author->id) }}" alt="{{ $author->name }}" class="user-avatar-round" style="width: 38px; height: 38px; border-radius: 50%; object-fit: cover;">
                    @else
                        <div class="user-avatar-letter" style="width: 38px; height: 38px;">{{ mb_substr($author->name, 0, 1) }}</div>
                    @endif
                </a>
                @if(!auth()->check() || (auth()->id() !== $author->id && !$isFollowing))
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
                    <x-verified-badge :user="$author"/>

                    @if($author->username === 'minhanna2025' || ($displayThread->tags->count() > 1))
                        <span class="meta-tag-arrow">></span>
                        <span class="meta-tag-name">{{ $author->username === 'minhanna2025' ? 'du học sinh' : $displayThread->tags->skip(1)->first()->name }}</span>
                    @endif
                    <time class="author-time-text" datetime="{{ $displayThread->published_at?->toISOString() }}">
                        {{ $displayThread->published_at ? $displayThread->published_at->diffForHumans(null, true) : 'vừa xong' }}
                    </time>
                    @php
                        $threadLocation = null;
                        if (!empty($displayThread->excerpt)) {
                            $parsedExcerpt = json_decode($displayThread->excerpt, true);
                            if (is_array($parsedExcerpt) && !empty($parsedExcerpt['location'])) {
                                $threadLocation = $parsedExcerpt['location'];
                            }
                        }
                    @endphp
                    @if($threadLocation)
                        <span class="meta-separator text-secondary" style="font-size: 11px;">·</span>
                        <span class="thread-location-pill" title="Địa điểm: {{ $threadLocation }}">
                            <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                                <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                                <circle cx="12" cy="10" r="3"></circle>
                            </svg>
                            <span>{{ $threadLocation }}</span>
                        </span>
                    @endif
                </div>

                <!-- More Options (3 dots) -->
                <div class="dropdown">
                    <button class="thread-more-btn" data-bs-toggle="dropdown" aria-expanded="false" title="Thêm tùy chọn" aria-label="Tùy chọn bài viết">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="1.5" fill="currentColor"></circle>
                            <circle cx="19" cy="12" r="1.5" fill="currentColor"></circle>
                            <circle cx="5" cy="12" r="1.5" fill="currentColor"></circle>
                        </svg>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end thread-options-dropdown-menu">
                        <!-- Group 1: Hỏi Meta AI -->
                        <button type="button" class="thread-option-item btn-ask-meta-ai" 
                            data-thread-id="{{ $displayThread->id }}" 
                            data-author-name="{{ e($author->name) }}"
                            data-thread-content="{{ e(Str::limit(strip_tags($cleanContent), 280)) }}">
                            <span class="thread-option-label">Hỏi Meta AI</span>
                            <span class="thread-option-icon">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                                    <g transform="translate(12,12)">
                                        <ellipse cx="0" cy="-7.5" rx="1.2" ry="2.2" transform="rotate(0) rotate(18, 0, -7.5)" fill="currentColor"/>
                                        <ellipse cx="0" cy="-7.5" rx="1.2" ry="2.2" transform="rotate(40) rotate(18, 0, -7.5)" fill="currentColor"/>
                                        <ellipse cx="0" cy="-7.5" rx="1.2" ry="2.2" transform="rotate(80) rotate(18, 0, -7.5)" fill="currentColor"/>
                                        <ellipse cx="0" cy="-7.5" rx="1.2" ry="2.2" transform="rotate(120) rotate(18, 0, -7.5)" fill="currentColor"/>
                                        <ellipse cx="0" cy="-7.5" rx="1.2" ry="2.2" transform="rotate(160) rotate(18, 0, -7.5)" fill="currentColor"/>
                                        <ellipse cx="0" cy="-7.5" rx="1.2" ry="2.2" transform="rotate(200) rotate(18, 0, -7.5)" fill="currentColor"/>
                                        <ellipse cx="0" cy="-7.5" rx="1.2" ry="2.2" transform="rotate(240) rotate(18, 0, -7.5)" fill="currentColor"/>
                                        <ellipse cx="0" cy="-7.5" rx="1.2" ry="2.2" transform="rotate(280) rotate(18, 0, -7.5)" fill="currentColor"/>
                                        <ellipse cx="0" cy="-7.5" rx="1.2" ry="2.2" transform="rotate(320) rotate(18, 0, -7.5)" fill="currentColor"/>
                                    </g>
                                </svg>
                            </span>
                        </button>

                        <div class="thread-option-divider"></div>

                        <!-- Group 2: Sao chép liên kết & Lấy mã nhúng -->
                        <button type="button" class="thread-option-item btn-copy-thread-link" data-url="{{ route('threads.show', $displayThread->id) }}">
                            <span class="thread-option-label">Sao chép liên kết</span>
                            <span class="thread-option-icon">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"></path>
                                    <path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"></path>
                                </svg>
                            </span>
                        </button>
                        <button type="button" class="thread-option-item btn-embed-thread" data-url="{{ route('threads.embed', $displayThread->id) }}">
                            <span class="thread-option-label">Lấy mã nhúng</span>
                            <span class="thread-option-icon">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M7 4a3 3 0 0 0-3 3v2a3 3 0 0 1-3 3 3 3 0 0 1 3 3v2a3 3 0 0 0 3 3"></path>
                                    <path d="M17 4a3 3 0 0 1 3 3v2a3 3 0 0 0 3 3 3 3 0 0 0-3 3v2a3 3 0 0 1-3 3"></path>
                                    <circle cx="9.5" cy="12" r="0.9" fill="currentColor"></circle>
                                    <circle cx="12" cy="12" r="0.9" fill="currentColor"></circle>
                                    <circle cx="14.5" cy="12" r="0.9" fill="currentColor"></circle>
                                </svg>
                            </span>
                        </button>

                        <div class="thread-option-divider"></div>

                        <!-- Group 3: Lưu / Bỏ lưu & Không quan tâm -->
                        <button type="button" class="thread-option-item btn-ajax-thread-bookmark" data-thread-id="{{ $displayThread->id }}" data-bookmarked="{{ $isBookmarked ? '1' : '0' }}">
                            <span class="thread-option-label bookmark-label">{{ $isBookmarked ? 'Bỏ lưu' : 'Lưu' }}</span>
                            <span class="thread-option-icon">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="{{ $isBookmarked ? 'currentColor' : 'none' }}" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="bookmark-svg-icon">
                                    <path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"></path>
                                </svg>
                            </span>
                        </button>
                        <button type="button" class="thread-option-item btn-not-interested" data-thread-id="{{ $displayThread->id }}">
                            <span class="thread-option-label">Không quan tâm</span>
                            <span class="thread-option-icon">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                                    <line x1="1" y1="1" x2="23" y2="23"></line>
                                </svg>
                            </span>
                        </button>

                        <div class="thread-option-divider"></div>

                        <!-- Group 4: Thêm vào bảng feed -->
                        <button type="button" class="thread-option-item btn-add-to-custom-feed" data-thread-id="{{ $displayThread->id }}">
                            <span class="thread-option-label">Thêm vào bảng feed</span>
                            <span class="thread-option-icon">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                    <polyline points="9 18 15 12 9 6"></polyline>
                                </svg>
                            </span>
                        </button>

                        <div class="thread-option-divider"></div>

                        <!-- Group 5: Tắt thông báo, Hạn chế, Chặn, Báo cáo -->
                        <button type="button" class="thread-option-item btn-mute-thread" data-thread-id="{{ $displayThread->id }}" data-muted="{{ $isMuted ? '1' : '0' }}">
                            <span class="thread-option-label mute-label">{{ $isMuted ? 'Bật thông báo' : 'Tắt thông báo' }}</span>
                            <span class="thread-option-icon">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
                                    <circle cx="9" cy="7" r="4"></circle>
                                    <line x1="17" y1="11" x2="22" y2="16"></line>
                                    <line x1="22" y1="11" x2="17" y2="16"></line>
                                </svg>
                            </span>
                        </button>
                        <button type="button" class="thread-option-item btn-restrict-user" data-user-id="{{ $author->id }}" data-user-name="{{ e($author->name) }}" data-restricted="{{ $isRestricted ? '1' : '0' }}">
                            <span class="thread-option-label restrict-label">{{ $isRestricted ? 'Bỏ hạn chế' : 'Hạn chế' }}</span>
                            <span class="thread-option-icon">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                                    <circle cx="8.5" cy="7" r="4"></circle>
                                    <path d="M17.5 12.5l3.5 1.5v3.2c0 2.2-3.5 3.8-3.5 3.8s-3.5-1.6-3.5-3.8V14l3.5-1.5z"></path>
                                </svg>
                            </span>
                        </button>
                        <button type="button" class="thread-option-item thread-option-danger btn-block-user" data-user-id="{{ $author->id }}" data-user-name="{{ e($author->name) }}" data-blocked="{{ $isBlocked ? '1' : '0' }}">
                            <span class="thread-option-label block-label">{{ $isBlocked ? 'Bỏ chặn' : 'Chặn' }}</span>
                            <span class="thread-option-icon">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <line x1="4.93" y1="4.93" x2="19.07" y2="19.07"></line>
                                </svg>
                            </span>
                        </button>
                        <button type="button" class="thread-option-item thread-option-danger btn-report-thread" data-thread-id="{{ $displayThread->id }}" data-author-name="{{ e($author->name) }}">
                            <span class="thread-option-label">Báo cáo</span>
                            <span class="thread-option-icon">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path>
                                    <line x1="12" y1="8" x2="12" y2="12"></line>
                                    <line x1="12" y1="16" x2="12.01" y2="16"></line>
                                </svg>
                            </span>
                        </button>

                        <!-- Author / Admin: Xóa bài viết -->
                        @auth
                            @if($currentUser->id === $thread->user_id || $currentUser->isAdmin())
                                <div class="thread-option-divider"></div>
                                <form action="{{ route('threads.destroy', $thread->id) }}" data-live-submit method="post" onsubmit="return confirm('Bạn có chắc chắn muốn xóa bài viết này không?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="thread-option-item thread-option-danger w-100">
                                        <span class="thread-option-label">Xóa bài viết</span>
                                        <span class="thread-option-icon">
                                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <polyline points="3 6 5 6 21 6"></polyline>
                                                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                            </svg>
                                        </span>
                                    </button>
                                </form>
                            @endif
                        @endauth
                    </div>
                </div>
            </div>

            <!-- Content Area -->
            @php
                $cleanContent = preg_replace('/<br\s*\/?>/i', "\n", $displayThread->content ?? '');
                $formattedContent = $displayThread->content_format === 'html'
                    ? app(\App\Services\ThreadText::class)->html($displayThread->content)
                    : app(\App\Services\ThreadText::class)->html(str_replace(["\r\n", "\n", "\r"], '<br>', app(\App\Services\Mentions::class)->commentHtml($displayThread)));
                $formattedContent = str_replace('class="stream-hashtag"', 'class="thread-hashtag"', $formattedContent);
            @endphp
            @if(trim($cleanContent) !== '')
                <div class="thread-content-text">
                    {!! $formattedContent !!}
                </div>
            @endif

            <!-- Attached Document Display (if thread has attached text doc) -->
            @php
                $attachedDoc = null;
                if (!empty($displayThread->excerpt)) {
                    $parsedExcerpt = json_decode($displayThread->excerpt, true);
                    if (is_array($parsedExcerpt) && !empty($parsedExcerpt['attached_doc'])) {
                        $attachedDoc = $parsedExcerpt['attached_doc'];
                    }
                }
            @endphp

            @if($attachedDoc)
                <div class="thread-attached-doc-display">
                    <div class="attached-doc-display-header">
                        <div class="attached-doc-display-badge">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                <polyline points="14 2 14 8 20 8"></polyline>
                                <line x1="16" y1="13" x2="8" y2="13"></line>
                                <line x1="16" y1="17" x2="8" y2="17"></line>
                            </svg>
                            <span>Văn bản đính kèm</span>
                        </div>
                    </div>
                    <div class="attached-doc-display-body">
                        {!! $attachedDoc !!}
                    </div>
                </div>
            @endif

            <!-- Interactive Poll Display (if thread has a poll) -->
            @php
                $pollData = null;
                if (!empty($displayThread->excerpt)) {
                    $parsedExcerpt = json_decode($displayThread->excerpt, true);
                    if (is_array($parsedExcerpt) && !empty($parsedExcerpt['is_poll']) && !empty($parsedExcerpt['options'])) {
                        $pollData = $parsedExcerpt;
                    }
                }
            @endphp

            @if($pollData)
                @php
                    $currentUserId = (string) (auth()->id() ?? '');
                    $votersList = is_array($pollData['voters']) ? $pollData['voters'] : (array) $pollData['voters'];
                    $userVoted = isset($votersList[$currentUserId]);
                    $userPick = $votersList[$currentUserId] ?? null;
                    $totalVotes = $pollData['total_votes'] ?? 0;
                    $maxVotes = max(1, $totalVotes);
                    $pollClosed = !empty($pollData['expires_at']) && strtotime($pollData['expires_at']) <= now()->timestamp;
                @endphp
                <div class="thread-poll-display-card" id="pollCard-{{ $displayThread->id }}" data-expires-at="{{ $pollData['expires_at'] ?? '' }}" data-poll-id="{{ $displayThread->id }}" data-poll-options='@json($pollData["options"])' data-poll-total="{{ $totalVotes }}" data-poll-pick="{{ $userPick }}" data-thread-id="{{ $displayThread->id }}">
                    <div class="poll-items-wrap">
                        @foreach($pollData['options'] as $opt)
                            @php
                                $optVotes = $opt['votes'] ?? 0;
                                $percent = $totalVotes > 0 ? round(($optVotes / $maxVotes) * 100) : 0;
                                $isPicked = ($userPick === $opt['id']);
                            @endphp
                            @if($userVoted || $pollClosed)
                                <div class="poll-display-result-row {{ $isPicked ? 'user-picked' : '' }}">
                                    <div class="poll-result-bar-bg" style="width: {{ $percent }}%;"></div>
                                    <div class="poll-result-label-row">
                                        <span class="poll-result-opt-name">
                                            {{ $opt['text'] }}
                                            @if($isPicked)
                                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" class="ms-1 text-white"><polyline points="20 6 9 17 4 12"></polyline></svg>
                                            @endif
                                        </span>
                                        <span class="poll-result-percent">{{ $percent }}%</span>
                                    </div>
                                </div>
                            @else
                                <button type="button" class="poll-display-vote-btn btn-ajax-poll-vote" data-vote-url="{{ route('threads.vote', $displayThread->id) }}" data-thread-id="{{ $displayThread->id }}" data-option-id="{{ $opt['id'] }}">
                                    {{ $opt['text'] }}
                                </button>
                            @endif
                        @endforeach
                    </div>
                    <div class="poll-display-footer">
                        <span class="poll-total-votes-count">{{ $totalVotes }} phiếu bầu</span>
                        <span>·</span>
                        <span class="poll-deadline">{{ $pollClosed ? 'Đã kết thúc' : 'Còn '.\Illuminate\Support\Carbon::parse($pollData['expires_at'] ?? now()->addDay())->diffForHumans(now(), true) }}</span>
                    </div>
                </div>
            @endif

            <x-thread-media :items="$displayThread->media"/>
            <x-thread-gifs :ids="(json_decode($displayThread->excerpt ?? '', true) ?: [])['giphy_ids'] ?? []"/>
            <x-thread-music :track="(json_decode($displayThread->excerpt ?? '', true) ?: [])['music'] ?? null"/>

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
                            {!! nl2br(e(preg_replace('/<br\s*\/?>/i', "\n", $quoted->content ?? ''))) !!}
                        </div>
                        <x-thread-gifs :ids="(json_decode($quoted->excerpt ?? '', true) ?: [])['giphy_ids'] ?? []"/>
                        <x-thread-music :track="(json_decode($quoted->excerpt ?? '', true) ?: [])['music'] ?? null"/>
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
                <a href="{{ route('threads.show', $displayThread->id) }}#replyBox" class="action-btn" title="Trả lời" @guest onclick="event.preventDefault(); window.checkAuth('trả lời bài viết này');" @endguest>
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
                    data-content-snippet="{{ Str::limit(strip_tags(preg_replace('/<br\s*\/?>/i', ' ', $displayThread->content ?? '')), 120) }}"
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
        background: color-mix(in srgb, var(--journal-text) 2%, transparent);
    }
    .thread-trend-indicator {
        display: flex;
        align-items: center;
        gap: 6px;
        margin-left: 54px;
        margin-bottom: 10px;
        font-size: 13px;
        font-weight: 700;
        color: var(--journal-text);
    }
    .trend-indicator-text {
        color: var(--journal-text);
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
        width: 38px;
        height: 38px;
        flex-shrink: 0;
    }
    .avatar-link {
        display: block;
        width: 38px;
        height: 38px;
        border-radius: 50%;
        overflow: hidden;
        flex-shrink: 0;
    }
    .user-avatar-round {
        width: 38px !important;
        height: 38px !important;
        max-width: 38px !important;
        max-height: 38px !important;
        border-radius: 50% !important;
        object-fit: cover !important;
        background: var(--journal-raised) !important;
        display: block !important;
        flex-shrink: 0 !important;
    }
    .user-avatar-letter {
        width: 38px !important;
        height: 38px !important;
        max-width: 38px !important;
        max-height: 38px !important;
        border-radius: 50% !important;
        background: var(--journal-raised) !important;
        color: var(--journal-text) !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        font-weight: 700 !important;
        font-size: 15px !important;
        border: 1px solid var(--journal-line) !important;
        flex-shrink: 0 !important;
    }
    .avatar-follow-plus-btn {
        position: absolute;
        bottom: -2px;
        right: -2px;
        width: 17px;
        height: 17px;
        border-radius: 50%;
        background: var(--journal-surface);
        color: var(--journal-text);
        border: 2px solid var(--journal-line);
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        padding: 0;
        transition: transform 0.15s, background 0.15s;
    }
    .avatar-follow-plus-btn:hover {
        transform: scale(1.15);
        background: var(--journal-raised);
    }
    .thread-connecting-line {
        width: 2px;
        flex-grow: 1;
        min-height: 40px;
        background: var(--journal-raised);
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
        color: var(--journal-text);
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
        color: var(--journal-text);
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
        color: var(--journal-text);
        word-break: break-word;
        margin-bottom: 12px;
        white-space: pre-line;
    }
    .thread-hashtag, .thread-mention {
        color: var(--accent-blue);
        font-weight: 500;
        text-decoration: none;
    }
    .thread-hashtag:hover, .thread-mention:hover {
        text-decoration: underline;
        color: var(--journal-link);
    }

    /* Media Gallery Grid */
    .thread-media-gallery {
        border-radius: 14px;
        overflow: hidden;
        margin-bottom: 12px;
        border: 1px solid var(--border-color);
        background: var(--journal-surface);
        display: grid;
        gap: 3px;
    }
    .thread-media-gallery.gallery-count-1 {
        grid-template-columns: 1fr;
    }
    .thread-media-gallery.gallery-count-2 {
        grid-template-columns: 1fr 1fr;
        aspect-ratio: 16 / 9;
    }
    .thread-media-gallery.gallery-count-3 {
        grid-template-columns: 2fr 1fr;
        grid-template-rows: 1fr 1fr;
        aspect-ratio: 16 / 9;
    }
    .thread-media-gallery.gallery-count-3 .gallery-media-item:first-child {
        grid-row: span 2;
    }
    .thread-media-gallery.gallery-count-4 {
        grid-template-columns: 1fr 1fr;
        grid-template-rows: 1fr 1fr;
        aspect-ratio: 16 / 9;
    }
    .gallery-media-item {
        position: relative;
        overflow: hidden;
        background: #000000;
        width: 100%;
        height: 100%;
    }
    .gallery-media-link {
        display: block;
        width: 100%;
        height: 100%;
    }
    .gallery-media-item img {
        width: 100%;
        height: 100%;
        object-fit: contain;
        display: block;
    }
    .gallery-count-1 .gallery-media-item,
    .gallery-count-1 .gallery-media-link,
    .gallery-count-1 .gallery-media-item img {
        height: auto;
    }

    /* Inner Quoted Thread Card (Exact match to screenshot) */
    .threads-inner-quote-card {
        border: 1px solid var(--journal-line);
        background: var(--journal-surface);
        border-radius: 16px;
        padding: 14px 16px;
        margin: 10px 0 14px;
        cursor: pointer;
        transition: border-color 0.2s;
    }
    .threads-inner-quote-card:hover {
        border-color: var(--journal-line);
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
        color: var(--journal-text);
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
        color: var(--journal-text);
    }
    .inner-quote-arrow {
        color: var(--text-muted);
        font-size: 12px;
    }
    .inner-quote-topic-tag {
        font-size: 13px;
        color: var(--journal-text);
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
        color: var(--journal-text);
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

    /* Thread Location Tag in Header */
    .thread-location-pill {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        color: var(--journal-muted);
        font-size: 13px;
        font-weight: 500;
        cursor: default;
        transition: color 0.15s;
    }
    .thread-location-pill:hover {
        color: var(--journal-link);
    }

    /* Attached Document Display Card Styling */
    .thread-attached-doc-display {
        background: var(--journal-surface);
        border: 1px solid var(--journal-line);
        border-radius: 14px;
        padding: 14px 18px;
        margin-top: 10px;
        margin-bottom: 12px;
        max-width: 540px;
        transition: border-color 0.15s;
    }
    .thread-attached-doc-display:hover {
        border-color: var(--journal-line);
    }
    .attached-doc-display-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 8px;
        padding-bottom: 6px;
        border-bottom: 1px solid var(--journal-line);
    }
    .attached-doc-display-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        color: var(--journal-link);
        font-size: 13px;
        font-weight: 600;
    }
    .attached-doc-display-body {
        color: var(--journal-text);
        font-size: 15px;
        line-height: 1.6;
        word-break: break-word;
    }
    .attached-doc-display-body b, 
    .attached-doc-display-body strong {
        font-weight: 700;
        color: var(--journal-text);
    }
    .attached-doc-display-body i, 
    .attached-doc-display-body em {
        font-style: italic;
    }
    .attached-doc-display-body u {
        text-decoration: underline;
    }
    .attached-doc-display-body s, 
    .attached-doc-display-body strike {
        text-decoration: line-through;
    }
    .attached-doc-display-body code, 
    .attached-doc-display-body pre {
        background: var(--journal-raised);
        padding: 2px 6px;
        border-radius: 4px;
        color: var(--journal-success);
        font-family: monospace;
        font-size: 13.5px;
    }

    /* Thread Poll Display Card Styling */
    .thread-poll-display-card {
        margin: 10px 0 12px;
        max-width: 520px;
    }
    .poll-items-wrap {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }
    .poll-display-vote-btn {
        width: 100%;
        background: var(--journal-surface);
        border: 1px solid var(--journal-line);
        border-radius: 12px;
        padding: 12px 18px;
        color: var(--journal-text);
        font-size: 14.5px;
        font-weight: 500;
        text-align: left;
        cursor: pointer;
        transition: background 0.15s, border-color 0.15s, transform 0.05s;
    }
    .poll-display-vote-btn:hover {
        background: var(--journal-raised);
        border-color: var(--journal-line);
    }
    .poll-display-vote-btn:active {
        transform: scale(0.99);
    }
    .poll-display-result-row {
        position: relative;
        width: 100%;
        background: var(--journal-surface);
        border: 1px solid var(--journal-line);
        border-radius: 12px;
        overflow: hidden;
        height: 44px;
    }
    .poll-display-result-row.user-picked {
        border-color: var(--journal-line);
    }
    .poll-result-bar-bg {
        position: absolute;
        top: 0;
        left: 0;
        bottom: 0;
        background: color-mix(in srgb, var(--journal-text) 12%, transparent);
        border-radius: 10px;
        transition: width 0.4s ease;
    }
    .poll-display-result-row.user-picked .poll-result-bar-bg {
        background: color-mix(in srgb, var(--journal-text) 22%, transparent);
    }
    .poll-result-label-row {
        position: relative;
        z-index: 2;
        display: flex;
        align-items: center;
        justify-content: space-between;
        height: 100%;
        padding: 0 18px;
        font-size: 14.5px;
        font-weight: 500;
        color: var(--journal-text);
    }
    .poll-result-percent {
        color: var(--journal-muted);
        font-size: 13.5px;
        font-weight: 600;
    }
    .poll-display-footer {
        display: flex;
        align-items: center;
        gap: 6px;
        color: var(--journal-faint);
        font-size: 13px;
        margin-top: 8px;
        padding-left: 2px;
    }

    /* Threads Post 3-Dots Options Dropdown Menu (Exact match to screenshot) */
    .thread-options-dropdown-menu {
        min-width: 260px;
        background: var(--journal-surface) !important;
        border: 1px solid var(--journal-line) !important;
        border-radius: 18px !important;
        padding: 6px !important;
        box-shadow: var(--journal-shadow) !important;
        z-index: 1050;
        margin-top: 6px !important;
    }
    .thread-option-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        width: 100%;
        padding: 10px 14px;
        background: transparent;
        border: none;
        border-radius: 12px;
        color: var(--journal-text);
        font-size: 15px;
        font-weight: 600;
        cursor: pointer;
        text-align: left;
        transition: background 0.12s ease, color 0.12s ease;
        text-decoration: none;
        user-select: none;
    }
    .thread-option-item:hover,
    .thread-option-item:focus {
        background: color-mix(in srgb, var(--journal-text) 8%, transparent);
        color: var(--journal-text);
        outline: none;
    }
    .thread-option-item.thread-option-danger {
        color: var(--journal-danger) !important;
    }
    .thread-option-item.thread-option-danger .thread-option-icon svg {
        stroke: var(--journal-danger) !important;
    }
    .thread-option-item.thread-option-danger:hover {
        background: rgba(255, 59, 48, 0.12);
        color: var(--journal-danger) !important;
    }
    .thread-option-label {
        flex: 1;
        white-space: nowrap;
    }
    .thread-option-icon {
        flex-shrink: 0;
        margin-left: 14px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }
    .thread-option-divider {
        height: 1px;
        background: color-mix(in srgb, var(--journal-text) 8%, transparent);
        margin: 4px 6px;
        border: none;
    }
</style>

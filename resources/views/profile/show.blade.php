@extends('layouts.threads')

@section('content')

    <!-- Profile Header Section -->
    <div class="profile-header-card p-4 rounded-4 mb-4" style="background: var(--journal-surface); border: 1px solid var(--border-color);">
        <div class="d-flex justify-content-between align-items-start gap-3">
            <div>
                <h3 class="text-white fw-bold mb-1 fs-4">{{ $user->name }}<x-verified-badge :user="$user"/></h3>
                <div class="text-secondary small mb-3">{{ $user->handle() }}</div>
                @if($user->bio)
                    <p class="text-white fs-6 mb-3" style="line-height: 1.5;">{{ $user->bio }}</p>
                @endif
                <div class="d-flex gap-3 text-secondary small align-items-center">
                    <button type="button" class="btn-stat-link" id="btnOpenFollowersModal" data-initial-tab="followers">
                        <strong class="text-white" id="profileFollowersCount" data-live-region="followers-count">{{ $followersCount }}</strong> người theo dõi
                    </button>
                    <span>·</span>
                    <button type="button" class="btn-stat-link" id="btnOpenFollowingModal" data-initial-tab="following">
                        <strong class="text-white" id="profileFollowingCount">{{ $followingCount }}</strong> đang theo dõi
                    </button>
                    <span>·</span>
                    <span><strong class="text-white">{{ $threadsCount }}</strong> threads</span>
                </div>
            </div>
            <div>
                @if($user->avatar_path)
                    <img src="{{ route('avatar.show', $user->id) }}" alt="{{ $user->name }}" style="width: 80px; height: 80px; border-radius: 50%; object-fit: cover; border: 2px solid var(--journal-line);">
                @else
                    <div style="width: 80px; height: 80px; border-radius: 50%; background: var(--journal-raised); color: var(--journal-text); display: flex; align-items: center; justify-content: center; font-size: 32px; font-weight: bold; border: 2px solid var(--journal-line);">
                        {{ mb_substr($user->name, 0, 1) }}
                    </div>
                @endif
            </div>
        </div>

        <!-- Action Button: Follow/Unfollow or Edit Profile -->
        <div class="mt-4 pt-3 border-top border-dark d-flex gap-2">
            @auth
                @if(auth()->id() === $user->id)
                    <a href="{{ route('account.edit') }}" class="btn-threads-outline w-100 text-center py-2">
                        Chỉnh sửa trang cá nhân
                    </a>
                @else
                    @php $isFollowing = auth()->user()->isFollowing($user); @endphp
                    <button type="button" class="w-100 py-2 {{ $isFollowing ? 'btn-threads-outline' : 'btn-threads-primary' }}" id="btnFollowUser" data-user-id="{{ $user->id }}">
                        <span id="followBtnText">{{ auth()->user()->hasRequestedToFollow($user) ? 'Đã gửi yêu cầu' : ($isFollowing ? 'Đang theo dõi' : ($user->is_private ? 'Yêu cầu theo dõi' : 'Theo dõi')) }}</span>
                    </button>
                @endif
            @else
                <button type="button" class="btn-threads-primary w-100 text-center py-2" onclick="window.checkAuth('theo dõi {{ e($user->name) }}')">
                    Theo dõi
                </button>
            @endauth
        </div>
    </div>

    @if(! $user->canViewPosts(auth()->user()))<p class="p-4 text-secondary">Tài khoản này ở chế độ riêng tư. {{ auth()->user()?->hasRequestedToFollow($user) ? 'Yêu cầu của bạn đang chờ chủ tài khoản chấp nhận.' : 'Gửi yêu cầu theo dõi và chờ được chấp nhận để xem bài viết.' }}</p>@endif
    <!-- Profile Tabs: Threads, Trả lời, Đăng lại, Đã thích -->
    <div class="profile-tabs-header d-flex border-bottom border-dark mb-3">
        <a href="{{ route('profile.show', ['username' => $user->username ?? $user->id, 'tab' => 'threads']) }}" class="flex-grow-1 text-center py-3 fw-semibold {{ $tab === 'threads' ? 'text-white border-bottom border-2 border-white' : 'text-secondary' }}">
            Threads
        </a>
        <a href="{{ route('profile.show', ['username' => $user->username ?? $user->id, 'tab' => 'replies']) }}" class="flex-grow-1 text-center py-3 fw-semibold {{ $tab === 'replies' ? 'text-white border-bottom border-2 border-white' : 'text-secondary' }}">
            Trả lời
        </a>
        <a href="{{ route('profile.show', ['username' => $user->username ?? $user->id, 'tab' => 'reposts']) }}" class="flex-grow-1 text-center py-3 fw-semibold {{ $tab === 'reposts' ? 'text-white border-bottom border-2 border-white' : 'text-secondary' }}">
            Đã đăng lại
        </a>
        @if(auth()->check() && (auth()->id() === $user->id || auth()->user()->isAdmin()))
            <a href="{{ route('profile.show', ['username' => $user->username ?? $user->id, 'tab' => 'likes']) }}" class="flex-grow-1 text-center py-3 fw-semibold {{ $tab === 'likes' ? 'text-white border-bottom border-2 border-white' : 'text-secondary' }}">
                Đã thích
            </a>
        @endif
    </div>

    <!-- User's Threads Feed -->
    <div id="profileThreadsContainer" data-live-region="profile" data-live-list>
        @include('threads.partials.thread_list', ['threads' => $threads, 'userInteractions' => $userInteractions ?? []])
    </div>

    <!-- Pagination -->
    <div class="mt-4">
        {{ $threads->links() }}
    </div>

    <!-- Followers & Following Modal -->
    <div class="modal fade" id="followersModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" style="max-width: 520px;">
            <div class="modal-content" style="background: var(--journal-surface); border: 1px solid var(--journal-line); border-radius: 20px; color: var(--journal-text); box-shadow: var(--journal-shadow); overflow: hidden;">
                <div class="modal-header border-bottom border-dark px-4 py-3">
                    <div class="d-flex w-100 gap-2">
                        <button type="button" class="followers-modal-tab flex-grow-1 active" id="tabFollowersBtn" data-tab="followers">
                            Người theo dõi (<span id="modalFollowersCount" data-live-region="modal-followers-count">{{ $followersCount }}</span>)
                        </button>
                        <button type="button" class="followers-modal-tab flex-grow-1" id="tabFollowingBtn" data-tab="following">
                            Đang theo dõi (<span id="modalFollowingCount" data-live-region="modal-following-count">{{ $followingCount }}</span>)
                        </button>
                    </div>
                    <button type="button" class="btn-close btn-close-white ms-2" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-0" style="max-height: 460px; overflow-y: auto;">
                    <div id="followersModalLoading" class="text-center py-5 text-secondary">
                        <div class="spinner-border spinner-border-sm text-light mb-2" role="status"></div>
                        <div>Đang tải danh sách...</div>
                    </div>
                    <div id="followersModalList" class="p-2" style="display: none;"></div>
                </div>
            </div>
        </div>
    </div>

<style>
    .btn-stat-link {
        background: none;
        border: none;
        padding: 0;
        color: var(--text-secondary);
        font-size: 13px;
        cursor: pointer;
        transition: color 0.15s ease;
    }
    .btn-stat-link:hover {
        color: var(--journal-text);
        text-decoration: underline;
    }
    .followers-modal-tab {
        background: transparent;
        border: none;
        border-bottom: 2px solid transparent;
        color: var(--journal-faint);
        font-size: 14px;
        font-weight: 600;
        padding: 10px 0;
        cursor: pointer;
        transition: all 0.15s ease;
    }
    .followers-modal-tab:hover {
        color: var(--journal-text);
    }
    .followers-modal-tab.active {
        color: var(--journal-text);
        border-bottom-color: var(--journal-text);
    }
    .user-list-item-card {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        padding: 10px 14px;
        border-radius: 12px;
        transition: background 0.15s ease;
    }
    .user-list-item-card:hover {
        background: color-mix(in srgb, var(--journal-text) 4%, transparent);
    }
    .user-list-item-link {
        display: flex;
        align-items: center;
        gap: 12px;
        text-decoration: none;
        color: inherit;
        flex: 1;
        min-width: 0;
    }
    .btn-user-modal-follow {
        padding: 5px 14px;
        font-size: 12px;
        font-weight: 600;
        border-radius: 10px;
        border: 1px solid var(--journal-line);
        background: var(--journal-button-bg);
        color: var(--journal-button-text);
        cursor: pointer;
        transition: all 0.15s ease;
        flex-shrink: 0;
    }
    .btn-user-modal-follow:hover {
        opacity: 0.9;
    }
    .btn-user-modal-follow.following {
        background: transparent;
        color: var(--journal-faint);
        border-color: var(--journal-line);
    }
</style>
@endsection

@push('threads-scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    const profileUsername = @json($user->username ?: $user->id);
    const followBtn = document.getElementById('btnFollowUser');

    if (followBtn) {
        followBtn.addEventListener('click', function() {
            const userId = this.getAttribute('data-user-id');
            const textSpan = document.getElementById('followBtnText');
            const countSpan = document.getElementById('profileFollowersCount');

            fetch(`/users/${userId}/follow`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                }
            })
            .then(res => res.json())
            .then(data => {
                if (data && data.success) {
                    if (data.following) {
                        textSpan.textContent = 'Đang theo dõi';
                        followBtn.classList.remove('btn-threads-primary');
                        followBtn.classList.add('btn-threads-outline');
                    } else {
                        textSpan.textContent = 'Theo dõi';
                        followBtn.classList.remove('btn-threads-outline');
                        followBtn.classList.add('btn-threads-primary');
                    }
                    if (countSpan) {
                        countSpan.textContent = data.followers_count;
                    }
                    const modalCount = document.getElementById('modalFollowersCount');
                    if (modalCount) {
                        modalCount.textContent = data.followers_count;
                    }
                }
            })
            .catch(err => console.error(err));
        });
    }

    // Followers & Following Modal logic
    const followersModalEl = document.getElementById('followersModal');
    let followersModalInstance = null;
    if (followersModalEl && typeof bootstrap !== 'undefined') {
        followersModalInstance = new bootstrap.Modal(followersModalEl);
    }

    const btnOpenFollowers = document.getElementById('btnOpenFollowersModal');
    const btnOpenFollowing = document.getElementById('btnOpenFollowingModal');
    const tabFollowersBtn = document.getElementById('tabFollowersBtn');
    const tabFollowingBtn = document.getElementById('tabFollowingBtn');
    const modalLoading = document.getElementById('followersModalLoading');
    const modalList = document.getElementById('followersModalList');

    let currentModalTab = 'followers';

    function loadUserList(tab) {
        currentModalTab = tab;
        if (tab === 'followers') {
            tabFollowersBtn.classList.add('active');
            tabFollowingBtn.classList.remove('active');
        } else {
            tabFollowingBtn.classList.add('active');
            tabFollowersBtn.classList.remove('active');
        }

        modalLoading.style.display = 'block';
        modalList.style.display = 'none';
        modalList.innerHTML = '';

        const url = `/users/${profileUsername}/${tab}`;
        fetch(url, { headers: { 'Accept': 'application/json' } })
            .then(res => res.json())
            .then(data => {
                modalLoading.style.display = 'none';
                modalList.style.display = 'block';

                if (tab === 'followers' && data.count !== undefined) {
                    document.getElementById('modalFollowersCount').textContent = data.count;
                } else if (tab === 'following' && data.count !== undefined) {
                    document.getElementById('modalFollowingCount').textContent = data.count;
                }

                if (!data.users || data.users.length === 0) {
                    modalList.innerHTML = `
                        <div class="text-center py-5 text-secondary">
                            <div class="mb-2" style="font-size: 32px;">👥</div>
                            <div class="text-white fw-bold">${tab === 'followers' ? 'Chưa có người theo dõi nào' : 'Chưa theo dõi ai'}</div>
                            <div class="small mt-1">${tab === 'followers' ? 'Khi có người theo dõi, danh sách sẽ hiện ở đây.' : 'Các tài khoản đang theo dõi sẽ xuất hiện ở đây.'}</div>
                        </div>
                    `;
                    return;
                }

                modalList.innerHTML = data.users.map(u => {
                    const avatarHtml = u.avatar_url
                        ? `<img src="${u.avatar_url}" class="user-avatar-round" style="width: 40px; height: 40px;" alt="${u.name}">`
                        : `<div class="user-avatar-letter" style="width: 40px; height: 40px; font-size: 14px;">${(u.name || 'U').charAt(0).toUpperCase()}</div>`;

                    let buttonHtml = '';
                    if (!u.is_me) {
                        buttonHtml = `
                            <button type="button"
                                class="btn-user-modal-follow ${u.is_following ? 'following' : ''}"
                                data-user-id="${u.id}">
                                ${u.is_following ? 'Đang theo dõi' : 'Theo dõi'}
                            </button>
                        `;
                    }

                    return `
                        <div class="user-list-item-card">
                            <a href="${u.profile_url}" class="user-list-item-link">
                                ${avatarHtml}
                                <div class="overflow-hidden">
                                    <div class="text-white fw-bold text-truncate" style="font-size: 14px;">${u.name}</div>
                                    <div class="text-secondary text-truncate" style="font-size: 13px;">${u.handle}</div>
                                    ${u.bio ? `<div class="text-secondary small text-truncate mt-1" style="max-width: 280px;">${u.bio}</div>` : ''}
                                </div>
                            </a>
                            ${buttonHtml}
                        </div>
                    `;
                }).join('');

                // Attach follow handler to buttons inside modal
                modalList.querySelectorAll('.btn-user-modal-follow').forEach(btn => {
                    btn.addEventListener('click', function(e) {
                        e.preventDefault();
                        e.stopPropagation();
                        const targetUserId = this.getAttribute('data-user-id');
                        fetch(`/users/${targetUserId}/follow`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrfToken,
                                'Accept': 'application/json'
                            }
                        })
                        .then(r => r.json())
                        .then(resp => {
                            if (resp && resp.success) {
                                if (resp.following) {
                                    this.textContent = 'Đang theo dõi';
                                    this.classList.add('following');
                                } else {
                                    this.textContent = 'Theo dõi';
                                    this.classList.remove('following');
                                }
                            }
                        })
                        .catch(err => console.error(err));
                    });
                });
            })
            .catch(() => {
                modalLoading.style.display = 'none';
                modalList.style.display = 'block';
                modalList.innerHTML = '<div class="text-center py-4 text-danger small">Không thể tải danh sách. Vui lòng thử lại.</div>';
            });
    }

    if (btnOpenFollowers) {
        btnOpenFollowers.addEventListener('click', function() {
            if (followersModalInstance) followersModalInstance.show();
            loadUserList('followers');
        });
    }

    if (btnOpenFollowing) {
        btnOpenFollowing.addEventListener('click', function() {
            if (followersModalInstance) followersModalInstance.show();
            loadUserList('following');
        });
    }

    if (tabFollowersBtn) {
        tabFollowersBtn.addEventListener('click', function() {
            loadUserList('followers');
        });
    }

    if (tabFollowingBtn) {
        tabFollowingBtn.addEventListener('click', function() {
            loadUserList('following');
        });
    }
});
</script>
@endpush

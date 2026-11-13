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
            <div class="modal-content" style="background: #181818; border: 1px solid #2e2e2e; border-radius: 20px; color: #fff; box-shadow: 0 10px 40px rgba(0,0,0,0.85); overflow: hidden;">
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
        color: #ffffff;
        text-decoration: underline;
    }
    .followers-modal-tab {
        background: transparent;
        border: none;
        border-bottom: 2px solid transparent;
        color: #777777;
        font-size: 14px;
        font-weight: 600;
        padding: 10px 0;
        cursor: pointer;
        transition: all 0.15s ease;
    }
    .followers-modal-tab:hover {
        color: #ffffff;
    }
    .followers-modal-tab.active {
        color: #ffffff;
        border-bottom-color: #ffffff;
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
        background: rgba(255, 255, 255, 0.04);
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
        border: 1px solid #383838;
        background: #ffffff;
        color: #000000;
        cursor: pointer;
        transition: all 0.15s ease;
        flex-shrink: 0;
    }
    .btn-user-modal-follow:hover {
        opacity: 0.9;
    }
    .btn-user-modal-follow.following {
        background: transparent;
        color: #777777;
        border-color: #383838;
    }
</style>
@endsection

@push('threads-scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const csrf = document.querySelector('meta[name="csrf-token"]').content;
    const modalElement = document.getElementById('followersModal');
    const list = document.getElementById('followersModalList');
    const loading = document.getElementById('followersModalLoading');
    const modal = bootstrap.Modal.getOrCreateInstance(modalElement);
    const more = document.createElement('button');
    more.type = 'button'; more.className = 'btn-threads-outline m-3'; more.textContent = 'Tải thêm'; more.hidden = true;
    const listStatus = document.createElement('p'); listStatus.setAttribute('role', 'status'); list.after(listStatus); listStatus.after(more);
    let version = 0, next = null, busy = false;
    const urls = { followers: @json(route('users.followers', $user->username ?: $user->id)), following: @json(route('users.following', $user->username ?: $user->id)) };
    const follow = async (id, control) => {
        if (control.disabled || !window.checkAuth('theo dõi')) return;
        control.disabled = true;
        try {
            const response = await fetch('/users/' + id + '/follow', { method: 'POST', headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf } });
            const data = await response.json();
            if (!response.ok) throw new Error(data.message || 'Không theo dõi được tài khoản này.');
            control.textContent = data.follow_label;
            control.classList.toggle('following', data.following || data.requested);
            if (Number(id) === @json($user->id)) document.querySelectorAll('#profileFollowersCount, #modalFollowersCount').forEach(count => count.textContent = data.followers_count);
            if (data.viewer_id === @json($user->id)) document.querySelectorAll('#profileFollowingCount, #modalFollowingCount').forEach(count => count.textContent = data.viewer_following_count);
        } catch (error) { window.showToast?.(error.message); }
        finally { control.disabled = false; }
    };
    document.getElementById('btnFollowUser')?.addEventListener('click', event => follow(event.currentTarget.dataset.userId, event.currentTarget));
    async function load(url, reset = false) {
        const current = version;
        busy = true; more.disabled = true; loading.style.display = 'block'; listStatus.textContent = '';
        try {
            const response = await fetch(url, { headers: { Accept: 'application/json' }, credentials: 'same-origin', cache: 'no-store' });
            const data = await response.json();
            if (current !== version) return;
            if (!response.ok || !Array.isArray(data.users)) throw new Error(data.message || 'Không tải được danh sách.');
            if (reset) list.replaceChildren();
            list.style.display = 'block';
            data.users.forEach(user => {
                const row = document.createElement('div'); row.className = 'user-list-item-card';
                const link = document.createElement('a'); link.className = 'user-list-item-link'; link.href = user.profile_url;
                if (user.avatar_url) { const image = document.createElement('img'); image.src = user.avatar_url; image.alt = user.name; image.className = 'user-avatar-round'; image.width = 40; image.height = 40; link.append(image); }
                const label = document.createElement('span'); label.textContent = user.name + ' · ' + user.handle; link.append(label); row.append(link);
                if (!user.is_me) { const control = document.createElement('button'); control.type = 'button'; control.className = 'btn-user-modal-follow'; control.textContent = user.requested ? 'Đã gửi yêu cầu' : (user.is_following ? 'Đang theo dõi' : 'Theo dõi'); control.addEventListener('click', () => follow(user.id, control)); row.append(control); }
                list.append(row);
            });
            if (!list.children.length) list.textContent = 'Chưa có tài khoản nào.';
            next = data.nextPageUrl; more.hidden = !next;
        } catch (error) { if (current === version) { listStatus.textContent = error.message; list.style.display = 'block'; next = url; more.hidden = false; } }
        finally { if (current === version) { busy = false; more.disabled = false; loading.style.display = 'none'; } }
    }
    function open(tab) {
        version++; list.replaceChildren(); more.hidden = true;
        document.querySelectorAll('.followers-modal-tab').forEach(button => button.classList.toggle('active', button.dataset.tab === tab));
        modal.show(); void load(urls[tab], true);
    }
    document.getElementById('btnOpenFollowersModal')?.addEventListener('click', () => open('followers'));
    document.getElementById('btnOpenFollowingModal')?.addEventListener('click', () => open('following'));
    document.querySelectorAll('.followers-modal-tab').forEach(button => button.addEventListener('click', () => open(button.dataset.tab)));
    more.addEventListener('click', () => { if (!busy && next) void load(next); });
    modalElement.addEventListener('hidden.bs.modal', () => { version++; busy = false; });
});
</script>
@endpush

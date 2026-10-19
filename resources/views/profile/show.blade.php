@extends('layouts.threads')

@section('content')

    <!-- Profile Header Section -->
    <div class="profile-header-card p-4 rounded-4 mb-4" style="background: #181818; border: 1px solid var(--border-color);">
        <div class="d-flex justify-content-between align-items-start gap-3">
            <div>
                <h3 class="text-white fw-bold mb-1 fs-4">{{ $user->name }}</h3>
                <div class="text-secondary small mb-3">{{ $user->handle() }}</div>
                @if($user->bio)
                    <p class="text-white fs-6 mb-3" style="line-height: 1.5;">{{ $user->bio }}</p>
                @endif
                <div class="d-flex gap-3 text-secondary small">
                    <span><strong class="text-white" id="profileFollowersCount">{{ $followersCount }}</strong> người theo dõi</span>
                    <span>·</span>
                    <span><strong class="text-white">{{ $followingCount }}</strong> đang theo dõi</span>
                    <span>·</span>
                    <span><strong class="text-white">{{ $threadsCount }}</strong> threads</span>
                </div>
            </div>
            <div>
                @if($user->avatar_path)
                    <img src="{{ route('avatar.show', $user->id) }}" alt="{{ $user->name }}" style="width: 80px; height: 80px; border-radius: 50%; object-fit: cover; border: 2px solid #333;">
                @else
                    <div style="width: 80px; height: 80px; border-radius: 50%; background: #262626; color: #fff; display: flex; align-items: center; justify-content: center; font-size: 32px; font-weight: bold; border: 2px solid #333;">
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
                        <span id="followBtnText">{{ $isFollowing ? 'Đang theo dõi' : 'Theo dõi' }}</span>
                    </button>
                @endif
            @else
                <a href="{{ route('login') }}" class="btn-threads-primary w-100 text-center py-2">
                    Theo dõi
                </a>
            @endauth
        </div>
    </div>

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
    <div id="profileThreadsContainer">
        @include('threads.partials.thread_list', ['threads' => $threads])
    </div>

    <!-- Pagination -->
    <div class="mt-4">
        {{ $threads->links() }}
    </div>

@endsection

@push('threads-scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
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
                }
            })
            .catch(err => console.error(err));
        });
    }
});
</script>
@endpush

@forelse($threads as $thread)
    @include('threads.partials.thread_card', ['thread' => $thread, 'userInteractions' => $userInteractions ?? []])
@empty
    @if(($tab ?? '') === 'following')
    <div class="text-center py-5" data-following-empty>
        <div class="mb-3 text-secondary"><x-journal-icon name="people" :size="36"/></div>
        <h5 class="fw-bold mb-2">Chưa có bài viết từ người bạn theo dõi</h5>
        <p class="text-secondary small mb-3">Theo dõi những tài khoản bạn quan tâm để xem bài viết của họ ở đây.</p>
        <a href="{{ route('search.index', ['type' => 'people']) }}" class="btn-threads-outline">Khám phá tài khoản</a>
    </div>
    @else
    <div class="text-center py-5">
        <div class="mb-3 text-secondary" style="font-size: 40px;">💬</div>
        <h5 class="fw-bold text-white mb-1">Chưa có bài viết nào ở đây</h5>
        <p class="text-secondary small mb-3">Hãy là người đầu tiên bắt đầu một chuỗi thảo luận mới!</p>
        @auth
            <button type="button" class="btn-threads-primary" data-bs-toggle="modal" data-bs-target="#newThreadModal">
                Đăng bài viết ngay
            </button>
        @else
            <a href="{{ route('login') }}" class="btn-threads-primary">
                Đăng nhập để đăng bài
            </a>
        @endauth
    </div>
    @endif
@endforelse

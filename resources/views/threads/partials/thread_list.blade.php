<<<<<<< HEAD
@forelse($threads as $thread)
    @include('threads.partials.thread_card', ['thread' => $thread])
@empty
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
@endforelse
=======
@foreach($threads as $thread)
    @include('threads.partials.thread_card', ['thread' => $thread])
@endforeach
>>>>>>> Phuong

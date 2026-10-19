@extends('layouts.threads')

@section('content')

    <!-- Top Bar with Back Button -->
    <div class="d-flex align-items-center gap-3 py-3 mb-2 border-bottom border-dark">
        <a href="{{ route('home') }}" class="btn-threads-outline rounded-circle p-2 d-inline-flex align-items-center justify-content-center" style="width: 38px; height: 38px;" title="Quay lại">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="19" y1="12" x2="5" y2="12"></line>
                <polyline points="12 19 5 12 12 5"></polyline>
            </svg>
        </a>
        <h4 class="text-white fs-5 fw-bold mb-0">Thread</h4>
    </div>

    <!-- Parent Thread (if this thread is a reply) -->
    @if($thread->parent)
        <div class="mb-0">
            @include('threads.partials.thread_card', ['thread' => $thread->parent, 'hasReplies' => true])
        </div>
    @endif

    <!-- Main Active Thread -->
    <div class="main-thread-focus-area">
        @include('threads.partials.thread_card', ['thread' => $thread, 'isReplyItem' => (bool)$thread->parent])
    </div>

    <!-- Quick Reply Box for this Thread -->
    <div id="replyBox" class="reply-composer-section p-3 rounded-4 my-3" style="background: #181818; border: 1px solid var(--border-color);">
        @auth
            <form action="{{ route('threads.store') }}" method="post" id="replyForm">
                @csrf
                <input type="hidden" name="parent_id" value="{{ $thread->id }}">
                <input type="hidden" name="type" value="reply">

                <div class="d-flex gap-3 align-items-start">
                    @if(auth()->user()->avatar_path)
                        <img src="{{ route('avatar.show', auth()->user()->id) }}" alt="{{ auth()->user()->name }}" class="user-avatar-round">
                    @else
                        <div class="user-avatar-letter">{{ mb_substr(auth()->user()->name, 0, 1) }}</div>
                    @endif
                    <div class="flex-grow-1">
                        <div class="text-secondary small mb-1">
                            Trả lời <span class="text-white fw-bold">{{ $thread->author->handle() }}</span>
                        </div>
                        <textarea 
                            name="content" 
                            id="replyContentInput" 
                            class="w-100 bg-transparent border-0 text-white outline-none p-0" 
                            style="font-size: 15px; resize: none; min-height: 60px; outline: none !important;"
                            placeholder="Nhập câu trả lời của bạn..." 
                            maxlength="2000"
                            required
                        ></textarea>
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center pt-2 mt-2 border-top border-dark">
                    <span class="text-secondary small">Nhấn Đăng để gửi phản hồi</span>
                    <button type="submit" class="btn-threads-primary px-3 py-1">
                        Trả lời
                    </button>
                </div>
            </form>
        @else
            <div class="text-center py-2">
                <span class="text-secondary small">Vui lòng <a href="{{ route('login') }}" class="text-white text-decoration-underline">đăng nhập</a> để tham gia trả lời cuộc trò chuyện này.</span>
            </div>
        @endauth
    </div>

    <!-- Replies Chain -->
    <div class="replies-chain-container">
        <h6 class="text-secondary fw-semibold mb-3 px-1">Tất cả câu trả lời ({{ $replies->count() }})</h6>
        @forelse($replies as $reply)
            @include('threads.partials.thread_card', ['thread' => $reply, 'isReplyItem' => true])
        @empty
            <div class="text-center py-4 text-secondary small">
                Chưa có câu trả lời nào. Hãy là người đầu tiên trả lời!
            </div>
        @endforelse
    </div>

@endsection

@push('threads-scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const replyInput = document.getElementById('replyContentInput');
    if (replyInput) {
        replyInput.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = (this.scrollHeight) + 'px';
        });

        if (window.location.hash === '#replyBox') {
            replyInput.focus();
        }
    }
});
</script>
@endpush

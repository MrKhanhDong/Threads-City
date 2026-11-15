@extends('layouts.threads')
@php($pageTitle = 'Bài viết')

@section('page-heading')
    <div class="thread-detail-nav">
        <a class="thread-detail-back" href="{{ route('home') }}" aria-label="Quay lại bảng tin" title="Quay lại"><x-journal-icon name="arrow-left" :size="22"/></a>
        <div class="thread-detail-nav-copy"><h1>Bài viết</h1><span>{{ number_format($thread->views_count, 0, ',', '.') }} lượt xem</span></div>
    </div>
@endsection

@section('content')

    <!-- Parent Thread (if this thread is a reply) -->
    @if($thread->parent)
        <div class="thread-parent-context">
            @include('threads.partials.thread_card', ['thread' => $thread->parent, 'hasReplies' => true, 'userInteractions' => $userInteractions ?? []])
        </div>
    @endif

    <!-- Main Active Thread -->
    <div class="main-thread-focus-area thread-detail-root" data-live-region="thread">
        @include('threads.partials.thread_card', ['thread' => $thread, 'isReplyItem' => (bool)$thread->parent, 'userInteractions' => $userInteractions ?? []])
    </div>

    <section id="replyBox" class="thread-discussion-entry" data-reply-home="{{ $thread->id }}" aria-label="Tham gia bình luận">
        <div class="thread-discussion-toolbar" data-live-region="reply-summary"><h2>Bình luận <span data-thread-replies-total>{{ $replies->count() }}</span></h2><span>Cũ nhất trước</span></div>
        <div class="thread-discussion-compose">
            @auth
            <div data-thread-detail-composer></div>
            <button type="button" class="thread-detail-reply-prompt" data-thread-detail-prompt aria-label="Viết bình luận"><x-avatar :user="auth()->user()"/><span>Trả lời {{ $thread->author->handle() }}…</span><x-journal-icon name="image" :size="21"/></button>
            @else
            <div class="thread-detail-signin"><span>Đăng nhập để tham gia bình luận.</span><a class="btn-threads-outline" href="{{ route('login') }}">Đăng nhập</a></div>
            @endauth
        </div>
    </section>

    <!-- Replies Chain -->
    <div class="replies-chain-container thread-discussion-list" data-live-region="replies" data-live-list>
        @forelse($replies as $reply)
            @include('threads.partials.thread_card', ['thread' => $reply, 'isReplyItem' => true, 'userInteractions' => $userInteractions ?? []])
        @empty
            <div class="thread-discussion-empty" data-no-replies>
                <span><x-journal-icon name="message" :size="25"/></span><strong>Bắt đầu cuộc trò chuyện</strong><p>Chưa có bình luận nào. Chia sẻ suy nghĩ của bạn nhé.</p>
            </div>
        @endforelse
    </div>

@endsection

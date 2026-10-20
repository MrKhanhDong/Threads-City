@extends('layouts.public')
@php($pageTitle = 'Cuộc trò chuyện')
@section('content')
<div class="stream-detail-heading"><a href="{{ route('home') }}">← Bảng tin</a><h1>Thread</h1></div>
@if($thread->parent && $thread->parent->status === 'published')<a class="stream-parent-link" href="{{ route('threads.show', $thread->parent) }}">Đang trả lời {{ $thread->parent->author->name }} · Xem bài gốc ↗</a>@endif
@include('threads.partials.thread_card', ['thread' => $thread])
<div class="stream-reply-heading"><h2>Trả lời <span data-thread-replies-total>{{ $thread->replies_count }}</span></h2>@auth<button type="button" class="btn-threads-outline" data-compose="reply" data-target-id="{{ $thread->id }}" data-context="{{ $thread->author->name }}: {{ \Illuminate\Support\Str::limit(strip_tags($thread->content), 100) }}">Viết trả lời</button>@else<a class="btn-threads-outline" href="{{ route('login') }}">Đăng nhập để trả lời</a>@endauth</div>
<div data-stream-replies="{{ $thread->id }}">@include('threads.partials.thread_list', ['threads' => $replies])</div>
@if($replies->isEmpty())<p class="stream-no-replies" data-no-replies>Chưa có trả lời. Chia sẻ suy nghĩ của bạn nhé.</p>@endif
<div class="journal-pagination">{{ $replies->links() }}</div>
@endsection
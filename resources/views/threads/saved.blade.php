<<<<<<< HEAD
@extends('layouts.threads')

@section('content')

    <!-- Top Bar -->
    <div class="d-flex align-items-center gap-3 py-3 mb-3 border-bottom border-dark">
        <a href="{{ route('home') }}" class="btn-threads-outline rounded-circle p-2 d-inline-flex align-items-center justify-content-center" style="width: 38px; height: 38px;" title="Quay lại">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="19" y1="12" x2="5" y2="12"></line>
                <polyline points="12 19 5 12 12 5"></polyline>
            </svg>
        </a>
        <div>
            <h4 class="text-white fs-5 fw-bold mb-0">Bài viết đã lưu</h4>
            <small class="text-secondary">Bộ sưu tập các bài viết yêu thích của bạn</small>
        </div>
    </div>

    <!-- Saved Threads Feed -->
    <div id="savedThreadsContainer">
        @include('threads.partials.thread_list', ['threads' => $threads])
    </div>

    <!-- Pagination -->
    <div class="mt-4">
        {{ $threads->links() }}
    </div>

@endsection
=======
@extends('layouts.public')
@php($pageTitle = 'Bài viết đã lưu')
@section('content')
<div class="stream-detail-heading"><a href="{{ route('home') }}">← Bảng tin</a><h1>Đã lưu</h1></div>
<div data-stream-feed>@include('threads.partials.thread_list', ['threads' => $threads])</div>
@if($threads->isEmpty())<div class="empty-state"><x-journal-icon name="bookmark" :size="36"/><h2>Góc đọc còn trống</h2><p>Mở menu bài viết và chọn “Lưu bài viết” để đọc lại sau.</p><a class="btn-threads-outline" href="{{ route('home') }}">Khám phá bảng tin</a></div>@endif
<div class="journal-pagination">{{ $threads->links() }}</div>
@endsection
>>>>>>> Phuong

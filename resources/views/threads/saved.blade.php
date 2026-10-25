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

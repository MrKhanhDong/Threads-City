@extends('layouts.threads')

@section('content')

    <!-- Feed Header Bar: Dành cho bạn -->
    <div class="feed-header-bar">
        <div class="feed-header-title dropdown">
            <span class="cursor-pointer d-flex align-items-center gap-1" data-bs-toggle="dropdown" aria-expanded="false">
                {{ $tab === 'following' ? 'Đang theo dõi' : 'Dành cho bạn' }}
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" class="opacity-75">
                    <polyline points="6 9 12 15 18 9"></polyline>
                </svg>
            </span>
            <ul class="dropdown-menu dropdown-menu-dark threads-dropdown-menu">
                <li><a class="dropdown-item {{ $tab === 'for-you' ? 'active' : '' }}" href="{{ route('home', ['tab' => 'for-you']) }}">Dành cho bạn</a></li>
                <li><a class="dropdown-item {{ $tab === 'following' ? 'active' : '' }}" href="{{ route('home', ['tab' => 'following']) }}">Đang theo dõi</a></li>
            </ul>
        </div>
        <div class="feed-header-tools">
            <button type="button" class="feed-tool-btn" title="Thêm cột" @auth data-bs-toggle="modal" data-bs-target="#newThreadModal" @else onclick="window.checkAuth('thêm cột nội dung')" @endauth>
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="3" width="18" height="18" rx="3" ry="3"></rect>
                    <line x1="12" y1="8" x2="12" y2="16"></line>
                </svg>
            </button>
            <button type="button" class="feed-tool-btn" title="Cài đặt">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"></circle>
                    <circle cx="8" cy="12" r="1.2" fill="currentColor"></circle>
                    <circle cx="12" cy="12" r="1.2" fill="currentColor"></circle>
                    <circle cx="16" cy="12" r="1.2" fill="currentColor"></circle>
                </svg>
            </button>
        </div>
    </div>

    <!-- Quick Inline Composer (Matches screenshot) -->
    @include('threads.partials.composer')

    <!-- Threads Feed Container -->
    <div id="threadsFeedContainer" data-live-region="feed" data-live-list>
        @include('threads.partials.thread_list', ['threads' => $threads, 'userInteractions' => $userInteractions ?? []])
    </div>

    <!-- Pagination / Load more -->
    @if($threads->hasMorePages())
        <div class="text-center py-4" id="loadMoreSection">
            <button type="button" class="btn-threads-outline px-4 py-2" id="btnLoadMoreThreads" data-next-url="{{ $threads->nextPageUrl() }}">
                <span id="loadMoreText">Tải thêm bài viết</span>
                <span id="loadMoreSpinner" class="spinner-border spinner-border-sm ms-2" style="display: none;" role="status"></span>
            </button>
        </div>
    @endif

@endsection

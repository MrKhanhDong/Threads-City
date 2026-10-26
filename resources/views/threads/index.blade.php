@extends('layouts.threads')
@php($pageTitle = request()->filled('q') || request()->boolean('search') ? 'Tìm kiếm' : ($tab === 'following' ? 'Đang theo dõi' : 'Dành cho bạn'))
@section('content')
<nav class="stream-tabs" aria-label="Chọn bảng tin">
    <a href="{{ route('home') }}" @class(['is-active' => $tab === 'for-you'])>Dành cho bạn</a>
    <a href="{{ route('home', ['tab' => 'following']) }}" @class(['is-active' => $tab === 'following'])>Đang theo dõi</a>
</nav>
@if(request()->boolean('search') || request()->filled('q'))
<form method="get" action="{{ route('home') }}" class="journal-restored-search" role="search">
    <input type="hidden" name="search" value="1">
    <label class="visually-hidden" for="journal-search-input">Tìm kiếm bài viết</label>
    <input id="journal-search-input" type="search" name="q" value="{{ is_string(request('q')) ? request('q') : '' }}" maxlength="150" placeholder="Tìm bài viết, chủ đề…" autofocus>
    <button type="submit" class="btn-threads-outline">Tìm kiếm</button>
</form>
@endif

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

@extends('layouts.public')
@php($pageTitle = $tab === 'following' ? 'Đang theo dõi' : 'Dành cho bạn')
@section('content')
<nav class="stream-tabs" aria-label="Chọn bảng tin"><a href="{{ route('home') }}" @class(['is-active' => $tab === 'for-you']) @if($tab === 'for-you') aria-current="page" @endif>Dành cho bạn</a><a href="{{ route('home', ['tab' => 'following']) }}" @class(['is-active' => $tab === 'following']) @if($tab === 'following') aria-current="page" @endif>Đang theo dõi</a></nav>
<div class="stream-quick-composer">@auth<x-avatar :user="auth()->user()"/><button type="button" data-compose="new" class="stream-compose-placeholder">Có gì mới?</button><button type="button" class="stream-submit" data-compose="new">Đăng</button>@else<a href="{{ route('login') }}" class="stream-guest-invite"><x-journal-icon name="write"/><span>Đăng nhập để chia sẻ suy nghĩ của bạn</span><span>Đăng nhập ↗</span></a>@endauth</div>
@if(request()->filled('q'))<div class="stream-search-result">Kết quả cho “{{ request('q') }}” <a href="{{ route('home') }}">Xóa tìm kiếm</a></div>@endif
<div data-stream-feed data-stream-primary="{{ $tab }}">
    @include('threads.partials.thread_list', ['threads' => $threads])
</div>
@if($threads->isEmpty())<div class="empty-state"><x-journal-icon name="message" :size="36"/><h1>{{ request()->filled('q') ? 'Chưa tìm thấy bài viết' : 'Bắt đầu một cuộc trò chuyện' }}</h1><p>{{ $tab === 'following' ? 'Theo dõi một người từ trang cá nhân để xem bài của họ tại đây.' : 'Một suy nghĩ nhỏ cũng có thể mở đầu câu chuyện hay.' }}</p>@auth<button type="button" class="stream-submit" data-compose="new">Viết Thread đầu tiên</button>@endauth</div>@endif
@if($threads->hasMorePages())<div class="stream-load-more"><a href="{{ $threads->nextPageUrl() }}" class="btn-threads-outline" data-stream-more>Tải thêm bài viết</a></div>@endif
@endsection

@extends('layouts.public')
@php($pageTitle = 'Đã thích')
@section('content')
<div class="reader-space-heading"><h1>Đã thích</h1><p>Những bài viết bạn đã thả tim. Chỉ bạn xem được danh sách này.</p></div>
<div data-stream-feed>@include('threads.partials.thread_list', ['threads' => $threads])</div>
@if($threads->isEmpty())<div class="empty-state"><x-journal-icon name="heart" :size="36"/><h2>Chưa có bài viết đã thích</h2><p>Thả tim một câu chuyện yêu thích để tìm lại ở đây.</p><a class="btn-threads-outline" href="{{ route('home') }}">Khám phá bảng tin</a></div>@endif
<div class="journal-pagination">{{ $threads->links() }}</div>
@endsection

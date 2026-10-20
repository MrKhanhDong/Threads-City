@extends('layouts.public')
@php($pageTitle = 'Bài viết đã lưu')
@section('content')
<div class="stream-detail-heading"><a href="{{ route('home') }}">← Bảng tin</a><h1>Đã lưu</h1></div>
<div data-stream-feed>@include('threads.partials.thread_list', ['threads' => $threads])</div>
@if($threads->isEmpty())<div class="empty-state"><x-journal-icon name="bookmark" :size="36"/><h2>Góc đọc còn trống</h2><p>Mở menu bài viết và chọn “Lưu bài viết” để đọc lại sau.</p><a class="btn-threads-outline" href="{{ route('home') }}">Khám phá bảng tin</a></div>@endif
<div class="journal-pagination">{{ $threads->links() }}</div>
@endsection
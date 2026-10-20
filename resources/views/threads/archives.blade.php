@extends('layouts.public')
@php($pageTitle = 'Lưu trữ')
@section('content')
<div class="reader-space-heading"><h1>Lưu trữ</h1><p>Bài viết được ẩn khỏi bảng tin, tìm kiếm và trang cá nhân. Chỉ bạn có thể khôi phục bài của mình.</p></div>
@forelse($threads as $thread)
    <article class="reader-archive-card"><div><h2>{{ $thread->title ?: 'Bài viết của bạn' }}</h2><p>{{ \Illuminate\Support\Str::limit(html_entity_decode(strip_tags($thread->content), ENT_QUOTES | ENT_HTML5, 'UTF-8'), 240) }}</p><small>Đã lưu trữ {{ $thread->archived_at->diffForHumans() }}</small></div>@if($thread->media->isNotEmpty())<div class="reader-archive-images">@foreach($thread->media as $media)<img src="{{ $media->url() }}" alt="{{ $media->alt ?: 'Ảnh trong bài viết' }}" loading="lazy">@endforeach</div>@elseif($thread->cover)<div class="reader-archive-images"><img src="{{ $thread->cover->url() }}" alt="{{ $thread->cover->alt ?: 'Ảnh bìa' }}" loading="lazy"></div>@endif<form method="post" action="{{ route('threads.unarchive', $thread) }}">@csrf<button type="submit" class="btn-threads-outline">Khôi phục bài viết</button></form></article>
@empty<div class="empty-state"><x-journal-icon name="archive" :size="36"/><h2>Chưa có bài viết lưu trữ</h2><p>Mở menu bài viết của bạn và chọn “Lưu trữ bài viết” để ẩn tạm thời.</p><a class="btn-threads-outline" href="{{ route('profile.show', auth()->user()->username ?: auth()->id()) }}">Đến trang cá nhân</a></div>@endforelse
<div class="journal-pagination">{{ $threads->links() }}</div>
@endsection

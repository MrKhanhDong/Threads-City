@extends('layouts.blog')
@section('title', 'Tất cả bài viết')
@section('content')
    <header class="page-heading"><span class="overline">THE JOURNAL</span><h1>Tất cả bài viết<span class="heading-dot">.</span></h1><p>Những ghi chép, trải nghiệm và ý tưởng mình muốn lưu lại.</p></header>
    <form class="archive-search" action="{{ route('posts.index') }}" method="get"><x-icon name="search" /><label class="sr-only" for="search">Tìm bài viết</label><input id="search" type="search" name="q" value="{{ $query }}" placeholder="Tìm theo tiêu đề, nội dung hoặc thẻ…" maxlength="200">@if($category)<input type="hidden" name="category" value="{{ $category }}">@endif @if($tag)<input type="hidden" name="tag" value="{{ $tag }}">@endif<button class="button button--black" type="submit">Tìm kiếm</button></form>
    <nav class="filter-tabs" aria-label="Lọc chuyên mục"><a href="{{ route('posts.index', array_filter(['q' => $query, 'tag' => $tag])) }}" @class(['is-active' => $category === ''])>Tất cả</a>@foreach($categories as $item)<a href="{{ route('posts.index', array_filter(['category' => $item['slug'], 'q' => $query, 'tag' => $tag])) }}" @class(['is-active' => $category === $item['slug']])>{{ $item['name'] }}</a>@endforeach</nav>
    <div class="result-summary"><span>{{ $posts->total() }} bài viết @if($query) cho “{{ $query }}” @endif @if($tag) · Thẻ: {{ $tag }} @endif</span>@if($query || $category || $tag)<a href="{{ route('posts.index') }}">Xóa bộ lọc</a>@else<span>Mới nhất trước</span>@endif</div>
    <div class="post-list">@forelse($posts as $post)<x-post-card :post="$post" />@empty<div class="empty-state"><x-icon name="search" size="36" /><h2>Chưa tìm thấy bài viết</h2><p>Thử một từ khóa khác hoặc xem tất cả ghi chép.</p><a class="button" href="{{ route('posts.index') }}">Xem tất cả bài viết</a></div>@endforelse</div>
    <x-pagination :paginator="$posts" />
@endsection

@extends('layouts.public')
@php($pageTitle = $heading)
@section('content')
<section class="journal-archive">
    <p class="journal-label">GÓC ĐỌC · NHỮNG CÂU CHUYỆN</p><h1>{{ $heading }}</h1><p class="lead-muted">{{ $subtitle }}</p>
    @if(!request()->routeIs('saved'))
    <form method="get" action="{{ route('posts.index') }}" class="filter-bar" id="journal-search" role="search">
        <x-field name="q" label="Tìm bài viết" :value="$filters['q'] ?? ''" placeholder="Bạn muốn đọc điều gì?" maxlength="150"/>
        <div class="field"><label for="category">Chuyên mục</label><select class="form-select" id="category" name="category"><option value="">Tất cả chuyên mục</option>@foreach($navigationCategories as $category)<option value="{{ $category->slug }}" @selected(($filters['category'] ?? '') === $category->slug)>{{ $category->name }}</option>@endforeach</select></div>
        <div class="field"><label for="tag">Thẻ</label><select class="form-select" id="tag" name="tag"><option value="">Tất cả thẻ</option>@foreach($tags ?? [] as $tag)<option value="{{ $tag->slug }}" @selected(($filters['tag'] ?? '') === $tag->slug)>{{ $tag->name }}</option>@endforeach</select></div>
        <button class="btn btn-forest" type="submit">Tìm kiếm ↗</button>
    </form>
    @endif
    <div class="journal-archive-count"><span>{{ $posts->total() }} bài viết</span>@if(request()->filled('q') || request()->filled('category') || request()->filled('tag'))<a href="{{ route('posts.index') }}">Xóa bộ lọc ↗</a>@endif</div>
</section>
<div class="journal-feed">@forelse($posts as $post)<x-post-card :post="$post"/>@empty<div class="empty-state"><x-journal-icon name="book" :size="36"/><h2>{{ request()->routeIs('saved') ? 'Góc đọc còn trống' : 'Chưa tìm thấy bài viết' }}</h2><p>{{ request()->routeIs('saved') ? 'Mở một bài viết và chọn “Lưu bài” để đọc lại sau.' : 'Thử từ khóa hoặc bộ lọc khác nhé.' }}</p><a class="btn btn-outline-forest" href="{{ route('posts.index') }}">Khám phá bài viết</a></div>@endforelse</div>
<div class="journal-pagination">{{ $posts->links() }}</div>
@endsection

@extends('layouts.public')
@section('content')
<section class="journal-welcome"><p class="journal-label"><span class="journal-status-dot"></span> CHÀO MỪNG BẠN GHÉ CHƠI</p><h1>Những điều nhỏ,<br><span>những câu chuyện riêng.</span></h1><p>Một vài ghi chép về cuộc sống, công nghệ và những góc nhìn trong cộng đồng. Chọn một bài, ngồi lại một chút nhé.</p></section>
<div class="journal-feed-heading"><h2>Mới từ góc viết</h2><span>Sắp xếp theo mới nhất <span aria-hidden="true">↓</span></span></div>
<nav class="journal-category-tabs" aria-label="Chọn chuyên mục"><a class="is-active" href="{{ route('home') }}" aria-current="page">Tất cả</a>@foreach($navigationCategories as $category)<a href="{{ route('categories.show', $category->slug) }}">{{ $category->name }}</a>@endforeach</nav>
<div class="journal-feed">
    @if($featured && $posts->onFirstPage())<x-post-card :post="$featured" :featured="true"/>@endif
    @forelse($posts as $post)<x-post-card :post="$post"/>@empty @if(!$featured)<div class="empty-state"><x-journal-icon name="book" :size="36"/><h2>Hẹn bạn ở câu chuyện đầu tiên.</h2><p>Hãy khám phá Threads và chia sẻ câu chuyện đầu tiên của bạn.</p><a href="{{ route('about') }}" class="btn btn-outline-forest">Về Threads ↗</a></div>@endif @endforelse
</div>
<div class="journal-pagination">{{ $posts->links() }}</div>
@endsection
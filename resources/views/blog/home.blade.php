@extends('layouts.blog')
@section('title', 'Trang chủ')
@section('content')
    <section class="welcome">
        <span class="overline"><span class="tiny-dot"></span>BLOG CÁ NHÂN CỦA {{ mb_strtoupper($author['name']) }}</span>
        <h1>Những dòng viết.<br>Những điều mình học.</h1>
        <p>Về code, cuộc sống và những điều đáng lưu lại.<br>Chào bạn, rất vui vì bạn ở đây.</p>
    </section>
    @if($featured)
        <div class="section-heading"><h2>Bài viết nổi bật</h2><span>01 / JOURNAL</span></div>
        <article class="featured-post">
            <a class="featured-image" href="{{ route('posts.show', $featured['slug']) }}">
                @if($featured['image'])<img src="{{ $featured['image'] }}" alt="{{ $featured['image_alt'] }}">@else<div class="featured-placeholder"><x-icon name="book" size="50" /></div>@endif
                <span class="image-caption">GHI CHÉP TỪ GÓC LÀM VIỆC</span>
            </a>
            <div class="featured-content">
                <div class="post-meta"><a href="{{ route('posts.index', ['category' => $featured['category_slug']]) }}">{{ $featured['category'] }}</a><span>·</span><time datetime="{{ $featured['date'] }}">{{ $featured['date_label'] }}</time></div>
                <h2><a href="{{ route('posts.show', $featured['slug']) }}">{{ $featured['title'] }}</a></h2>
                <p>{{ $featured['excerpt'] }}</p>
                <div class="featured-bottom"><a class="button button--black" href="{{ route('posts.show', $featured['slug']) }}">Đọc tiếp <x-icon name="arrow" size="17" /></a><span>{{ $featured['read_time'] }} phút đọc</span><x-bookmark :post="$featured" /></div>
            </div>
        </article>
        <div class="section-heading section-heading--latest"><h2>Ghi chép mới nhất</h2><a class="text-link" href="{{ route('posts.index') }}">Xem tất cả <x-icon name="arrow" size="16" /></a></div>
        <div class="post-list">@foreach($posts as $post)<x-post-card :post="$post" />@endforeach</div>
    @else
        <div class="empty-state"><x-icon name="book" size="36" /><h2>Một trang mới đang bắt đầu</h2><p>Chưa có bài viết được xuất bản. Hẹn bạn quay lại sớm.</p></div>
    @endif
    <div class="end-note"><span></span><p>Cảm ơn bạn đã ghé qua góc nhỏ này.</p><span></span></div>
@endsection

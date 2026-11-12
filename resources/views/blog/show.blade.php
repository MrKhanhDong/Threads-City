@extends('layouts.public')
@php
$pageTitle = $post->seo_title ?: $post->title;
$authorUrl = route('profile.show', $post->author->username ?: $post->author->id);
$breadcrumbs = [['label' => 'Bài viết', 'url' => route('posts.index')]];
if ($post->categories->first()) {
    $breadcrumbs[] = ['label' => $post->categories->first()->name, 'url' => route('categories.show', $post->categories->first()->slug)];
}
$breadcrumbs[] = ['label' => $post->title];
$seo = ['description'=>$post->meta_description ?: $post->excerpt, 'canonical'=>$post->canonical_url ?: route('posts.show',$post->slug), 'og_title'=>$post->og_title ?: $post->title, 'og_description'=>$post->og_description ?: $post->meta_description ?: $post->excerpt, 'image'=>($post->ogImage ?: $post->cover)?->url()];
$structured = ['@context'=>'https://schema.org','@type'=>'BlogPosting','headline'=>$post->title,'description'=>$seo['description'],'datePublished'=>$post->published_at?->toIso8601String(),'dateModified'=>$post->updated_at->toIso8601String(),'author'=>['@type'=>'Person','name'=>$post->author->name,'url'=>$authorUrl],'mainEntityOfPage'=>$seo['canonical']];
if($seo['image']) { $structured['image']=$seo['image']; }
@endphp
@push('head')
@if(!$preview)
<script type="application/ld+json">{!! json_encode($structured, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>
@endif
@endpush
@section('content')
<section class="container article-header">
@if($preview)<div class="alert alert-warning">Đang xem bản đã lưu · Không công khai nếu bài còn nháp/lên lịch. <a href="{{ route('admin.posts.edit',$post) }}">Quay lại editor ↗</a></div>@endif
<p class="eyebrow">{{ $post->categories->pluck('name')->join(' / ') }}</p><h1>{{ $post->title }}</h1><p class="article-excerpt">{{ $post->excerpt }}</p><div class="article-byline"><div class="author-line"><x-avatar :user="$post->author" /><span><a href="{{ $authorUrl }}">{{ $post->author->name }}</a><small>{{ $post->published_at?->format('d.m.Y') ?? 'Bản nháp' }} · {{ $post->readingMinutes() }} phút đọc</small></span></div><div class="article-actions">
<button type="button" class="btn btn-outline-forest btn-sm" data-share="{{ route('posts.show',$post->slug) }}">Chia sẻ ↗</button>
@if(!$preview)
<x-post-like :post="$post" :liked="$liked" :count="$likesCount" />
@endif
@if(!$preview)@auth<form method="post" action="{{ in_array($post->id,$savedIds) ? route('bookmarks.destroy',$post->id) : route('bookmarks.store',$post->id) }}">@csrf @if(in_array($post->id,$savedIds))@method('delete')@endif<button class="btn btn-outline-forest btn-sm">{{ in_array($post->id,$savedIds) ? 'Đã lưu ✓' : 'Lưu bài +' }}</button></form>@else<a class="btn btn-outline-forest btn-sm" href="{{ route('login') }}">Lưu bài +</a>@endauth @endif
</div></div><div class="article-cover"><x-cover :post="$post" /></div></section>
<div class="container article-layout"><aside class="article-toc">@if(count($body['headings'])>1)<p class="eyebrow">TRONG BÀI VIẾT</p><nav aria-label="Mục lục">@foreach($body['headings'] as $heading)<a class="toc-level-{{ $heading['level'] }}" href="#{{ $heading['id'] }}">{{ $heading['title'] }}</a>@endforeach</nav>@endif</aside><div class="article-main"><article class="article-content">{!! $body['html'] !!}</article>
<div class="article-tags">@foreach($post->tags as $tag)<a href="{{ route('tags.show',$tag->slug) }}">#{{ $tag->name }}</a>@endforeach</div><div class="author-box"><x-avatar :user="$post->author" /><div><p class="eyebrow">VIẾT BỞI {{ $post->author->name }}</p><p>{{ $post->author->bio ?: 'Cảm ơn bạn đã dành thời gian đọc những câu chuyện ở đây.' }}</p><a class="text-link" href="{{ $authorUrl }}">Xem hồ sơ ↗</a></div></div>
@if(!$preview)<section id="comments" class="comments-section"><h2>Để lại một lời nhắn</h2><p class="lead-muted">Một cuộc trò chuyện nhỏ, từ câu chuyện vừa đọc.</p>
@auth @if(auth()->user()->hasVerifiedEmail())<form method="post" action="{{ route('comments.store',$post->id) }}">@csrf<x-field type="textarea" name="body" label="Bình luận của bạn" :use-old="!old('parent_id')" rows="4" minlength="2" maxlength="5000" required placeholder="Chia sẻ suy nghĩ của bạn..."/><div class="form-footer"><small>Bình luận hiển thị ngay. Hãy trao đổi lịch sự và không chia sẻ thông tin nhạy cảm.</small><button class="btn btn-forest">Gửi lời nhắn ↗</button></div></form>@else<p><a href="{{ route('verification.notice') }}">Xác minh email</a> để gửi bình luận.</p>@endif @else<p><a class="text-link" href="{{ route('login') }}">Đăng nhập</a> hoặc <a class="text-link" href="{{ route('register') }}">tạo tài khoản độc giả</a> để tham gia trò chuyện.</p>@endauth
<div class="comment-list">@forelse($comments as $comment)@include('blog.thread',['comment'=>$comment])@empty<p class="muted">Chưa có bình luận. Bạn có thể là người bắt đầu câu chuyện.</p>@endforelse</div>{{ $comments->fragment('comments')->links() }}</section>@endif
</div></div>
@if($related->isNotEmpty())<section class="container related-section"><div class="section-heading"><h2>Có thể bạn cũng thích</h2><a href="{{ route('posts.index') }}">Tiếp tục đọc ↗</a></div><div class="post-grid">@foreach($related as $relatedPost)<x-post-card :post="$relatedPost" />@endforeach</div></section>@endif
@endsection

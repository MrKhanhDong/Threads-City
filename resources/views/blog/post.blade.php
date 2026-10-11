@extends('layouts.blog')
@section('title', $post['title'])
@section('description', $post['excerpt'])
@if($preview) @section('robots', 'noindex,nofollow') @endif
@section('toc')
@foreach($post['sections'] as $section)
@if($section['heading'])<a href="#section-{{ $loop->index }}"><span>{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>{{ $section['heading'] }}</a>@endif
@endforeach
@endsection
@section('content')
<a class="back-link" href="{{ $preview ? route('admin.posts.edit', $post['id']) : route('posts.index') }}"><x-icon name="back" size="17" /> {{ $preview ? 'Quay lại chỉnh sửa' : 'Tất cả bài viết' }}</a>
@if($preview)<p class="flash">Bản xem trước dành cho quản trị viên · Chưa phải trang công khai</p>@endif
<article class="article-detail">
<header class="article-header"><div class="post-meta"><a href="{{ route('posts.index', ['category' => $post['category_slug']]) }}">{{ $post['category'] }}</a><span>·</span><time datetime="{{ $post['date'] }}">{{ $post['date_label'] }}</time></div>
<h1>{{ $post['title'] }}</h1><p class="article-deck">{{ $post['excerpt'] }}</p>
<div class="article-byline"><span class="avatar avatar--small">{{ $author['initials'] }}</span><span><strong>{{ $post['author_name'] }}</strong><small>{{ $post['read_time'] }} phút đọc</small></span><div class="article-tools">@unless($preview)<x-bookmark :post="$post" /><button class="icon-button" type="button" data-copy-link aria-label="Sao chép liên kết"><x-icon name="link" /></button>@endunless</div></div></header>
@if($post['image'])<figure class="article-cover"><img src="{{ $post['image'] }}" alt="{{ $post['image_alt'] }}"></figure>@endif
<div class="prose">@foreach($post['sections'] as $section)<section id="section-{{ $loop->index }}">@if($section['heading'])<h2>{{ $section['heading'] }}</h2>@endif @foreach($section['paragraphs'] as $paragraph)<p>{{ $paragraph }}</p>@endforeach</section>@endforeach</div>
<div class="article-tags">@foreach($post['tags'] as $tag)<a class="pill" href="{{ route('posts.index', ['tag' => $tag]) }}">{{ $tag }}</a>@endforeach</div>
<div class="article-signoff"><span class="avatar">{{ $author['initials'] }}</span><div><strong>Viết bởi {{ $post['author_name'] }}</strong><p>{{ $author['bio'] }}</p><a class="text-link" href="{{ route('about') }}">Một chút về mình <x-icon name="arrow" size="16" /></a></div></div>
</article>
@unless($preview)
<section class="comments-section" id="comments">
<div class="section-heading"><h2>Để lại một lời nhắn</h2><span class="muted">{{ $comments->total() }} lời nhắn hiển thị</span></div>
@auth
<p class="muted">Bình luận của độc giả được duyệt trước khi hiển thị công khai.</p>
<form action="{{ route('comments.store', $post['id']) }}" method="post" class="form-stack">@csrf
<label for="comment-body">Lời nhắn của {{ auth()->user()->name }}</label><textarea id="comment-body" name="body" required minlength="2" maxlength="2000" rows="4" placeholder="Một suy nghĩ sau khi đọc bài viết…">{{ old('body') }}</textarea>
<button class="button button--black" type="submit">Gửi lời nhắn <x-icon name="arrow" size="16" /></button>
</form>
@else
<p class="muted"><a class="text-link" href="{{ route('login') }}">Đăng nhập</a> để gửi bình luận hoặc lưu bài vào danh sách đọc.</p>
@endauth
<div class="comment-list">
@forelse($comments as $comment)
<article class="comment"><div class="comment-top"><strong>{{ $comment->user->name }}</strong><small>{{ $comment->created_at->format('d/m/Y H:i') }}</small></div><p class="preserve-lines">{{ $comment->body }}</p>
@if($comment->status !== 'approved')<span class="status-chip">{{ $comment->status === 'pending' ? 'Đang chờ duyệt · chỉ bạn nhìn thấy' : 'Không được duyệt · chỉ bạn nhìn thấy' }}</span>@endif
@if(auth()->check() && (auth()->id() === $comment->user_id || auth()->user()->isAdmin()))
<form method="post" action="{{ route('comments.destroy', $comment) }}" data-confirm="Xóa lời nhắn này?">@csrf @method('DELETE')<button class="text-button" type="submit">Xóa lời nhắn</button></form>
@endif
</article>
@empty <p class="muted">Chưa có lời nhắn. Bạn có thể là người đầu tiên.</p> @endforelse
</div><x-pagination :items="$comments" />
</section>
@if($related->isNotEmpty())<section class="related"><div class="section-heading"><h2>Có thể bạn muốn đọc</h2></div>@foreach($related as $item)<x-post-card :post="$item" />@endforeach</section>@endif
@endunless
@endsection

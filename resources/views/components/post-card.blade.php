@props(['post'])
<article class="post-card">
    <div class="post-card-content">
        <div class="post-meta">
            <a href="{{ route('posts.index', ['category' => $post['category_slug']]) }}">{{ $post['category'] }}</a><span>·</span>
            <time datetime="{{ $post['date'] }}">{{ $post['date_label'] }}</time>
        </div>
        <h2><a href="{{ route('posts.show', $post['slug']) }}">{{ $post['title'] }}</a></h2>
        <p>{{ $post['excerpt'] }}</p>
        <div class="post-bottom">
            <span><x-icon name="clock" size="14" />{{ $post['read_time'] }} phút đọc</span>
            <a class="text-link" href="{{ route('posts.show', $post['slug']) }}">Đọc bài viết <x-icon name="arrow" size="16" /></a>
        </div>
    </div>
    @if($post['image'])
        <a class="post-thumbnail" href="{{ route('posts.show', $post['slug']) }}" tabindex="-1" aria-hidden="true">
            <img src="{{ $post['image'] }}" alt="" loading="lazy">
        </a>
    @endif
    <div class="card-save"><x-bookmark :post="$post" /></div>
</article>

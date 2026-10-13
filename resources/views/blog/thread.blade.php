@php($batch = $focusedRoot === $comment->id ? $focusedBatch : null)
<section class="comment-thread" data-comment-thread data-replies-url="{{ route('comments.replies', $comment) }}" data-next="{{ $batch['next'] ?? 0 }}" data-loaded="{{ $batch ? '1' : '0' }}" data-more="{{ !$batch || $batch['more'] ? '1' : '0' }}">
    @include('blog.comment', ['comment' => $comment])
    @if($comment->replies_count > 0)
        <div class="reply-controls">
            <button type="button" class="text-link link-button" data-replies-toggle aria-expanded="{{ $batch ? 'true' : 'false' }}" aria-controls="replies-{{ $comment->id }}">{{ $batch ? 'Ẩn bớt trả lời' : 'Xem trả lời' }}</button>
        </div>
        <div id="replies-{{ $comment->id }}" class="comment-replies" data-replies-panel @if(!$batch) hidden @endif>
            <button type="button" class="text-link link-button" data-replies-earlier @if(!$batch || !$batch['earlier']) hidden @endif>Xem các trả lời trước</button>
            <div data-replies-list>@if($batch)@include('blog.replies', ['replies' => $batch['replies']])@endif</div>
            <button type="button" class="text-link link-button" data-replies-more @if($batch && !$batch['more']) hidden @endif>Xem thêm 5 trả lời</button>
            <button type="button" class="text-link link-button ms-3" data-replies-collapse>Ẩn bớt trả lời</button>
            <p class="small muted" role="status" aria-live="polite" data-replies-status></p>
        </div>
        <noscript><a class="text-link small" href="{{ route('posts.show', ['slug' => $post->slug, 'comment' => $comment->replies()->orderBy('id')->value('id')]) }}#comment-{{ $comment->id }}">Xem trả lời (cần bật JavaScript để tải thêm)</a></noscript>
    @endif
</section>

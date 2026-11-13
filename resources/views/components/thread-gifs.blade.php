@props(['ids' => []])
@if(is_array($ids) && count($ids))
<div class="thread-gif-gallery" data-media-gallery aria-label="GIF đính kèm">
    @foreach($ids as $gifId)
        @if(is_string($gifId) && preg_match('/\A[a-zA-Z0-9]{1,100}\z/', $gifId))
        <figure class="thread-gif" data-giphy-id="{{ $gifId }}">
            <span role="status">Đang tải GIF…</span>
        </figure>
        @endif
    @endforeach
</div>
@endif

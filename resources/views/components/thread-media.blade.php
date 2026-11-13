@props(['items'])
@php($visuals = $items->filter(fn ($item) => !str_starts_with($item->mime_type, 'audio/')))
@if($visuals->isNotEmpty())
<div class="thread-media-strip" data-media-gallery data-media-count="{{ $visuals->count() }}" tabindex="0" role="region" aria-label="Tệp đính kèm, giữ chuột kéo hoặc vuốt ngang để xem thêm">
    @foreach($visuals as $item)
        <div class="thread-media-tile">
            @if(str_starts_with($item->mime_type, 'video/'))
                <div class="clip-player" data-player>
                    <video src="{{ $item->url() }}" controls playsinline muted loop preload="metadata" data-clip aria-label="Video đính kèm"></video>
                </div>
            @else
                <a href="{{ $item->url() }}" class="gallery-media-link" data-lightbox aria-label="Phóng to ảnh">
                    <img src="{{ $item->url() }}" alt="{{ $item->alt ?: 'Ảnh đính kèm' }}" loading="lazy">
                </a>
            @endif
        </div>
    @endforeach
</div>
@endif
@foreach($items->filter(fn ($item) => str_starts_with($item->mime_type, 'audio/')) as $item)
<div class="voice-player" data-player>
    <audio src="{{ $item->url() }}" controls preload="metadata" data-voice data-duration="{{ ($item->duration_ms ?? 0) / 1000 }}" aria-label="Ghi âm giọng nói"></audio>
    <div class="voice-waveform" aria-hidden="true">
        @foreach($item->waveform ?: array_fill(0, 40, 12) as $height)
            <span style="--wave-height: {{ max(8, min(100, (int) $height)) }}%"></span>
        @endforeach
    </div>
</div>
@endforeach

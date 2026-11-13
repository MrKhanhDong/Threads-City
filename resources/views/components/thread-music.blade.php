@props(['track' => null])
@if(is_array($track) && ($track['provider'] ?? '') === 'audius' && preg_match('/\A[a-zA-Z0-9]{1,64}\z/', $track['id'] ?? ''))
<section class="music-card" data-music-card data-player aria-label="Nhạc đính kèm" onclick="event.stopPropagation()">
    <div class="music-card-heading">
        @if(!empty($track['artwork']))<img src="{{ $track['artwork'] }}" alt="Bìa bài hát" loading="lazy" width="48" height="48">@endif
        <div class="music-track-info">
            <a href="{{ $track['url'] }}" target="_blank" rel="noopener noreferrer">{{ $track['title'] }}</a>
            <span>{{ $track['artist'] }} · Audius</span>
        </div>
    </div>
    <audio controls preload="none" data-music-audio src="https://api.audius.co/v1/tracks/{{ $track['id'] }}/stream?app_name=ThreadsCity" aria-label="Nghe {{ $track['title'] }}"></audio>
    <p data-music-error role="status" hidden>Không phát được bài hát. Bạn có thể mở bài hát trên Audius để kiểm tra.</p>
</section>
@endif

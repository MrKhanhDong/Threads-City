<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $thread->author->name }} trên Threads</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; }
        body { background: #101010; color: #ffffff; padding: 16px; display: flex; justify-content: center; align-items: flex-start; min-height: 100vh; }
        .embed-card { width: 100%; max-width: 520px; background: #181818; border: 1px solid rgba(255, 255, 255, 0.12); border-radius: 18px; padding: 18px 20px; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5); }
        .embed-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; }
        .embed-author-wrap { display: flex; align-items: center; gap: 12px; text-decoration: none; color: inherit; }
        .embed-avatar { width: 42px; height: 42px; border-radius: 50%; object-fit: cover; }
        .embed-avatar-fallback { width: 42px; height: 42px; border-radius: 50%; background: #333333; display: flex; align-items: center; justify-content: center; font-weight: 700; color: #ffffff; }
        .embed-author-names { display: flex; flex-direction: column; }
        .embed-author-name { font-weight: 700; font-size: 15px; color: #ffffff; }
        .embed-author-handle { font-size: 13px; color: #8e8e8e; }
        .embed-threads-logo { width: 24px; height: 24px; fill: currentColor; color: #ffffff; }
        .embed-content { font-size: 15px; line-height: 1.55; color: #f3f5f7; word-break: break-word; margin-bottom: 14px; white-space: pre-line; }
        .embed-content a { color: #0095f6; text-decoration: none; }
        .embed-media { border-radius: 14px; overflow: hidden; margin-bottom: 14px; border: 1px solid rgba(255, 255, 255, 0.08); }
        .embed-media img { width: 100%; max-height: 380px; object-fit: cover; display: block; }
        .embed-footer { display: flex; justify-content: space-between; align-items: center; padding-top: 12px; border-top: 1px solid rgba(255, 255, 255, 0.08); font-size: 13px; color: #8e8e8e; }
        .embed-stats { display: flex; gap: 14px; }
        .embed-cta { background: #ffffff; color: #000000; text-decoration: none; font-weight: 700; font-size: 12px; padding: 6px 14px; border-radius: 9999px; transition: opacity 0.15s; }
        .embed-cta:hover { opacity: 0.9; }
    </style>
</head>
<body>
    <div class="embed-card">
        <div class="embed-header">
            <a href="{{ route('threads.show', $thread->id) }}" target="_blank" rel="noopener" class="embed-author-wrap">
                @if($thread->author->avatar_path)
                    <img src="{{ route('avatar.show', $thread->author->id) }}" alt="{{ $thread->author->name }}" class="embed-avatar">
                @else
                    <div class="embed-avatar-fallback">{{ mb_substr($thread->author->name, 0, 1) }}</div>
                @endif
                <div class="embed-author-names">
                    <span class="embed-author-name">{{ $thread->author->name }}</span>
                    <span class="embed-author-handle">{{ $thread->author->handle() }}</span>
                </div>
            </a>
            <a href="{{ route('threads.show', $thread->id) }}" target="_blank" rel="noopener" title="Mở trên Threads">
                <svg class="embed-threads-logo" viewBox="0 0 192 192">
                    <path d="M141.537 88.9883C140.71 88.5919 139.87 88.2109 139.019 87.8451C137.537 60.5382 122.616 44.905 97.5619 44.745C97.4111 44.744 97.2604 44.744 97.1096 44.744C75.2536 44.744 57.0707 58.751 51.5204 79.882C46.0691 100.638 53.6841 123.332 70.8251 137.416C87.9661 151.5 112.43 155.083 133.024 146.516C148.971 139.878 159.227 124.965 160.827 106.012H144.741C143.342 118.847 136.035 129.351 124.636 134.1C109.117 140.556 90.6811 137.856 77.7656 127.243C64.8501 116.63 59.1121 99.5306 63.2201 83.8906C67.4326 67.8546 81.2336 57.214 97.7331 57.214C97.8471 57.214 97.9611 57.215 98.0751 57.216C113.627 57.315 124.516 67.045 125.753 84.148C117.753 82.522 108.887 81.879 99.5371 82.261C73.1971 83.337 57.4981 97.575 58.3341 117.747C59.1381 137.147 75.8361 149.336 96.0601 148.471C111.455 147.813 124.12 140.174 130.407 127.818C134.825 136.195 142.155 142.365 150.941 145.42C157.65 147.752 164.838 148.33 171.936 147.108L174.524 134.42C169.176 135.337 163.743 134.901 158.673 133.138C150.771 130.392 144.974 123.86 142.753 115.228C148.513 107.035 151.706 97.234 151.706 87.051C151.706 84.821 151.482 82.607 151.042 80.441L141.537 88.9883ZM127.469 110.15C125.132 117.841 117.844 134.871 96.6961 135.773C83.8401 136.321 73.1201 128.528 72.5851 115.632C71.9961 101.444 83.2161 93.684 99.0741 93.037C105.748 92.765 112.186 93.284 118.067 94.55C122.956 95.602 126.155 97.214 127.469 110.15Z"/>
                </svg>
            </a>
        </div>

        <div class="embed-content">
            {!! nl2br(e($thread->content)) !!}
        </div>

        @if($thread->media->isNotEmpty())
            <div class="embed-media">
                <img src="{{ route('media.show', $thread->media->first()->id) }}" alt="Attachment">
            </div>
        @endif

        <div class="embed-footer">
            <div class="embed-stats">
                <span>❤️ {{ $thread->likes_count }}</span>
                <span>💬 {{ $thread->replies_count }}</span>
            </div>
            <a href="{{ route('threads.show', $thread->id) }}" target="_blank" rel="noopener" class="embed-cta">Xem trên Threads</a>
        </div>
    </div>
</body>
</html>

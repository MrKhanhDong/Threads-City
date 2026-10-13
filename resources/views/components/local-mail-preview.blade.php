@php
    $mailPreview = config('mail.local_preview_url');
    $showLocalMail = app()->environment('local') && config('mail.default') === 'smtp'
        && is_string($mailPreview) && parse_url($mailPreview, PHP_URL_SCHEME) === 'http'
        && in_array(parse_url($mailPreview, PHP_URL_HOST), ['127.0.0.1', 'localhost'], true);
@endphp
@if($showLocalMail)
    <p class="alert alert-info">Đang test email local: thư được nhận trong Mailpit, không gửi ra hộp thư Internet.
        <a href="{{ $mailPreview }}" target="_blank" rel="noopener noreferrer">Mở hộp thư thử ↗</a>
    </p>
@endif

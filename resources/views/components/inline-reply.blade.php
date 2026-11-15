@auth
<template id="inline-reply-template">
    <form class="inline-reply-form" data-inline-reply-form action="{{ route('threads.store') }}" data-upload-url="{{ route('threads.upload-media') }}" data-media-limit="{{ config('blog.thread_media_limit') }}" method="post">
        @csrf
        <input type="hidden" name="parent_id"><input type="hidden" name="type" value="reply">
        <div class="inline-reply-heading"><span>Trả lời <strong data-reply-recipient></strong></span><button type="button" data-reply-close aria-label="Đóng ô trả lời">✕</button></div>
        <p class="inline-reply-context" data-reply-context></p>
        <div class="inline-reply-editor"><x-avatar :user="auth()->user()"/><div class="inline-reply-input"><textarea name="content" rows="1" maxlength="2000" placeholder="Viết câu trả lời…" aria-label="Nội dung trả lời" data-reply-input></textarea><div class="inline-reply-tools"><button type="button" data-reply-attach aria-label="Đính kèm ảnh" title="Đính kèm ảnh"><x-journal-icon name="image" :size="19"/></button><span>Ctrl + Enter để gửi</span><button class="inline-reply-submit" type="submit">Trả lời <x-journal-icon name="send" :size="16"/></button></div></div></div>
        <input type="file" accept="image/jpeg,image/png,image/webp,image/gif" multiple data-reply-files hidden>
        <div class="inline-reply-media" data-reply-media></div>
        <p class="inline-reply-status" data-reply-status role="status" aria-live="polite"></p>
    </form>
</template>
@endauth

@auth
<div class="modal fade stream-composer-modal" id="streamComposer" tabindex="-1" aria-labelledby="streamComposerTitle" aria-hidden="true" data-stream-composer data-user-id="{{ auth()->id() }}" data-store-url="{{ route('threads.store') }}" data-upload-url="{{ route('threads.upload-media') }}" data-editor-url="{{ asset('vendor/tinymce/tinymce.min.js') }}" data-editor-license="{{ config('blog.editor_license') }}">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable"><div class="modal-content">
        <div class="modal-header"><button type="button" class="stream-cancel" data-bs-dismiss="modal">Hủy</button><h2 id="streamComposerTitle" class="modal-title">Bài viết mới</h2><span></span></div>
        <div class="modal-body"><form method="post" action="{{ route('threads.store') }}" data-stream-form>
            @csrf
            <input type="hidden" name="content_format" value="html">
            <p class="muted small">Gõ @ và chọn người dùng để nhắc đến, tối đa 5 người ngoài bản thân. Chỉ người có quyền xem bài mới nhận thông báo.</p>
            <div class="stream-compose-context" data-compose-context hidden></div>
            <div class="stream-compose-row"><x-avatar :user="auth()->user()"/><div class="stream-compose-main"><strong>{{ auth()->user()->username ?: auth()->user()->name }}<x-verified-badge :user="auth()->user()"/></strong><label for="streamContent" class="visually-hidden">Nội dung bài viết</label><textarea id="streamContent" name="content" rows="4" placeholder="Có gì mới?" data-compose-text></textarea><span class="stream-counter" data-compose-count>0/500</span><div class="stream-media-previews" data-compose-media></div><div class="stream-compose-toolbar"><button type="button" class="stream-icon-button" data-compose-attach aria-label="Đính kèm ảnh"><x-journal-icon name="image"/></button><span>Tối đa 4 ảnh · 5 MB/ảnh</span><input type="file" data-compose-file accept="image/jpeg,image/png,image/webp" multiple hidden></div></div></div>
            <div data-compose-continuations></div>
            <button type="button" class="stream-add-thread" data-compose-add><x-journal-icon name="plus" :size="16"/>Thêm vào chuỗi</button>
            <p class="stream-compose-error" data-compose-error role="alert" hidden></p>
            <div class="stream-compose-footer"><span>Mọi người đều có thể trả lời</span><button type="submit" class="stream-submit" data-compose-submit disabled>Đăng</button></div>
        </form></div>
    </div></div>
</div>
@endauth

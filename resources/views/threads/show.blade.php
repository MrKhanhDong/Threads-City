@extends('layouts.threads')

@section('content')

    <!-- Top Bar with Back Button -->
    <div class="d-flex align-items-center gap-3 py-3 mb-2 border-bottom border-dark">
        <a href="{{ route('home') }}" class="btn-threads-outline rounded-circle p-2 d-inline-flex align-items-center justify-content-center" style="width: 38px; height: 38px;" title="Quay lại">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="19" y1="12" x2="5" y2="12"></line>
                <polyline points="12 19 5 12 12 5"></polyline>
            </svg>
        </a>
        <h4 class="text-white fs-5 fw-bold mb-0">Thread</h4>
    </div>

    <!-- Parent Thread (if this thread is a reply) -->
    @if($thread->parent)
        <div class="mb-0">
            @include('threads.partials.thread_card', ['thread' => $thread->parent, 'hasReplies' => true, 'userInteractions' => $userInteractions ?? []])
        </div>
    @endif

    <!-- Main Active Thread -->
    <div class="main-thread-focus-area" data-live-region="thread">
        @include('threads.partials.thread_card', ['thread' => $thread, 'isReplyItem' => (bool)$thread->parent, 'userInteractions' => $userInteractions ?? []])
    </div>

    <!-- Quick Reply Box for this Thread -->
    <div id="replyBox" class="reply-composer-section p-3 rounded-4 my-3" style="background: var(--journal-surface); border: 1px solid var(--border-color);">
        @auth
            <form action="{{ route('threads.store') }}" method="post" id="replyForm">
                @csrf
                <input type="hidden" name="parent_id" value="{{ $thread->id }}">
                <input type="hidden" name="type" value="reply">
                <div id="replyMediaIdsContainer"></div>

                <div class="d-flex gap-3 align-items-start">
                    @if(auth()->user()->avatar_path)
                        <img src="{{ route('avatar.show', auth()->user()->id) }}" alt="{{ auth()->user()->name }}" class="user-avatar-round" style="width: 38px; height: 38px; border-radius: 50%; object-fit: cover;">
                    @else
                        <div class="user-avatar-letter" style="width: 38px; height: 38px;">{{ mb_substr(auth()->user()->name, 0, 1) }}</div>
                    @endif
                    <div class="flex-grow-1 position-relative">
                        <div class="text-secondary small mb-1">
                            Trả lời <span class="text-white fw-bold">{{ $thread->author->handle() }}</span>
                        </div>
                        <textarea 
                            name="content" 
                            id="replyContentInput" 
                            class="w-100 bg-transparent border-0 text-white outline-none p-0" 
                            style="font-size: 15px; resize: none; min-height: 60px; outline: none !important;"
                            placeholder="Nhập câu trả lời của bạn..." 
                            maxlength="2000"
                        ></textarea>
                        <div id="replyMentionDropdown" class="mention-autocomplete-dropdown" style="display: none;"></div>
                    </div>
                </div>

                <div id="replyMediaPreview" class="modal-media-grid my-2" style="display: none;"></div>

                <div class="d-flex justify-content-between align-items-center pt-2 mt-2 border-top border-dark">
                    <div class="d-flex align-items-center gap-2">
                        <button type="button" class="btn btn-sm text-secondary p-1 border-0" id="replyAddImageBtn" title="Đính kèm ảnh">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                <rect x="3" y="3" width="18" height="18" rx="3" ry="3"></rect>
                                <circle cx="8.5" cy="8.5" r="1.5"></circle>
                                <polyline points="21 15 16 10 5 21"></polyline>
                            </svg>
                        </button>
                        <input type="file" id="replyFileInput" accept="image/*" multiple hidden>
                        <span class="text-secondary" style="font-size: 12px;" id="replyStatusText">Nhấn Đăng để gửi phản hồi</span>
                    </div>
                    <button type="submit" class="btn-threads-primary px-3 py-1" id="replySubmitBtn">
                        Trả lời
                    </button>
                </div>
            </form>
        @else
            <div class="text-center py-3 cursor-pointer" onclick="window.checkAuth('tham gia trả lời cuộc trò chuyện')">
                <span class="text-secondary small">Vui lòng <a href="javascript:void(0)" onclick="window.checkAuth('tham gia trả lời cuộc trò chuyện')" class="text-white text-decoration-underline fw-semibold">đăng nhập</a> để tham gia trả lời cuộc trò chuyện này.</span>
            </div>
        @endauth
    </div>

    <!-- Replies Chain -->
    <div class="replies-chain-container" data-live-region="replies" data-live-list>
        <h6 class="text-secondary fw-semibold mb-3 px-1">Tất cả câu trả lời ({{ $replies->count() }})</h6>
        @forelse($replies as $reply)
            @include('threads.partials.thread_card', ['thread' => $reply, 'isReplyItem' => true, 'userInteractions' => $userInteractions ?? []])
        @empty
            <div class="text-center py-4 text-secondary small">
                Chưa có câu trả lời nào. Hãy là người đầu tiên trả lời!
            </div>
        @endforelse
    </div>

@endsection

@push('threads-scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const replyInput = document.getElementById('replyContentInput');
    const replyForm = document.getElementById('replyForm');
    const replyAddBtn = document.getElementById('replyAddImageBtn');
    const replyFileInput = document.getElementById('replyFileInput');
    const replyPreview = document.getElementById('replyMediaPreview');
    const replyMediaIds = document.getElementById('replyMediaIdsContainer');
    const replySubmit = document.getElementById('replySubmitBtn');
    const replyStatus = document.getElementById('replyStatusText');
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    const maxMedia = {{ (int) config('blog.thread_media_limit') }};

    let uploadedMedia = [];
    let isUploading = 0;

    function renderReplyPreviews() {
        if (!replyPreview || !replyMediaIds) return;
        replyMediaIds.innerHTML = '';
        replyPreview.innerHTML = '';
        if (uploadedMedia.length === 0) {
            replyPreview.style.display = 'none';
            return;
        }
        replyPreview.style.display = 'grid';
        uploadedMedia.forEach((media, idx) => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'media_ids[]';
            input.value = media.id;
            replyMediaIds.appendChild(input);

            const thumb = document.createElement('div');
            thumb.className = 'modal-media-thumb';
            thumb.innerHTML = `
                <img src="${media.url}" alt="Reply media ${idx+1}">
                <button type="button" class="modal-media-remove" data-idx="${idx}">✕</button>
            `;
            replyPreview.appendChild(thumb);
        });
    }

    replyPreview?.addEventListener('click', function(e) {
        const rm = e.target.closest('.modal-media-remove');
        if (!rm) return;
        e.preventDefault();
        const idx = parseInt(rm.getAttribute('data-idx'));
        if (!isNaN(idx)) {
            uploadedMedia.splice(idx, 1);
            renderReplyPreviews();
        }
    });

    if (replyAddBtn && replyFileInput) {
        replyAddBtn.addEventListener('click', () => replyFileInput.click());
        replyFileInput.addEventListener('change', function() {
            if (!this.files || this.files.length === 0) return;
            const availableSlots = Math.max(0, maxMedia - uploadedMedia.length - isUploading);
            const files = Array.from(this.files);
            this.value = '';
            if (files.length > availableSlots) {
                if (replyStatus) replyStatus.textContent = `Tối đa ${maxMedia} ảnh cho mỗi câu trả lời. Còn ${availableSlots} chỗ đính kèm.`;
                return;
            }

            files.forEach(file => {
                if (!file.type.startsWith('image/')) return;
                isUploading++;
                if (replySubmit) replySubmit.disabled = true;
                if (replyStatus) replyStatus.textContent = 'Đang tải ảnh...';

                const formData = new FormData();
                formData.append('image', file);

                fetch('{{ route("threads.upload-media") }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    },
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    if (data && data.id && data.url) {
                        uploadedMedia.push({ id: data.id, url: data.url });
                    }
                })
                .catch(err => console.error(err))
                .finally(() => {
                    isUploading = Math.max(0, isUploading - 1);
                    if (isUploading === 0) {
                        if (replySubmit) replySubmit.disabled = false;
                        if (replyStatus) replyStatus.textContent = 'Nhấn Đăng để gửi phản hồi';
                    }
                    renderReplyPreviews();
                });
            });
        });
    }

    replyForm?.addEventListener('threads:sent', function() {
        uploadedMedia = [];
        renderReplyPreviews();
        if (replyInput) replyInput.style.height = '60px';
    });

    if (replyInput) {
        replyInput.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = (this.scrollHeight) + 'px';
        });

        if (window.location.hash === '#replyBox') {
            replyInput.focus();
        }

        if (window.setupMentionAutocomplete) {
            window.setupMentionAutocomplete(replyInput, document.getElementById('replyMentionDropdown'));
        }
    }
});
</script>
@endpush

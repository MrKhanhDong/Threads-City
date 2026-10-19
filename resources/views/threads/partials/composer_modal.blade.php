<!-- New Thread / Quote Modal -->
<div class="modal fade" id="newThreadModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 560px;">
        <div class="modal-content" style="background: #181818; border: 1px solid #2e2e2e; border-radius: 20px; color: #fff;">
            <div class="modal-header border-bottom border-dark px-4 py-3">
                <h5 class="modal-title fs-6 fw-bold" id="newThreadModalTitle">Bài viết mới</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body px-4 py-3">
                <form id="modalThreadForm" action="{{ route('threads.store') }}" method="post">
                    @csrf
                    <input type="hidden" name="parent_id" id="modalParentId" value="">
                    <input type="hidden" name="repost_of_id" id="modalRepostOfId" value="">
                    <input type="hidden" name="type" id="modalPostType" value="post">
                    <div id="modalMediaIdsContainer"></div>

                    <!-- Author Info -->
                    <div class="d-flex gap-3 align-items-start mb-2">
                        @if(auth()->user()->avatar_path)
                            <img src="{{ route('avatar.show', auth()->user()->id) }}" alt="{{ auth()->user()->name }}" class="user-avatar-round">
                        @else
                            <div class="user-avatar-letter">{{ mb_substr(auth()->user()->name, 0, 1) }}</div>
                        @endif
                        <div class="flex-grow-1">
                            <div class="fw-semibold text-white fs-6 mb-1">{{ auth()->user()->handle() }}</div>
                            <textarea 
                                name="content" 
                                id="modalComposerText" 
                                class="w-100 bg-transparent border-0 text-white outline-none p-0" 
                                style="font-size: 15px; resize: none; min-height: 90px; outline: none !important;"
                                placeholder="Có gì mới hôm nay?..." 
                                maxlength="2000"
                                required
                            ></textarea>
                        </div>
                    </div>

                    <!-- Quoted thread preview container (if quoting) -->
                    <div id="modalQuotedThreadContainer" class="p-3 mb-3 rounded-3" style="display: none; background: #202020; border: 1px solid #333;"></div>

                    <!-- Media previews -->
                    <div id="modalMediaPreview" class="d-flex gap-2 flex-wrap mb-3" style="display: none;"></div>

                    <!-- Toolbar -->
                    <div class="d-flex justify-content-between align-items-center pt-3 border-top border-dark">
                        <div class="d-flex align-items-center gap-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary text-white border-0 p-1" id="modalAddImageBtn" title="Đính kèm ảnh">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                                    <circle cx="8.5" cy="8.5" r="1.5"></circle>
                                    <polyline points="21 15 16 10 5 21"></polyline>
                                </svg>
                            </button>
                            <input type="file" id="modalComposerFileInput" accept="image/*" hidden>
                            <span id="modalCharCount" class="text-secondary small">0/2000</span>
                        </div>
                        <button type="submit" class="btn-threads-primary px-4" id="modalSubmitBtn">
                            <span>Đăng</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

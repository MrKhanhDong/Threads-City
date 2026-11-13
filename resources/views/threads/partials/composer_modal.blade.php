<!-- New Thread / Quote Modal (Meta Threads Style) -->
<div class="modal fade" id="newThreadModal" data-upload-url="{{ route('threads.upload-media') }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 620px;">
        <div class="modal-content threads-composer-modal-content">

            <!-- Header matching screenshot: Hủy (left) | Bài viết mới (center) | Icons (right) -->
            <div class="threads-modal-header">
                <button type="button" class="composer-header-cancel-btn" data-bs-dismiss="modal">
                    Hủy
                </button>
                <div class="composer-header-title-box">
                    <span class="composer-header-title" id="newThreadModalTitle">Bài viết mới</span>
                </div>
                <div class="composer-header-tools">
                    <button type="button" class="composer-header-icon-btn" title="Bản nháp" onclick="alert('Tính năng bản nháp đang được phát triển')">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                            <polyline points="14 2 14 8 20 8"></polyline>
                            <line x1="16" y1="13" x2="8" y2="13"></line>
                            <line x1="16" y1="17" x2="8" y2="17"></line>
                        </svg>
                    </button>
                    <div class="dropdown d-inline-block">
                        <button type="button" class="composer-header-icon-btn" data-bs-toggle="dropdown" aria-expanded="false" title="Tùy chọn khác">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                <circle cx="12" cy="12" r="10"></circle>
                                <circle cx="8" cy="12" r="1.2" fill="currentColor"></circle>
                                <circle cx="12" cy="12" r="1.2" fill="currentColor"></circle>
                                <circle cx="16" cy="12" r="1.2" fill="currentColor"></circle>
                            </svg>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end threads-dropdown-menu">
                            <li><a class="dropdown-item" href="javascript:void(0)" onclick="document.getElementById('modalComposerText')?.focus();">Tập trung viết</a></li>
                            <li><a class="dropdown-item" href="javascript:void(0)" onclick="if(confirm('Bạn có muốn xóa nội dung đang nhập không?')) { document.getElementById('modalComposerText').value = ''; updateSubmitState(); }">Xóa toàn bộ</a></li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Body Area -->
            @include('threads.partials.gif_picker')
            @include('threads.partials.music_picker')
            <div class="threads-modal-body" id="modalDropZone">
                <form id="modalThreadForm" action="{{ route('threads.store') }}" method="post" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="parent_id" id="modalParentId" value="">
                    <input type="hidden" name="repost_of_id" id="modalRepostOfId" value="">
                    <input type="hidden" name="type" id="modalPostType" value="post">
                    <div id="modalMediaIdsContainer"></div>

                    <!-- Main Flow: Left (Avatar + Thread line) | Right (User, Textarea, Poll, Toolbar, Add Thread) -->
                    <div class="composer-flow-wrapper">

                        <!-- Left: Avatar, Thread Line, Sub-Avatar -->
                        <div class="composer-left-col">
                            <div class="composer-avatar-top">
                                @if(auth()->user()->avatar_path)
                                    <img src="{{ route('avatar.show', auth()->user()->id) }}" alt="{{ auth()->user()->name }}" class="composer-avatar-img">
                                @else
                                    <div class="composer-avatar-placeholder">
                                        <svg width="24" height="24" viewBox="0 0 24 24" fill="#a0a0a0">
                                            <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                                        </svg>
                                    </div>
                                @endif
                            </div>
                            <div class="composer-connecting-line"></div>
                            <div class="composer-avatar-bottom">
                                <div class="composer-avatar-mini-placeholder">
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="#666666">
                                        <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                                    </svg>
                                </div>
                            </div>
                        </div>

                        <!-- Right: Main Content, Poll & Tools -->
                        <div class="composer-right-col">

                            <!-- User header: khanhdongisme > Cộng đồng hoặc chủ đề -->
                            <div class="composer-meta-header">
                                <span class="composer-username">{{ auth()->user()->username ?? auth()->user()->name }}</span>
                                <span class="composer-meta-separator">›</span>
                                <div class="dropdown d-inline-block">
                                    <span class="composer-topic-picker" data-bs-toggle="dropdown" aria-expanded="false" id="composerTopicLabel">
                                        Cộng đồng hoặc chủ đề
                                    </span>
                                    <ul class="dropdown-menu threads-dropdown-menu">
                                        <li><h6 class="dropdown-header text-secondary small">Chọn chủ đề nhanh</h6></li>
                                        <li><a class="dropdown-item topic-item" href="javascript:void(0)" data-tag="congnghe">💻 Công nghệ</a></li>
                                        <li><a class="dropdown-item topic-item" href="javascript:void(0)" data-tag="doisong">🌿 Đời sống</a></li>
                                        <li><a class="dropdown-item topic-item" href="javascript:void(0)" data-tag="hoctap">📚 Học tập</a></li>
                                        <li><a class="dropdown-item topic-item" href="javascript:void(0)" data-tag="thethao">⚽ Thể thao</a></li>
                                        <li><a class="dropdown-item topic-item" href="javascript:void(0)" data-tag="huit">🎓 HUIT</a></li>
                                    </ul>
                                </div>

                                <!-- Attached Location Badge -->
                                <div id="composerLocationBadge" class="composer-location-badge ms-auto" style="display: none;">
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                                        <circle cx="12" cy="10" r="3"></circle>
                                    </svg>
                                    <span id="composerLocationText"></span>
                                    <button type="button" class="composer-location-remove-btn" id="btnRemoveLocation" title="Gỡ vị trí">
                                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                                    </button>
                                    <input type="hidden" name="location" id="modalLocationInput" value="">
                                </div>
                            </div>

                            <!-- Textarea with placeholder "Có gì mới?" -->
                            <div class="composer-textarea-box position-relative">
                                <textarea
                                    name="content"
                                    id="modalComposerText"
                                    class="composer-textarea"
                                    placeholder="Có gì mới?"
                                    maxlength="2000"
                                    rows="1"
                                ></textarea>
                                <div id="modalMentionDropdown" class="mention-autocomplete-dropdown" style="display: none;"></div>
                            </div>

                            <!-- Poll Creation Box (Matches screenshot exactly) -->
                            <div id="composerPollContainer" class="composer-poll-container" style="display: none;">
                                <div class="poll-options-list" id="pollOptionsList">
                                    <div class="poll-option-row">
                                        <input type="text" class="poll-option-input" name="poll_options[]" value="Có" placeholder="Có" maxlength="80">
                                    </div>
                                    <div class="poll-option-row">
                                        <input type="text" class="poll-option-input" name="poll_options[]" value="Không" placeholder="Không" maxlength="80">
                                    </div>
                                </div>

                                <div class="poll-add-option-row" id="pollAddOptionBtn" role="button">
                                    <span class="poll-add-option-text">Thêm lựa chọn khác</span>
                                </div>

                                <div class="poll-meta-row">
                                    <label class="poll-duration-label">Kết thúc sau <select name="poll_duration_hours" id="pollDurationHours" aria-label="Thời hạn bình chọn"><option value="1">1 giờ</option><option value="6">6 giờ</option><option value="24" selected>24 giờ</option><option value="72">3 ngày</option><option value="168">7 ngày</option></select></label>
                                    <button type="button" class="poll-remove-action-btn" id="btnRemovePoll">Gỡ cuộc thăm dò ý kiến</button>
                                </div>
                            </div>

                            <!-- Quoted preview container (when quoting another thread) -->
                            <div id="modalQuotedThreadContainer" class="p-3 mb-2 rounded-3" style="display: none; background: var(--journal-raised); border: 1px solid var(--journal-line);"></div>

                            <!-- Media thumbnails preview grid -->
                            <input type="hidden" id="modalMusicId" name="music_id" value="">
                            <div data-selected-music hidden></div>
                            <div id="modalMediaPreview" class="modal-media-grid mb-2" style="display: none;"></div>

                            <!-- Drag & Drop Overlay indicator -->
                            <div id="modalDropZonePrompt" class="modal-drop-zone-prompt text-center py-2 mb-2 rounded-3" style="display: none;">
                                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#0095f6" stroke-width="2" class="mb-1">
                                    <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                                    <circle cx="8.5" cy="8.5" r="1.5"></circle>
                                    <polyline points="21 15 16 10 5 21"></polyline>
                                </svg>
                                <div class="text-white small fw-semibold">Thả ảnh vào đây để tải lên</div>
                            </div>

                            <!-- Toolbar of icons (Shows 9 icons normally, reduces to 3 when Poll is active) -->
                            <div class="composer-toolbar-strip" id="composerToolbarStrip">
                                <!-- 1. Image / Gallery -->
                                <button type="button" class="composer-tool-icon-btn tool-standard-only" id="modalAddImageBtn" title="Đính kèm ảnh" aria-label="Đính kèm ảnh">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                        <rect x="3" y="3" width="18" height="18" rx="3" ry="3"></rect>
                                        <circle cx="8.5" cy="8.5" r="1.5"></circle>
                                        <polyline points="21 15 16 10 5 21"></polyline>
                                    </svg>
                                </button>
                                <input type="file" id="modalComposerFileInput" accept="image/jpeg,image/png,image/webp,image/gif" multiple hidden>
                                <button type="button" class="composer-tool-icon-btn tool-standard-only" id="modalAddVideoBtn" title="Video ngắn, tối đa 60 giây" aria-label="Đính kèm video ngắn">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="5" width="13" height="14" rx="3"/><path d="m16 10 5-3v10l-5-3Z"/></svg>
                                </button>
                                <input type="file" id="modalComposerVideoInput" accept="video/mp4,video/webm" multiple hidden>

                                <!-- 2. GIF -->
                                <button type="button" class="composer-tool-icon-btn tool-standard-only" id="modalAddGifBtn" title="Đính kèm GIF">
                                    <span class="tool-badge-gif">GIF</span>
                                </button>

                                <!-- 3. Emoji (Always available) -->
                                <div class="dropdown d-inline-block">
                                    <button type="button" class="composer-tool-icon-btn tool-always-visible" id="modalAddEmojiBtn" data-bs-toggle="dropdown" aria-expanded="false" title="Biểu tượng cảm xúc">
                                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                            <circle cx="12" cy="12" r="10"></circle>
                                            <path d="M8 14s1.5 2 4 2 4-2 4-2"></path>
                                            <line x1="9" y1="9" x2="9.01" y2="9" stroke-width="2.5"></line>
                                            <line x1="15" y1="9" x2="15.01" y2="9" stroke-width="2.5"></line>
                                        </svg>
                                    </button>
                                    <div class="dropdown-menu threads-dropdown-menu p-2" style="min-width: 240px;">
                                        <div class="d-flex flex-wrap gap-2 fs-5 justify-content-center" id="quickEmojiPicker">
                                            <span role="button" class="emoji-opt">😀</span>
                                            <span role="button" class="emoji-opt">😂</span>
                                            <span role="button" class="emoji-opt">😍</span>
                                            <span role="button" class="emoji-opt">🔥</span>
                                            <span role="button" class="emoji-opt">👏</span>
                                            <span role="button" class="emoji-opt">❤️</span>
                                            <span role="button" class="emoji-opt">✨</span>
                                            <span role="button" class="emoji-opt">👍</span>
                                            <span role="button" class="emoji-opt">🎉</span>
                                            <span role="button" class="emoji-opt">😎</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- 4. Sticker (Always available) -->
                                <button type="button" class="composer-tool-icon-btn tool-always-visible" id="modalAddStickerBtn" title="Nhãn dán">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                        <path d="M15.5 3H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-10.5L15.5 3z"></path>
                                        <path d="M15 3v6h6"></path>
                                        <circle cx="9.5" cy="12.5" r="1" fill="currentColor"></circle>
                                        <circle cx="14.5" cy="12.5" r="1" fill="currentColor"></circle>
                                        <path d="M9.5 15.5c1 1 2.5 1 3.5 0"></path>
                                    </svg>
                                </button>

                                <!-- 5. Poll / Lines (Triggers poll view) -->
                                <button type="button" class="composer-tool-icon-btn tool-standard-only" id="modalAddPollBtn" title="Thăm dò ý kiến">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                        <line x1="4" y1="6" x2="20" y2="6"></line>
                                        <line x1="4" y1="12" x2="14" y2="12"></line>
                                        <line x1="4" y1="18" x2="18" y2="18"></line>
                                    </svg>
                                </button>

                                <!-- 6. Quote / Speech bubble -->
                                <button type="button" class="composer-tool-icon-btn tool-standard-only" id="modalAddQuoteBtn" title="Trích dẫn">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                                        <circle cx="9" cy="10" r="1" fill="currentColor"></circle>
                                        <circle cx="14" cy="10" r="1" fill="currentColor"></circle>
                                    </svg>
                                </button>

                                <!-- 7. Text Document (Đính kèm văn bản) -->
                                <button type="button" class="composer-tool-icon-btn tool-standard-only" id="modalAddListBtn" title="Đính kèm văn bản">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                        <rect x="3" y="4" width="18" height="16" rx="3"></rect>
                                        <line x1="7" y1="9" x2="17" y2="9"></line>
                                        <line x1="7" y1="12" x2="17" y2="12"></line>
                                        <line x1="7" y1="15" x2="13" y2="15"></line>
                                    </svg>
                                </button>

                                <!-- 8. Location pin (Always available) -->
                                <button type="button" class="composer-tool-icon-btn tool-always-visible" id="modalAddLocationBtn" title="Gắn vị trí">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                        <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                                        <circle cx="12" cy="10" r="3"></circle>
                                    </svg>
                                </button>

                                <!-- 9. Music -->
                                <button type="button" class="composer-tool-icon-btn tool-standard-only" id="modalAddMusicBtn" title="Thêm nhạc" aria-label="Thêm nhạc">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                        <path d="M9 18V5l12-2v13"></path>
                                        <circle cx="6" cy="18" r="3"></circle>
                                        <circle cx="18" cy="16" r="3"></circle>
                                    </svg>
                                </button>
                                <button type="button" class="composer-tool-icon-btn tool-standard-only" id="modalAddAudioBtn" title="Ghi âm giọng nói" aria-label="Ghi âm giọng nói">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                        <rect x="9" y="2" width="6" height="12" rx="3"></rect>
                                        <path d="M5 10v2a7 7 0 0 0 14 0v-2M12 19v3M8 22h8"></path>
                                    </svg>
                                </button>
                            </div>

                            <!-- "Thêm vào thread" line -->
                            <div class="composer-add-thread-row">
                                <span class="composer-add-thread-text" id="btnAddToThread" role="button">
                                    Thêm vào thread
                                </span>
                            </div>

                        </div>
                    </div>

                    <!-- Footer: Lựa chọn về bài viết (left) | Đăng (right) -->
                    <div class="threads-modal-footer">
                        <div class="dropdown d-inline-block">
                            <div class="composer-privacy-control" data-bs-toggle="dropdown" aria-expanded="false" role="button" title="Cài đặt người có thể trả lời">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                    <rect x="3" y="3" width="18" height="18" rx="3"></rect>
                                    <line x1="9" y1="3" x2="9" y2="21"></line>
                                    <line x1="15" y1="3" x2="15" y2="21"></line>
                                    <line x1="6" y1="8" x2="12" y2="8"></line>
                                    <line x1="12" y1="16" x2="18" y2="16"></line>
                                </svg>
                                <span id="composerPrivacyText">Lựa chọn về bài viết</span>
                            </div>
                            <ul class="dropdown-menu threads-dropdown-menu">
                                <li><h6 class="dropdown-header text-secondary small">Ai có thể trả lời và trích dẫn?</h6></li>
                                <li><a class="dropdown-item active privacy-opt" href="javascript:void(0)" data-privacy="anyone">🌐 Bất kỳ ai</a></li>
                                <li><a class="dropdown-item privacy-opt" href="javascript:void(0)" data-privacy="following">👥 Trang cá nhân bạn theo dõi</a></li>
                                <li><a class="dropdown-item privacy-opt" href="javascript:void(0)" data-privacy="mentioned">💬 Chỉ những người được nhắc đến</a></li>
                            </ul>
                        </div>

                        <div class="d-flex align-items-center gap-3">
                            <span id="modalCharCount" class="composer-char-count text-secondary" style="display: none;">0/2000</span>
                            <button type="submit" class="composer-publish-btn" id="modalSubmitBtn" disabled>
                                <span id="modalSubmitText">Đăng</span>
                                <span id="modalSubmitSpinner" class="spinner-border spinner-border-sm ms-2" style="display: none;" role="status"></span>
                            </button>
                        </div>
                    </div>

                </form>
            </div>

        </div>
    </div>
</div>

<style>
/* Meta Threads Modal Container */
.threads-composer-modal-content {
    background: var(--journal-surface) !important;
    border: 1px solid var(--journal-line) !important;
    border-radius: 20px !important;
    color: var(--journal-text) !important;
    box-shadow: var(--journal-shadow) !important;
    overflow: hidden;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
}

/* Modal Header: Hủy (left), Bài viết mới (center), Icons (right) */
.threads-modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 14px 20px;
    border-bottom: 1px solid var(--journal-line);
}
.composer-header-cancel-btn {
    background: transparent;
    border: none;
    color: var(--journal-text);
    font-size: 15px;
    font-weight: 500;
    cursor: pointer;
    padding: 0;
    line-height: 1;
    transition: opacity 0.15s;
}
.composer-header-cancel-btn:hover {
    opacity: 0.7;
}
.composer-header-title-box {
    text-align: center;
    flex: 1;
}
.composer-header-title {
    color: var(--journal-text);
    font-size: 16px;
    font-weight: 700;
    letter-spacing: -0.2px;
}
.composer-header-tools {
    display: flex;
    align-items: center;
    gap: 12px;
}
.composer-header-icon-btn {
    background: transparent;
    border: none;
    color: var(--journal-text);
    padding: 4px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    border-radius: 50%;
    transition: opacity 0.15s, background 0.15s;
}
.composer-header-icon-btn:hover {
    background: color-mix(in srgb, var(--journal-text) 8%, transparent);
}

/* Modal Body */
.threads-modal-body {
    padding: 18px 20px 14px;
}
.composer-flow-wrapper {
    display: flex;
    gap: 14px;
}

/* Left Column: Avatar + Line + Mini Avatar */
.composer-left-col {
    display: flex;
    flex-direction: column;
    align-items: center;
    width: 40px;
    flex-shrink: 0;
}
.composer-avatar-top {
    width: 40px;
    height: 40px;
}
.composer-avatar-img {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    object-fit: cover;
    display: block;
}
.composer-avatar-placeholder {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: var(--journal-raised);
    display: flex;
    align-items: center;
    justify-content: center;
    border: 1px solid var(--journal-line);
}
.composer-connecting-line {
    width: 2px;
    flex: 1;
    min-height: 46px;
    background: var(--journal-line);
    margin: 8px 0;
    border-radius: 1px;
}
.composer-avatar-bottom {
    width: 20px;
    height: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
}
.composer-avatar-mini-placeholder {
    width: 20px;
    height: 20px;
    border-radius: 50%;
    background: var(--journal-raised);
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0.6;
}

/* Right Column: User Meta, Textarea, Poll, Toolbar, Add Thread */
.composer-right-col {
    flex: 1;
    min-width: 0;
}
.composer-meta-header {
    display: flex;
    align-items: center;
    gap: 6px;
    margin-bottom: 6px;
}
.composer-username {
    font-size: 15px;
    font-weight: 700;
    color: var(--journal-text);
    line-height: 1.2;
}
.composer-meta-separator {
    color: var(--journal-faint);
    font-size: 13px;
    font-weight: 400;
}
.composer-topic-picker {
    color: var(--journal-faint);
    font-size: 14px;
    cursor: pointer;
    transition: color 0.15s;
    user-select: none;
}
.composer-topic-picker:hover {
    color: var(--journal-muted);
}
.composer-location-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: rgba(0, 149, 246, 0.12);
    color: var(--journal-link);
    border: 1px solid rgba(0, 149, 246, 0.3);
    border-radius: 20px;
    padding: 3px 10px;
    font-size: 13px;
    font-weight: 500;
}
.composer-location-remove-btn {
    background: transparent;
    border: none;
    color: var(--journal-link);
    cursor: pointer;
    padding: 0;
    line-height: 1;
    display: inline-flex;
    align-items: center;
    opacity: 0.7;
    transition: opacity 0.15s;
}
.composer-location-remove-btn:hover {
    opacity: 1;
}

/* Textarea */
.composer-textarea-box {
    margin-bottom: 8px;
}
.composer-textarea {
    width: 100%;
    background: transparent;
    border: none !important;
    outline: none !important;
    color: var(--journal-text);
    font-size: 15px;
    line-height: 1.5;
    resize: none;
    min-height: 40px;
    padding: 2px 0 6px;
}
.composer-textarea::placeholder {
    color: var(--journal-faint);
    font-weight: 400;
}

/* Poll Creation Component (Matches screenshot exactly) */
.composer-poll-container {
    margin-top: 4px;
    margin-bottom: 12px;
}
.poll-option-row {
    margin-bottom: 10px;
}
.poll-option-input {
    width: 100%;
    background: var(--journal-surface);
    border: 1px solid var(--journal-line);
    border-radius: 14px;
    padding: 13px 18px;
    color: var(--journal-text);
    font-size: 15px;
    font-weight: 500;
    outline: none;
    transition: border-color 0.15s, background 0.15s;
}
.poll-option-input:focus {
    border-color: var(--journal-line);
    background: var(--journal-surface);
}
.poll-option-input::placeholder {
    color: var(--journal-faint);
}
.poll-add-option-row {
    background: var(--journal-surface);
    border: 1px dashed var(--journal-line);
    border-radius: 14px;
    padding: 13px 18px;
    margin-bottom: 12px;
    cursor: pointer;
    transition: border-color 0.15s, background 0.15s;
}
.poll-add-option-row:hover {
    border-color: var(--journal-line);
    background: var(--journal-surface);
}
.poll-add-option-text {
    color: var(--journal-faint);
    font-size: 15px;
    font-weight: 500;
    user-select: none;
    transition: color 0.15s;
}
.poll-add-option-row:hover .poll-add-option-text {
    color: var(--journal-muted);
}
.poll-meta-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 2px 2px;
    margin-bottom: 6px;
}
.poll-duration-label {
    color: var(--journal-faint);
    font-size: 13.5px;
    font-weight: 400;
}
.poll-remove-action-btn {
    background: transparent;
    border: none;
    color: var(--journal-muted);
    font-size: 13.5px;
    font-weight: 500;
    cursor: pointer;
    padding: 0;
    transition: color 0.15s;
}
.poll-remove-action-btn:hover {
    color: var(--journal-text);
}

/* Toolbar Icons Strip */
.composer-toolbar-strip {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-top: 4px;
    margin-bottom: 16px;
}

/* When poll mode is active, hide 6 icons and show only Emoji, Sticker, Location */
.composer-toolbar-strip.poll-mode-active .tool-standard-only {
    display: none !important;
}

.composer-tool-icon-btn {
    background: transparent;
    border: none;
    color: var(--journal-faint);
    padding: 6px;
    border-radius: 8px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    line-height: 1;
    transition: color 0.15s, background 0.15s, transform 0.1s;
}
.composer-tool-icon-btn:hover {
    color: var(--journal-text);
    background: color-mix(in srgb, var(--journal-text) 8%, transparent);
}
.tool-badge-gif {
    font-size: 10px;
    font-weight: 800;
    border: 1.5px solid currentColor;
    border-radius: 4px;
    padding: 1px 3px;
    line-height: 1.1;
    letter-spacing: 0.5px;
    display: inline-block;
}

/* "Thêm vào thread" */
.composer-add-thread-row {
    padding-top: 2px;
}
.composer-add-thread-text {
    color: var(--journal-faint);
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    user-select: none;
    transition: color 0.15s;
}
.composer-add-thread-text:hover {
    color: var(--journal-muted);
}

/* Bottom Footer Bar */
.threads-modal-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-top: 20px;
    padding-top: 10px;
}
.composer-privacy-control {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    color: var(--journal-faint);
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    user-select: none;
    transition: color 0.15s;
}
.composer-privacy-control:hover {
    color: var(--journal-muted);
}
.composer-publish-btn {
    background: var(--journal-raised);
    color: var(--journal-faint);
    border: 1px solid var(--journal-line);
    border-radius: 9999px;
    font-size: 14px;
    font-weight: 700;
    padding: 7px 22px;
    cursor: not-allowed;
    transition: all 0.2s;
    line-height: 1.4;
}
.composer-publish-btn.is-active {
    background: var(--journal-button-bg) !important;
    color: var(--journal-button-text) !important;
    border-color: var(--journal-text) !important;
    cursor: pointer !important;
}
.composer-publish-btn.is-active:hover {
    background: var(--journal-button-hover) !important;
    border-color: var(--journal-text) !important;
}

/* Media previews */
.modal-media-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
    gap: 10px;
    max-height: 220px;
    overflow-y: auto;
    padding: 4px 0;
}
.modal-media-thumb {
    position: relative;
    border-radius: 12px;
    overflow: hidden;
    aspect-ratio: 1;
    border: 1px solid var(--journal-line);
    background: #000000;
}
.modal-media-thumb img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}
.modal-media-thumb video, .modal-media-thumb audio {
    width: 100%;
    height: 100%;
    object-fit: contain;
}
.modal-media-loading img {
    position: absolute;
    inset: 0;
}
.modal-media-status {
    position: absolute;
    right: 0;
    bottom: 0;
    left: 0;
    padding: 8px 4px;
    background: rgba(0, 0, 0, 0.75);
    color: #fff;
    text-align: center;
    font-size: 12px;
}
.modal-media-remove {
    position: absolute;
    top: 5px;
    right: 5px;
    width: 22px;
    height: 22px;
    border-radius: 50%;
    background: rgba(0, 0, 0, 0.75);
    color: #fff;
    border: 1px solid rgba(255, 255, 255, 0.2);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 11px;
    cursor: pointer;
    line-height: 1;
    padding: 0;
    transition: background 0.15s, transform 0.15s;
}
.modal-media-remove:hover {
    background: #ff3040;
    transform: scale(1.1);
}
.modal-media-loading {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    background: var(--journal-surface);
    color: var(--journal-muted);
    font-size: 11px;
    gap: 6px;
    height: 100%;
}
.modal-drop-zone-prompt {
    border: 2px dashed var(--journal-link);
    background: rgba(0, 149, 246, 0.08);
}
.emoji-opt {
    cursor: pointer;
    transition: transform 0.15s;
}
.emoji-opt:hover {
    transform: scale(1.25);
}
.btn-close-quote-preview {
    background: color-mix(in srgb, var(--journal-text) 10%, transparent);
    color: var(--journal-muted);
    border: none;
    border-radius: 50%;
    width: 22px;
    height: 22px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 11px;
    cursor: pointer;
    line-height: 1;
    padding: 0;
    transition: background 0.15s, color 0.15s;
}
.btn-close-quote-preview:hover {
    background: color-mix(in srgb, var(--journal-text) 20%, transparent);
    color: var(--journal-text);
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const modalEl = document.getElementById('newThreadModal');
    if (!modalEl) return;

    const modalForm = document.getElementById('modalThreadForm');
    const modalText = document.getElementById('modalComposerText');
    const modalFileInput = document.getElementById('modalComposerFileInput');
    const modalAddImageBtn = document.getElementById('modalAddImageBtn');
    const modalPreview = document.getElementById('modalMediaPreview');
    const modalMediaIdsContainer = document.getElementById('modalMediaIdsContainer');
    const modalSubmitBtn = document.getElementById('modalSubmitBtn');
    const modalSubmitText = document.getElementById('modalSubmitText');
    const modalSubmitSpinner = document.getElementById('modalSubmitSpinner');
    const modalCharCount = document.getElementById('modalCharCount');
    const modalDropZonePrompt = document.getElementById('modalDropZonePrompt');
    const dropZone = document.getElementById('modalDropZone');
    const topicLabel = document.getElementById('composerTopicLabel');

    // Poll Elements
    const pollContainer = document.getElementById('composerPollContainer');
    const pollOptionsList = document.getElementById('pollOptionsList');
    const pollAddOptionBtn = document.getElementById('pollAddOptionBtn');
    const btnRemovePoll = document.getElementById('btnRemovePoll');
    const modalAddPollBtn = document.getElementById('modalAddPollBtn');
    const toolbarStrip = document.getElementById('composerToolbarStrip');

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    const MAX_MEDIA = {{ (int) config('blog.thread_media_limit') }};
    let uploadedMedia = []; // Array of { id, url }
    let isUploadingCount = 0;
    const pendingUploads = new Map();
    let uploadSequence = 0;

    function clearMedia() {
        uploadedMedia.forEach(media => { if (media.previewUrl) URL.revokeObjectURL(media.previewUrl); });
        uploadedMedia = [];
        pendingUploads.forEach(upload => upload.controller.abort());
        pendingUploads.clear();
        isUploadingCount = 0;
    }

    function toast(msg) {
        const t = document.getElementById('threadsGlobalToast');
        if (t) {
            t.textContent = msg;
            t.classList.add('show');
            setTimeout(() => t.classList.remove('show'), 2500);
        } else {
            console.log(msg);
        }
    }

    // 1. Activate Poll Mode (Matches Screenshot)
    if (modalAddPollBtn) {
        modalAddPollBtn.addEventListener('click', function(e) {
            e.preventDefault();
            if (isUploadingCount || modalEl.dataset.recording) { toast('Chờ tải tệp hoặc dừng ghi âm trước.'); return; }
            // Clear any media if attaching poll
            if (uploadedMedia.length > 0) {
                clearMedia();
                renderPreviews();
                toast('Chuyển sang chế độ thăm dò ý kiến');
            }
            if (pollContainer) {
                pollContainer.style.display = 'block';
            }
            if (toolbarStrip) {
                toolbarStrip.classList.add('poll-mode-active');
            }
            const firstInput = pollOptionsList?.querySelector('.poll-option-input');
            if (firstInput) {
                firstInput.focus();
            }
            updateSubmitState();
        });
    }

    // 2. Remove Poll Mode
    if (btnRemovePoll) {
        btnRemovePoll.addEventListener('click', function(e) {
            e.preventDefault();
            if (pollContainer) {
                pollContainer.style.display = 'none';
            }
            if (toolbarStrip) {
                toolbarStrip.classList.remove('poll-mode-active');
            }
            // Reset poll options back to 2 default inputs
            if (pollOptionsList) {
                pollOptionsList.innerHTML = `
                    <div class="poll-option-row">
                        <input type="text" class="poll-option-input" name="poll_options[]" value="Có" placeholder="Có" maxlength="80">
                    </div>
                    <div class="poll-option-row">
                        <input type="text" class="poll-option-input" name="poll_options[]" value="Không" placeholder="Không" maxlength="80">
                    </div>
                `;
            }
            if (pollAddOptionBtn) {
                pollAddOptionBtn.style.display = 'block';
            }
            bindPollInputListeners();
            updateSubmitState();
        });
    }

    // 3. Add more poll options (up to 4)
    if (pollAddOptionBtn) {
        pollAddOptionBtn.addEventListener('click', function() {
            const inputs = pollOptionsList.querySelectorAll('.poll-option-input');
            const count = inputs.length;
            if (count >= 4) {
                toast('Tối đa 4 lựa chọn cho cuộc thăm dò ý kiến');
                return;
            }

            const newRow = document.createElement('div');
            newRow.className = 'poll-option-row';
            newRow.innerHTML = `<input type="text" class="poll-option-input" name="poll_options[]" placeholder="Lựa chọn ${count + 1}" maxlength="80">`;
            pollOptionsList.appendChild(newRow);

            const newInput = newRow.querySelector('input');
            newInput.focus();
            bindPollInputListeners();

            if (count + 1 >= 4) {
                pollAddOptionBtn.style.display = 'none';
            }
            updateSubmitState();
        });
    }

    function bindPollInputListeners() {
        document.querySelectorAll('.poll-option-input').forEach(input => {
            input.removeEventListener('input', updateSubmitState);
            input.addEventListener('input', updateSubmitState);
        });
    }
    bindPollInputListeners();

    // Interactive topic selector
    document.querySelectorAll('.topic-item').forEach(item => {
        item.addEventListener('click', function(e) {
            e.preventDefault();
            const tag = this.getAttribute('data-tag');
            const labelText = this.textContent.trim();
            if (topicLabel) {
                topicLabel.textContent = labelText;
                topicLabel.style.color = '#ffffff';
            }
            if (modalText) {
                const tagStr = ` #${tag}`;
                if (!modalText.value.includes(tagStr)) {
                    modalText.value = (modalText.value.trim() + tagStr).trim() + ' ';
                }
                modalText.focus();
                updateSubmitState();
            }
        });
    });

    // Quick Emoji Picker click
    document.querySelectorAll('.emoji-opt').forEach(opt => {
        opt.addEventListener('click', function(e) {
            e.preventDefault();
            const emoji = this.textContent;
            if (modalText) {
                const start = modalText.selectionStart || modalText.value.length;
                const end = modalText.selectionEnd || modalText.value.length;
                const val = modalText.value;
                modalText.value = val.substring(0, start) + emoji + val.substring(end);
                modalText.selectionStart = modalText.selectionEnd = start + emoji.length;
                modalText.focus();
                updateSubmitState();
            }
        });
    });

    // "Thêm vào thread" click appends continuation marker
    const btnAddToThread = document.getElementById('btnAddToThread');
    if (btnAddToThread) {
        btnAddToThread.addEventListener('click', function() {
            if (modalText) {
                if (!modalText.value.trim()) {
                    modalText.focus();
                    return;
                }
                modalText.value = modalText.value.trim() + '\n\n---\n';
                modalText.focus();
                modalText.style.height = 'auto';
                modalText.style.height = Math.max(46, modalText.scrollHeight) + 'px';
                updateSubmitState();
                toast('Đã thêm dòng nối tiếp vào thread');
            }
        });
    }

    // Interactive Privacy Picker
    document.querySelectorAll('.privacy-opt').forEach(opt => {
        opt.addEventListener('click', function(e) {
            e.preventDefault();
            document.querySelectorAll('.privacy-opt').forEach(o => o.classList.remove('active'));
            this.classList.add('active');
            const text = this.textContent.trim();
            const privacySpan = document.getElementById('composerPrivacyText');
            if (privacySpan) {
                privacySpan.textContent = text;
            }
        });
    });

    // Interactive Sticker button
    document.getElementById('modalAddStickerBtn')?.addEventListener('click', function() {
        toast('Bộ sưu tập nhãn dán đang chuẩn bị ra mắt!');
    });

    // Interactive Quote button
    document.getElementById('modalAddQuoteBtn')?.addEventListener('click', function() {
        if (modalText) {
            modalText.value = (modalText.value.trim() + '\n> "Trích dẫn ở đây..."\n').trim() + ' ';
            modalText.focus();
            updateSubmitState();
        }
    });

    // Interactive List button
    document.getElementById('modalAddListBtn')?.addEventListener('click', function() {
        if (modalText) {
            modalText.value = (modalText.value.trim() + '\n• Mục 1\n• Mục 2\n• Mục 3').trim() + '\n';
            modalText.focus();
            updateSubmitState();
        }
    });

    // Remove Location button listener
    document.getElementById('btnRemoveLocation')?.addEventListener('click', function(e) {
        e.preventDefault();
        const badge = document.getElementById('composerLocationBadge');
        const input = document.getElementById('modalLocationInput');
        const textSpan = document.getElementById('composerLocationText');
        if (badge) badge.style.display = 'none';
        if (input) input.value = '';
        if (textSpan) textSpan.textContent = '';
        updateSubmitState();
    });

    function updateSubmitState() {
        const textLen = (modalText?.value || '').trim().length;
        const mediaCount = uploadedMedia.length;

        const isPollActive = pollContainer && pollContainer.style.display !== 'none';
        const pollInputs = Array.from(document.querySelectorAll('.poll-option-input'))
            .map(i => i.value.trim())
            .filter(v => v.length > 0);
        const pollValid = isPollActive && pollInputs.length >= 2;
        const attachedDocInput = document.getElementById('modalAttachedDocHtml');
        const hasAttachedDoc = !!(attachedDocInput && attachedDocInput.value && attachedDocInput.value.trim().length > 0);
        const locationVal = (document.getElementById('modalLocationInput')?.value || '').trim();
        const hasLocation = locationVal.length > 0;
        const repostOfId = document.getElementById('modalRepostOfId')?.value;
        const hasRepost = !!(repostOfId && repostOfId.trim().length > 0);

        if (modalCharCount) {
            modalCharCount.textContent = `${textLen}/2000`;
            modalCharCount.style.display = textLen > 1500 ? 'inline-block' : 'none';
        }

        const hasMusic = !!document.getElementById('modalMusicId')?.value;
        const canSubmit = ((textLen > 0 || mediaCount > 0 || pollValid || hasAttachedDoc || hasLocation || hasRepost || hasMusic)) && isUploadingCount === 0 && !modalEl.dataset.recording;
        if (modalSubmitBtn) {
            modalSubmitBtn.disabled = !canSubmit;
            if (canSubmit) {
                modalSubmitBtn.classList.add('is-active');
            } else {
                modalSubmitBtn.classList.remove('is-active');
            }
        }

        if (modalAddImageBtn) {
            modalAddImageBtn.style.opacity = (mediaCount >= MAX_MEDIA || isUploadingCount > 0 || isPollActive) ? '0.4' : '1';
            modalAddImageBtn.disabled = (mediaCount >= MAX_MEDIA || isUploadingCount > 0 || isPollActive);
        }
        const videoButton = document.getElementById('modalAddVideoBtn');
        if (videoButton) videoButton.disabled = mediaCount >= MAX_MEDIA || isUploadingCount > 0 || isPollActive || !!modalEl.dataset.recording;
    }
    window.updateComposerSubmitState = updateSubmitState;
    window.threadMediaComposer = {
        add: media => {
            if (uploadedMedia.length + isUploadingCount >= MAX_MEDIA) throw new Error(`Tối đa ${MAX_MEDIA} tệp đính kèm.`);
            if (media.kind === 'giphy' && uploadedMedia.some(item => item.kind === 'giphy' && item.id === media.id)) throw new Error('GIF này đã có trong bài viết.');
            uploadedMedia.push(media);
            renderPreviews();
        },
        available: () => uploadedMedia.length + isUploadingCount < MAX_MEDIA && !isUploadingCount && pollContainer?.style.display === 'none',
        upload: files => handleFilesUpload(files),
    };

    // Auto-grow textarea
    if (modalText) {
        modalText.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = Math.max(40, this.scrollHeight) + 'px';
            updateSubmitState();
        });

        modalText.addEventListener('paste', function(e) {
            const items = (e.clipboardData || e.originalEvent.clipboardData).items;
            const files = [];
            for (let i = 0; i < items.length; i++) {
                if (items[i].type.indexOf('image') !== -1) {
                    const blob = items[i].getAsFile();
                    if (blob) files.push(blob);
                }
            }
            if (files.length > 0) {
                handleFilesUpload(files);
            }
        });

        if (window.setupMentionAutocomplete) {
            window.setupMentionAutocomplete(modalText, document.getElementById('modalMentionDropdown'));
        }
    }

    // Trigger file chooser
    if (modalAddImageBtn && modalFileInput) {
        modalAddImageBtn.addEventListener('click', function(e) {
            e.preventDefault();
            if (uploadedMedia.length >= MAX_MEDIA) {
                toast(`Tối đa ${MAX_MEDIA} tệp cho mỗi bài viết`);
                return;
            }
            modalFileInput.accept = 'image/jpeg,image/png,image/webp,image/gif';
            modalFileInput.click();
        });

        modalFileInput.addEventListener('change', function() {
            if (this.files && this.files.length > 0) {
                handleFilesUpload(Array.from(this.files));
                this.value = '';
            }
        });
    }

    // Drag and Drop
    document.getElementById('modalAddVideoBtn')?.addEventListener('click', () => document.getElementById('modalComposerVideoInput').click());
    document.getElementById('modalComposerVideoInput')?.addEventListener('change', function() {
        if (this.files.length) handleFilesUpload(Array.from(this.files));
        this.value = '';
    });

    if (dropZone) {
        ['dragenter', 'dragover'].forEach(eventName => {
            dropZone.addEventListener(eventName, function(e) {
                e.preventDefault();
                e.stopPropagation();
                if (modalDropZonePrompt) modalDropZonePrompt.style.display = 'block';
            });
        });

        ['dragleave', 'drop'].forEach(eventName => {
            dropZone.addEventListener(eventName, function(e) {
                e.preventDefault();
                e.stopPropagation();
                if (modalDropZonePrompt) modalDropZonePrompt.style.display = 'none';
            });
        });

        dropZone.addEventListener('drop', function(e) {
            const dt = e.dataTransfer;
            const files = Array.from(dt.files).filter(f => /^(image|video)\//.test(f.type));
            if (files.length > 0) {
                handleFilesUpload(files);
            }
        });
    }

    function renderPreviews() {
        if (!modalPreview || !modalMediaIdsContainer) return;
        modalMediaIdsContainer.innerHTML = '';
        modalPreview.innerHTML = '';

        if (uploadedMedia.length === 0 && isUploadingCount === 0) {
            modalPreview.style.display = 'none';
            updateSubmitState();
            return;
        }

        modalPreview.style.display = 'grid';
        uploadedMedia.forEach((media, idx) => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = media.kind === 'giphy' ? 'giphy_ids[]' : 'media_ids[]';
            input.value = media.id;
            modalMediaIdsContainer.appendChild(input);

            const thumb = document.createElement('div');
            thumb.className = 'modal-media-thumb';
            const image = document.createElement(media.kind === 'video' ? 'video' : media.kind === 'audio' ? 'audio' : 'img');
            image.src = media.previewUrl || media.url;
            image.alt = `Ảnh đính kèm ${idx + 1}`;
            if (media.kind === 'video' || media.kind === 'audio') {
                image.controls = true;
                image.preload = 'metadata';
                image.playsInline = true;
            }
            const remove = document.createElement('button');
            remove.type = 'button';
            remove.className = 'modal-media-remove';
            remove.dataset.idx = idx;
            remove.title = 'Gỡ ảnh';
            remove.textContent = '✕';
            thumb.append(image, remove);
            modalPreview.appendChild(thumb);
        });

        pendingUploads.forEach((upload, id) => {
            const thumb = document.createElement('div');
            thumb.className = 'modal-media-thumb modal-media-loading';
            const image = document.createElement(upload.kind === 'video' ? 'video' : 'img');
            image.src = upload.previewUrl;
            image.alt = 'Ảnh đang tải lên';
            const status = document.createElement('span');
            status.className = 'modal-media-status';
            status.setAttribute('role', 'status');
            status.textContent = upload.message;
            upload.status = status;
            const cancel = document.createElement('button');
            cancel.type = 'button';
            cancel.className = 'modal-media-remove';
            cancel.dataset.uploadId = id;
            cancel.title = 'Hủy tải ảnh';
            cancel.textContent = '✕';
            thumb.append(image, status, cancel);
            modalPreview.appendChild(thumb);
        });

        updateSubmitState();
    }

    modalPreview?.addEventListener('click', function(e) {
        const removeBtn = e.target.closest('.modal-media-remove');
        if (!removeBtn) return;
        e.preventDefault();
        if (removeBtn.dataset.uploadId) {
            pendingUploads.get(Number(removeBtn.dataset.uploadId))?.controller.abort();
            return;
        }
        const idx = parseInt(removeBtn.getAttribute('data-idx'));
        if (!isNaN(idx) && idx >= 0 && idx < uploadedMedia.length) {
            if (uploadedMedia[idx].previewUrl) URL.revokeObjectURL(uploadedMedia[idx].previewUrl);
            uploadedMedia.splice(idx, 1);
            renderPreviews();
        }
    });

    function handleFilesUpload(files) {
        if (pollContainer?.style.display !== 'none' || modalEl.dataset.recording) { toast('Gỡ bình chọn hoặc dừng ghi âm trước khi đính kèm.'); return; }
        const availableSlots = MAX_MEDIA - uploadedMedia.length - isUploadingCount;
        if (availableSlots <= 0) {
            toast(`Bạn chỉ có thể đính kèm tối đa ${MAX_MEDIA} ảnh.`);
            return;
        }

        const filesToUpload = files.slice(0, availableSlots);
        if (files.length > availableSlots) {
            toast(`Đã chọn quá ${MAX_MEDIA} ảnh. Chỉ tải lên ${availableSlots} ảnh đầu.`);
        }

        filesToUpload.forEach(file => {
            const isVideo = /^video\/(mp4|webm)$/.test(file.type);
            if (!file.type.startsWith('image/') && !isVideo) {
                toast('Chọn ảnh, video MP4 hoặc WebM.');
                return;
            }
            if (file.size > (isVideo ? 25 : 5) * 1024 * 1024) {
                toast(`Tệp vượt quá ${isVideo ? 25 : 5} MB.`);
                return;
            }

            const id = ++uploadSequence;
            const upload = { previewUrl: URL.createObjectURL(file), kind: isVideo ? 'video' : 'image', controller: new AbortController(), message: 'Đang chuẩn bị tệp…', saved: false };
            pendingUploads.set(id, upload);
            isUploadingCount = pendingUploads.size;
            renderPreviews();
            const showProgress = message => {
                upload.message = message;
                if (upload.status) upload.status.textContent = message;
            };

            const task = isVideo ? (async () => {
                const duration = await window.inspectThreadClip(file);
                if (upload.controller.signal.aborted) throw new DOMException('Đã hủy tải tệp.', 'AbortError');
                const body = new FormData();
                body.append('file', file);
                body.append('kind', 'video');
                body.append('duration', duration);
                const response = await fetch('{{ route("threads.upload-media") }}', { method: 'POST', headers: { 'X-CSRF-TOKEN': csrfToken, Accept: 'application/json' }, body, signal: upload.controller.signal });
                const data = await response.json();
                if (!response.ok) throw new Error(data.message || 'Tải tệp thất bại.');
                return data;
            })() : window.uploadThreadImage(file, '{{ route("threads.upload-media") }}', csrfToken, {
                signal: upload.controller.signal,
                onProgress: percent => showProgress(percent < 100 ? `Đang tải ${percent}%` : 'Đang lưu ảnh…'),
                onStage: stage => showProgress(({ preparing: 'Đang chuẩn bị ảnh…', uploading: 'Đang tải ảnh…', saving: 'Đang lưu ảnh…' })[stage]),
            });
            task.then(data => {
                if (!pendingUploads.has(id)) return;
                uploadedMedia.push({ ...data, kind: upload.kind, previewUrl: upload.previewUrl });
                upload.saved = true;
            })
            .catch(err => {
                if (err.name !== 'AbortError' && pendingUploads.has(id)) toast(err.message || 'Tải ảnh thất bại');
            })
            .finally(() => {
                if (!upload.saved) URL.revokeObjectURL(upload.previewUrl);
                pendingUploads.delete(id);
                isUploadingCount = pendingUploads.size;
                renderPreviews();
            });
        });
    }

    // Modal Form Submission (AJAX)
    modalForm?.addEventListener('submit', function(e) {
        e.preventDefault();
        if (isUploadingCount || modalEl.dataset.recording) { toast('Chờ tải tệp hoặc hoàn tất ghi âm trước khi đăng.'); return; }
        const content = (modalText?.value || '').trim();
        const isPollActive = pollContainer && pollContainer.style.display !== 'none';
        const pollOptions = isPollActive
            ? Array.from(document.querySelectorAll('.poll-option-input')).map(i => i.value.trim()).filter(v => v.length > 0)
            : [];
        const attachedDocInput = document.getElementById('modalAttachedDocHtml');
        const attachedDocHtml = attachedDocInput ? decodeURIComponent(attachedDocInput.value || '') : '';
        const locationVal = (document.getElementById('modalLocationInput')?.value || '').trim();

        const parentId = document.getElementById('modalParentId')?.value || null;
        const repostOfId = document.getElementById('modalRepostOfId')?.value || null;
        const postType = document.getElementById('modalPostType')?.value || 'post';

        if (!content && uploadedMedia.length === 0 && pollOptions.length === 0 && !attachedDocHtml && !locationVal && !repostOfId && !document.getElementById('modalMusicId')?.value) {
            toast('Vui lòng nhập nội dung, đính kèm ảnh, cuộc thăm dò hoặc vị trí');
            return;
        }

        if (modalSubmitBtn) modalSubmitBtn.disabled = true;
        if (modalSubmitSpinner) modalSubmitSpinner.style.display = 'inline-block';
        if (modalSubmitText) modalSubmitText.textContent = 'Đang đăng...';

        fetch(modalForm.action, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                content: content,
                mention_ids: [...modalForm.querySelectorAll('input[name="mention_ids[]"]')].map(input => Number(input.value)),
                parent_id: parentId,
                repost_of_id: repostOfId,
                type: postType,
                media_ids: uploadedMedia.filter(m => m.kind !== 'giphy').map(m => m.id),
                giphy_ids: uploadedMedia.filter(m => m.kind === 'giphy').map(m => m.id),
                music_id: document.getElementById('modalMusicId')?.value || null,
                ...(isPollActive ? { poll_options: pollOptions, poll_duration_hours: Number(document.getElementById('pollDurationHours').value) } : {}),
                attached_document_html: attachedDocHtml,
                location: locationVal
            })
        })
        .then(res => {
            if (!res.ok) {
                return res.json().then(err => { throw new Error(err.message || 'Không thể đăng bài viết'); });
            }
            return res.json();
        })
        .then(data => {
            if (data && (data.success || data.thread)) {
                toast('Đã đăng bài viết thành công!');
                if (window.bootstrap) {
                    bootstrap.Modal.getInstance(modalEl)?.hide();
                }
                resetModal();
                window.dispatchEvent(new Event('threads:refresh'));
            } else {
                toast(data.message || 'Đăng bài thất bại');
                if (modalSubmitBtn) modalSubmitBtn.disabled = false;
                if (modalSubmitSpinner) modalSubmitSpinner.style.display = 'none';
                if (modalSubmitText) modalSubmitText.textContent = 'Đang đăng...';
            }
        })
        .catch(err => {
            console.error(err);
            toast(err.message || 'Có lỗi xảy ra khi đăng bài');
            if (modalSubmitBtn) modalSubmitBtn.disabled = false;
            if (modalSubmitSpinner) modalSubmitSpinner.style.display = 'none';
            if (modalSubmitText) modalSubmitText.textContent = 'Đăng';
        });
    });

    function resetModal() {
        modalEl.dispatchEvent(new Event('composer:reset'));
        if (modalForm) { modalForm.reset(); modalForm.querySelectorAll('input[name="mention_ids[]"]').forEach(input => input.remove()); }
        clearMedia();
        if (modalMediaIdsContainer) modalMediaIdsContainer.innerHTML = '';
        if (modalPreview) {
            modalPreview.innerHTML = '';
            modalPreview.style.display = 'none';
        }
        if (modalText) {
            modalText.value = '';
            modalText.style.height = '40px';
        }
        if (pollContainer) {
            pollContainer.style.display = 'none';
        }
        if (toolbarStrip) {
            toolbarStrip.classList.remove('poll-mode-active');
        }
        if (pollOptionsList) {
            pollOptionsList.innerHTML = `
                <div class="poll-option-row">
                    <input type="text" class="poll-option-input" name="poll_options[]" value="Có" placeholder="Có" maxlength="80">
                </div>
                <div class="poll-option-row">
                    <input type="text" class="poll-option-input" name="poll_options[]" value="Không" placeholder="Không" maxlength="80">
                </div>
            `;
        }
        if (pollAddOptionBtn) {
            pollAddOptionBtn.style.display = 'block';
        }
        bindPollInputListeners();

        document.getElementById('modalParentId').value = '';
        document.getElementById('modalRepostOfId').value = '';
        document.getElementById('modalPostType').value = 'post';
        document.getElementById('newThreadModalTitle').textContent = 'Bài viết mới';
        if (topicLabel) {
            topicLabel.textContent = 'Cộng đồng hoặc chủ đề';
            topicLabel.style.color = '#777777';
        }
        const quoteContainer = document.getElementById('modalQuotedThreadContainer');
        if (quoteContainer) {
            quoteContainer.style.display = 'none';
            quoteContainer.innerHTML = '';
        }
        const attachedDocCard = document.getElementById('composerAttachedDocCard');
        if (attachedDocCard) {
            attachedDocCard.style.display = 'none';
            attachedDocCard.innerHTML = '';
        }
        const textDocEditor = document.getElementById('textDocEditor');
        if (textDocEditor) {
            textDocEditor.innerHTML = '';
        }
        const locationBadge = document.getElementById('composerLocationBadge');
        if (locationBadge) locationBadge.style.display = 'none';
        const locationInput = document.getElementById('modalLocationInput');
        if (locationInput) locationInput.value = '';
        const locationText = document.getElementById('composerLocationText');
        if (locationText) locationText.textContent = '';

        if (modalSubmitBtn) {
            modalSubmitBtn.disabled = true;
            modalSubmitBtn.classList.remove('is-active');
        }
        if (modalSubmitSpinner) modalSubmitSpinner.style.display = 'none';
        if (modalSubmitText) modalSubmitText.textContent = 'Đăng';
        updateSubmitState();
    }

    modalEl.addEventListener('hidden.bs.modal', function() {
        resetModal();
    });

    window.openThreadComposerWithImagePicker = function() {
        if (window.bootstrap) {
            const modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
            modal.show();
            setTimeout(() => {
                modalFileInput?.click();
            }, 350);
        }
    };
});
</script>

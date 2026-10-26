<!-- New Thread / Quote Modal (Meta Threads Style) -->
<div class="modal fade" id="newThreadModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 620px;">
        <div class="modal-content threads-composer-modal-content">
            
            <!-- Header matching screenshot: Hủy (left) | Thread mới (center) | Icons (right) -->
            <div class="threads-modal-header">
                <button type="button" class="composer-header-cancel-btn" data-bs-dismiss="modal">
                    Hủy
                </button>
                <div class="composer-header-title-box">
                    <span class="composer-header-title" id="newThreadModalTitle">Thread mới</span>
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
            <div class="threads-modal-body" id="modalDropZone">
                <form id="modalThreadForm" action="{{ route('threads.store') }}" method="post" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="parent_id" id="modalParentId" value="">
                    <input type="hidden" name="repost_of_id" id="modalRepostOfId" value="">
                    <input type="hidden" name="type" id="modalPostType" value="post">
                    <div id="modalMediaIdsContainer"></div>

                    <!-- Main Flow: Left (Avatar + Thread line) | Right (User, Textarea, Toolbar, Add Thread) -->
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

                        <!-- Right: Main Content & Tools -->
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

                            <!-- Quoted preview container (when quoting another thread) -->
                            <div id="modalQuotedThreadContainer" class="p-3 mb-2 rounded-3" style="display: none; background: #202020; border: 1px solid #333;"></div>

                            <!-- Media thumbnails preview grid -->
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

                            <!-- Toolbar of 9 icons under textarea (Exactly matches screenshot) -->
                            <div class="composer-toolbar-strip">
                                <!-- 1. Image / Gallery -->
                                <button type="button" class="composer-tool-icon-btn" id="modalAddImageBtn" title="Đính kèm ảnh">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                        <rect x="3" y="3" width="18" height="18" rx="3" ry="3"></rect>
                                        <circle cx="8.5" cy="8.5" r="1.5"></circle>
                                        <polyline points="21 15 16 10 5 21"></polyline>
                                    </svg>
                                </button>
                                <input type="file" id="modalComposerFileInput" accept="image/jpeg,image/png,image/jpg,image/webp,image/gif" multiple hidden>

                                <!-- 2. GIF -->
                                <button type="button" class="composer-tool-icon-btn" id="modalAddGifBtn" title="Đính kèm GIF">
                                    <span class="tool-badge-gif">GIF</span>
                                </button>

                                <!-- 3. Emoji -->
                                <div class="dropdown d-inline-block">
                                    <button type="button" class="composer-tool-icon-btn" data-bs-toggle="dropdown" aria-expanded="false" title="Biểu tượng cảm xúc">
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

                                <!-- 4. Sticker -->
                                <button type="button" class="composer-tool-icon-btn" id="modalAddStickerBtn" title="Nhãn dán">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                        <path d="M15.5 3H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-10.5L15.5 3z"></path>
                                        <path d="M15 3v6h6"></path>
                                        <circle cx="9.5" cy="12.5" r="1" fill="currentColor"></circle>
                                        <circle cx="14.5" cy="12.5" r="1" fill="currentColor"></circle>
                                        <path d="M9.5 15.5c1 1 2.5 1 3.5 0"></path>
                                    </svg>
                                </button>

                                <!-- 5. Poll / Lines -->
                                <button type="button" class="composer-tool-icon-btn" id="modalAddPollBtn" title="Thăm dò ý kiến">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                        <line x1="4" y1="6" x2="20" y2="6"></line>
                                        <line x1="4" y1="12" x2="14" y2="12"></line>
                                        <line x1="4" y1="18" x2="18" y2="18"></line>
                                    </svg>
                                </button>

                                <!-- 6. Quote / Speech bubble -->
                                <button type="button" class="composer-tool-icon-btn" id="modalAddQuoteBtn" title="Trích dẫn">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                                        <circle cx="9" cy="10" r="1" fill="currentColor"></circle>
                                        <circle cx="14" cy="10" r="1" fill="currentColor"></circle>
                                    </svg>
                                </button>

                                <!-- 7. List / Bullets -->
                                <button type="button" class="composer-tool-icon-btn" id="modalAddListBtn" title="Danh sách">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                        <rect x="3" y="4" width="18" height="16" rx="3"></rect>
                                        <line x1="7" y1="9" x2="17" y2="9"></line>
                                        <line x1="7" y1="12" x2="17" y2="12"></line>
                                        <line x1="7" y1="15" x2="13" y2="15"></line>
                                    </svg>
                                </button>

                                <!-- 8. Location pin -->
                                <button type="button" class="composer-tool-icon-btn" id="modalAddLocationBtn" title="Gắn vị trí">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                        <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                                        <circle cx="12" cy="10" r="3"></circle>
                                    </svg>
                                </button>

                                <!-- 9. Audio note -->
                                <button type="button" class="composer-tool-icon-btn" id="modalAddAudioBtn" title="Đính kèm âm thanh">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                        <path d="M9 18V5l12-2v13"></path>
                                        <circle cx="6" cy="18" r="3"></circle>
                                        <circle cx="18" cy="16" r="3"></circle>
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
    background: #181818 !important;
    border: 1px solid rgba(255, 255, 255, 0.12) !important;
    border-radius: 20px !important;
    color: #ffffff !important;
    box-shadow: 0 16px 50px rgba(0, 0, 0, 0.9) !important;
    overflow: hidden;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
}

/* Modal Header: Hủy (left), Thread mới (center), Icons (right) */
.threads-modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 14px 20px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.08);
}
.composer-header-cancel-btn {
    background: transparent;
    border: none;
    color: #ffffff;
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
    color: #ffffff;
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
    color: #ffffff;
    padding: 4px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    border-radius: 50%;
    transition: opacity 0.15s, background 0.15s;
}
.composer-header-icon-btn:hover {
    background: rgba(255, 255, 255, 0.08);
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
    background: #262626;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 1px solid rgba(255, 255, 255, 0.08);
}
.composer-connecting-line {
    width: 2px;
    flex: 1;
    min-height: 46px;
    background: #333333;
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
    background: #242424;
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0.6;
}

/* Right Column: User Meta, Textarea, Toolbar, Add Thread */
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
    color: #ffffff;
    line-height: 1.2;
}
.composer-meta-separator {
    color: #777777;
    font-size: 13px;
    font-weight: 400;
}
.composer-topic-picker {
    color: #777777;
    font-size: 14px;
    cursor: pointer;
    transition: color 0.15s;
    user-select: none;
}
.composer-topic-picker:hover {
    color: #a0a0a0;
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
    color: #ffffff;
    font-size: 15px;
    line-height: 1.5;
    resize: none;
    min-height: 46px;
    padding: 2px 0 6px;
}
.composer-textarea::placeholder {
    color: #666666;
    font-weight: 400;
}

/* Toolbar Icons Strip (9 icons exactly like screenshot) */
.composer-toolbar-strip {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-top: 4px;
    margin-bottom: 16px;
}
.composer-tool-icon-btn {
    background: transparent;
    border: none;
    color: #777777;
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
    color: #ffffff;
    background: rgba(255, 255, 255, 0.08);
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
    color: #555555;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    user-select: none;
    transition: color 0.15s;
}
.composer-add-thread-text:hover {
    color: #888888;
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
    color: #777777;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    user-select: none;
    transition: color 0.15s;
}
.composer-privacy-control:hover {
    color: #a0a0a0;
}
.composer-publish-btn {
    background: #1e1e1e;
    color: #555555;
    border: 1px solid #2a2a2a;
    border-radius: 9999px;
    font-size: 14px;
    font-weight: 700;
    padding: 7px 22px;
    cursor: not-allowed;
    transition: all 0.2s;
    line-height: 1.4;
}
.composer-publish-btn.is-active {
    background: #ffffff !important;
    color: #000000 !important;
    border-color: #ffffff !important;
    cursor: pointer !important;
}
.composer-publish-btn.is-active:hover {
    background: #e6e6e6 !important;
    border-color: #e6e6e6 !important;
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
    border: 1px solid #333333;
    background: #000000;
}
.modal-media-thumb img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}
.modal-media-remove {
    position: absolute;
    top: 5px;
    right: 5px;
    width: 22px;
    height: 22px;
    border-radius: 50%;
    background: rgba(0, 0, 0, 0.75);
    color: #ffffff;
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
    background: #111111;
    color: #888888;
    font-size: 11px;
    gap: 6px;
    height: 100%;
}
.modal-drop-zone-prompt {
    border: 2px dashed #0095f6;
    background: rgba(0, 149, 246, 0.08);
}
.emoji-opt {
    cursor: pointer;
    transition: transform 0.15s;
}
.emoji-opt:hover {
    transform: scale(1.25);
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

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    const MAX_IMAGES = 4;
    let uploadedMedia = []; // Array of { id, url }
    let isUploadingCount = 0;

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
                modalText.style.height = Math.max(60, modalText.scrollHeight) + 'px';
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

    // Interactive GIF button
    document.getElementById('modalAddGifBtn')?.addEventListener('click', function() {
        toast('Tính năng chọn GIF đang chuẩn bị ra mắt!');
    });

    // Interactive Poll button
    document.getElementById('modalAddPollBtn')?.addEventListener('click', function() {
        if (modalText) {
            modalText.value = (modalText.value.trim() + '\n📊 Thăm dò:\n1. Có\n2. Không').trim() + '\n';
            modalText.focus();
            updateSubmitState();
        }
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

    // Interactive Location button
    document.getElementById('modalAddLocationBtn')?.addEventListener('click', function() {
        if (navigator.geolocation) {
            toast('Đang xác định vị trí...');
            navigator.geolocation.getCurrentPosition(() => {
                if (modalText && !modalText.value.includes('📍')) {
                    modalText.value = (modalText.value.trim() + ' 📍 TP. Hồ Chí Minh').trim() + ' ';
                    updateSubmitState();
                    toast('Đã đính kèm vị trí');
                }
            }, () => {
                if (modalText && !modalText.value.includes('📍')) {
                    modalText.value = (modalText.value.trim() + ' 📍 TP. Hồ Chí Minh').trim() + ' ';
                    updateSubmitState();
                }
            });
        }
    });

    // Interactive Audio button
    document.getElementById('modalAddAudioBtn')?.addEventListener('click', function() {
        toast('Tính năng ghi âm giọng nói đang chuẩn bị ra mắt!');
    });

    function updateSubmitState() {
        const textLen = (modalText?.value || '').trim().length;
        const mediaCount = uploadedMedia.length;
        
        if (modalCharCount) {
            modalCharCount.textContent = `${textLen}/2000`;
            modalCharCount.style.display = textLen > 1500 ? 'inline-block' : 'none';
        }

        const canSubmit = (textLen > 0 || mediaCount > 0) && isUploadingCount === 0;
        if (modalSubmitBtn) {
            modalSubmitBtn.disabled = !canSubmit;
            if (canSubmit) {
                modalSubmitBtn.classList.add('is-active');
            } else {
                modalSubmitBtn.classList.remove('is-active');
            }
        }

        if (modalAddImageBtn) {
            modalAddImageBtn.style.opacity = (mediaCount >= MAX_IMAGES || isUploadingCount > 0) ? '0.4' : '1';
            modalAddImageBtn.disabled = (mediaCount >= MAX_IMAGES || isUploadingCount > 0);
        }
    }

    // Auto-grow textarea
    if (modalText) {
        modalText.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = Math.max(46, this.scrollHeight) + 'px';
            updateSubmitState();
        });

        // Clipboard Paste Image support (Ctrl+V)
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

        // Initialize mention autocomplete
        if (window.setupMentionAutocomplete) {
            window.setupMentionAutocomplete(modalText, document.getElementById('modalMentionDropdown'));
        }
    }

    // Trigger file chooser
    if (modalAddImageBtn && modalFileInput) {
        modalAddImageBtn.addEventListener('click', function(e) {
            e.preventDefault();
            if (uploadedMedia.length >= MAX_IMAGES) {
                toast(`Tối đa ${MAX_IMAGES} ảnh cho mỗi bài viết`);
                return;
            }
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
            const files = Array.from(dt.files).filter(f => f.type.startsWith('image/'));
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
            input.name = 'media_ids[]';
            input.value = media.id;
            modalMediaIdsContainer.appendChild(input);

            const thumb = document.createElement('div');
            thumb.className = 'modal-media-thumb';
            thumb.innerHTML = `
                <img src="${media.url}" alt="Attachment ${idx + 1}" loading="lazy">
                <button type="button" class="modal-media-remove" data-idx="${idx}" title="Xóa ảnh">✕</button>
            `;
            modalPreview.appendChild(thumb);
        });

        updateSubmitState();
    }

    modalPreview?.addEventListener('click', function(e) {
        const removeBtn = e.target.closest('.modal-media-remove');
        if (!removeBtn) return;
        e.preventDefault();
        const idx = parseInt(removeBtn.getAttribute('data-idx'));
        if (!isNaN(idx) && idx >= 0 && idx < uploadedMedia.length) {
            uploadedMedia.splice(idx, 1);
            renderPreviews();
        }
    });

    function handleFilesUpload(files) {
        const availableSlots = MAX_IMAGES - uploadedMedia.length - isUploadingCount;
        if (availableSlots <= 0) {
            toast(`Bạn chỉ có thể đính kèm tối đa ${MAX_IMAGES} ảnh.`);
            return;
        }

        const filesToUpload = files.slice(0, availableSlots);
        if (files.length > availableSlots) {
            toast(`Đã chọn quá ${MAX_IMAGES} ảnh. Chỉ tải lên ${availableSlots} ảnh đầu.`);
        }

        filesToUpload.forEach(file => {
            if (!file.type.startsWith('image/')) {
                toast('Chỉ chấp nhận file định dạng hình ảnh.');
                return;
            }
            if (file.size > 5 * 1024 * 1024) {
                toast(`File "${file.name}" vượt quá 5MB.`);
                return;
            }

            isUploadingCount++;
            modalPreview.style.display = 'grid';
            const placeholder = document.createElement('div');
            placeholder.className = 'modal-media-thumb modal-media-loading';
            placeholder.innerHTML = `
                <span class="spinner-border spinner-border-sm text-secondary" role="status"></span>
                <span>Đang tải...</span>
            `;
            modalPreview.appendChild(placeholder);
            updateSubmitState();

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
            .then(res => {
                if (!res.ok) throw new Error('Tải ảnh thất bại');
                return res.json();
            })
            .then(data => {
                if (data && data.id && data.url) {
                    uploadedMedia.push({ id: data.id, url: data.url });
                    toast('Đã tải ảnh lên thành công');
                } else {
                    toast('Không nhận được dữ liệu ảnh tải lên');
                }
            })
            .catch(err => {
                console.error(err);
                toast(err.message || 'Tải ảnh thất bại');
            })
            .finally(() => {
                placeholder.remove();
                isUploadingCount = Math.max(0, isUploadingCount - 1);
                renderPreviews();
            });
        });
    }

    // Modal Form Submission (AJAX)
    modalForm?.addEventListener('submit', function(e) {
        e.preventDefault();
        const content = (modalText?.value || '').trim();
        if (!content && uploadedMedia.length === 0) {
            toast('Vui lòng nhập nội dung hoặc đính kèm ảnh');
            return;
        }

        if (modalSubmitBtn) modalSubmitBtn.disabled = true;
        if (modalSubmitSpinner) modalSubmitSpinner.style.display = 'inline-block';
        if (modalSubmitText) modalSubmitText.textContent = 'Đang đăng...';

        const parentId = document.getElementById('modalParentId')?.value || null;
        const repostOfId = document.getElementById('modalRepostOfId')?.value || null;
        const postType = document.getElementById('modalPostType')?.value || 'post';

        fetch(modalForm.action, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                content: content,
                parent_id: parentId,
                repost_of_id: repostOfId,
                type: postType,
                media_ids: uploadedMedia.map(m => m.id)
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
                setTimeout(() => {
                    location.reload();
                }, 600);
            } else {
                toast(data.message || 'Đăng bài thất bại');
                if (modalSubmitBtn) modalSubmitBtn.disabled = false;
                if (modalSubmitSpinner) modalSubmitSpinner.style.display = 'none';
                if (modalSubmitText) modalSubmitText.textContent = 'Đăng';
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
        if (modalForm) modalForm.reset();
        uploadedMedia = [];
        isUploadingCount = 0;
        if (modalMediaIdsContainer) modalMediaIdsContainer.innerHTML = '';
        if (modalPreview) {
            modalPreview.innerHTML = '';
            modalPreview.style.display = 'none';
        }
        if (modalText) {
            modalText.value = '';
            modalText.style.height = '46px';
        }
        document.getElementById('modalParentId').value = '';
        document.getElementById('modalRepostOfId').value = '';
        document.getElementById('modalPostType').value = 'post';
        document.getElementById('newThreadModalTitle').textContent = 'Thread mới';
        if (topicLabel) {
            topicLabel.textContent = 'Cộng đồng hoặc chủ đề';
            topicLabel.style.color = '#777777';
        }
        const quoteContainer = document.getElementById('modalQuotedThreadContainer');
        if (quoteContainer) {
            quoteContainer.style.display = 'none';
            quoteContainer.innerHTML = '';
        }
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

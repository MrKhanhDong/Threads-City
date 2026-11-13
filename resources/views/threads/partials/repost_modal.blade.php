<!-- Repost or Quote Action Modal -->
<div class="modal fade" id="repostActionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 380px;">
        <div class="modal-content repost-action-modal-content">
            <div class="modal-body p-2">
                <!-- Option 1: Direct Repost / Undo Repost -->
                <button type="button" class="repost-menu-option" id="btnActionDirectRepost">
                    <div class="repost-option-icon-box" id="repostOptionIconBox">
                        <svg class="repost-modal-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M17 1l4 4-4 4"></path>
                            <path d="M3 11V9a4 4 0 0 1 4-4h14"></path>
                            <path d="M7 23l-4-4 4-4"></path>
                            <path d="M21 13v2a4 4 0 0 1-4 4H3"></path>
                        </svg>
                    </div>
                    <div class="text-start flex-grow-1">
                        <div class="fw-bold fs-6" id="repostOptionTitle">Đăng lại</div>
                        <small class="text-secondary" id="repostOptionDesc">Chia sẻ trực tiếp bài này đến trang cá nhân của bạn</small>
                    </div>
                </button>

                <div class="repost-menu-divider"></div>

                <!-- Option 2: Quote Thread -->
                <button type="button" class="repost-menu-option" id="btnActionQuoteThread">
                    <div class="repost-option-icon-box">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                        </svg>
                    </div>
                    <div class="text-start flex-grow-1">
                        <div class="fw-bold fs-6">Trích dẫn</div>
                        <small class="text-secondary">Thêm suy nghĩ của bạn trước khi chia sẻ</small>
                    </div>
                </button>
            </div>
        </div>
    </div>
</div>

<style>
    .repost-action-modal-content {
        background: #181818 !important;
        border: 1px solid #2e2e2e !important;
        border-radius: 22px !important;
        color: #ffffff !important;
        box-shadow: 0 16px 40px rgba(0, 0, 0, 0.75) !important;
        overflow: hidden;
    }
    .repost-menu-option {
        width: 100%;
        display: flex;
        align-items: center;
        gap: 14px;
        padding: 12px 14px;
        background: transparent;
        border: none;
        color: #fff;
        border-radius: 16px;
        cursor: pointer;
        transition: background 0.15s, transform 0.1s;
    }
    .repost-menu-option:hover {
        background: color-mix(in srgb, var(--journal-text) 8%, transparent);
    }
    .repost-menu-option:active {
        transform: scale(0.99);
    }
    .repost-option-icon-box {
        width: 38px;
        height: 38px;
        border-radius: 50%;
        background: var(--journal-raised);
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        transition: background 0.15s;
    }
    .repost-menu-divider {
        height: 1px;
        background: var(--journal-hover);
        margin: 4px 8px;
    }
    .repost-menu-option:active {
        transform: scale(0.99);
    }
    .repost-option-icon-box {
        width: 38px;
        height: 38px;
        border-radius: 50%;
        background: #222222;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        transition: background 0.15s;
    }
    .repost-menu-divider {
        height: 1px;
        background: #282828;
        margin: 4px 8px;
    }
</style>

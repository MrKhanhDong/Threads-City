<!-- Repost or Quote Action Modal -->
<div class="modal fade" id="repostActionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 380px;">
        <div class="modal-content" style="background: #181818; border: 1px solid #2e2e2e; border-radius: 20px; color: #fff;">
            <div class="modal-body p-2">
                <button type="button" class="repost-menu-option" id="btnActionDirectRepost">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M17 1l4 4-4 4"></path>
                        <path d="M3 11V9a4 4 0 0 1 4-4h14"></path>
                        <path d="M7 23l-4-4 4-4"></path>
                        <path d="M21 13v2a4 4 0 0 1-4 4H3"></path>
                    </svg>
                    <div class="text-start">
                        <div class="fw-bold fs-6">Đăng lại</div>
                        <small class="text-secondary">Chia sẻ trực tiếp bài này đến trang cá nhân của bạn</small>
                    </div>
                </button>

                <div class="border-top border-dark my-1"></div>

                <button type="button" class="repost-menu-option" id="btnActionQuoteThread">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                    </svg>
                    <div class="text-start">
                        <div class="fw-bold fs-6">Trích dẫn</div>
                        <small class="text-secondary">Thêm suy nghĩ của bạn trước khi đăng lại</small>
                    </div>
                </button>
            </div>
        </div>
    </div>
</div>

<style>
    .repost-menu-option {
        width: 100%;
        display: flex;
        align-items: center;
        gap: 14px;
        padding: 14px 16px;
        background: transparent;
        border: none;
        color: #fff;
        border-radius: 14px;
        cursor: pointer;
        transition: background 0.15s;
    }
    .repost-menu-option:hover {
        background: rgba(255, 255, 255, 0.08);
    }
</style>

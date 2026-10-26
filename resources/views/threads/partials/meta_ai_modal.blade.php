<!-- Meta AI Modal for Thread Analysis & Inquiries -->
<div class="modal fade" id="metaAiModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 560px;">
        <div class="modal-content meta-ai-modal-content">
            <!-- Modal Header -->
            <div class="meta-ai-header">
                <div class="meta-ai-header-left">
                    <span class="meta-ai-badge-icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                            <g transform="translate(12,12)">
                                <ellipse cx="0" cy="-7.5" rx="1.2" ry="2.2" transform="rotate(0) rotate(18, 0, -7.5)" fill="url(#metaAiGrad)"/>
                                <ellipse cx="0" cy="-7.5" rx="1.2" ry="2.2" transform="rotate(40) rotate(18, 0, -7.5)" fill="url(#metaAiGrad)"/>
                                <ellipse cx="0" cy="-7.5" rx="1.2" ry="2.2" transform="rotate(80) rotate(18, 0, -7.5)" fill="url(#metaAiGrad)"/>
                                <ellipse cx="0" cy="-7.5" rx="1.2" ry="2.2" transform="rotate(120) rotate(18, 0, -7.5)" fill="url(#metaAiGrad)"/>
                                <ellipse cx="0" cy="-7.5" rx="1.2" ry="2.2" transform="rotate(160) rotate(18, 0, -7.5)" fill="url(#metaAiGrad)"/>
                                <ellipse cx="0" cy="-7.5" rx="1.2" ry="2.2" transform="rotate(200) rotate(18, 0, -7.5)" fill="url(#metaAiGrad)"/>
                                <ellipse cx="0" cy="-7.5" rx="1.2" ry="2.2" transform="rotate(240) rotate(18, 0, -7.5)" fill="url(#metaAiGrad)"/>
                                <ellipse cx="0" cy="-7.5" rx="1.2" ry="2.2" transform="rotate(280) rotate(18, 0, -7.5)" fill="url(#metaAiGrad)"/>
                                <ellipse cx="0" cy="-7.5" rx="1.2" ry="2.2" transform="rotate(320) rotate(18, 0, -7.5)" fill="url(#metaAiGrad)"/>
                            </g>
                            <defs>
                                <linearGradient id="metaAiGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                                    <stop offset="0%" stop-color="#0080fb"/>
                                    <stop offset="50%" stop-color="#7f3ffc"/>
                                    <stop offset="100%" stop-color="#f953c6"/>
                                </linearGradient>
                            </defs>
                        </svg>
                    </span>
                    <div>
                        <h5 class="meta-ai-title mb-0">Hỏi Meta AI</h5>
                        <p class="meta-ai-subtitle mb-0">Hỏi đáp thông minh về bài viết này</p>
                    </div>
                </div>
                <button type="button" class="btn-close-meta-ai" data-bs-dismiss="modal" aria-label="Close">✕</button>
            </div>

            <!-- Thread Context Box -->
            <div class="meta-ai-context-box">
                <div class="meta-ai-context-author" id="metaAiThreadAuthor">@nguoidung</div>
                <div class="meta-ai-context-snippet" id="metaAiThreadSnippet">Nội dung bài viết đang được tải...</div>
            </div>

            <!-- Prompt Suggestions Chips -->
            <div class="meta-ai-chips-wrap">
                <button type="button" class="meta-ai-chip-btn" data-query="Tóm tắt ngắn gọn bài viết này">
                    <span>✨ Tóm tắt bài viết</span>
                </button>
                <button type="button" class="meta-ai-chip-btn" data-query="Giải thích góc nhìn và bối cảnh chính">
                    <span>🔍 Giải thích bối cảnh</span>
                </button>
                <button type="button" class="meta-ai-chip-btn" data-query="Dịch nội dung bài viết này sang tiếng Anh">
                    <span>🌐 Dịch sang tiếng Anh</span>
                </button>
            </div>

            <!-- Chat & Response Area -->
            <div class="meta-ai-chat-body" id="metaAiChatBody">
                <div class="meta-ai-welcome-msg">
                    <p class="text-secondary small mb-0">Chào bạn! Tôi có thể tóm tắt, giải thích ngữ cảnh hoặc dịch nội dung bài viết này. Hãy chọn gợi ý bên trên hoặc đặt câu hỏi bên dưới.</p>
                </div>
            </div>

            <!-- Input Bar -->
            <div class="meta-ai-footer">
                <form id="metaAiChatForm" class="meta-ai-input-form" onsubmit="return false;">
                    <input type="text" class="meta-ai-input" id="metaAiInput" placeholder="Hỏi Meta AI về bài viết này..." autocomplete="off">
                    <button type="submit" class="meta-ai-send-btn" id="metaAiSendBtn" title="Gửi câu hỏi">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="22" y1="2" x2="11" y2="13"></line>
                            <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
                        </svg>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
.meta-ai-modal-content {
    background: #141414;
    border: 1px solid rgba(255, 255, 255, 0.12);
    border-radius: 20px;
    box-shadow: 0 24px 60px rgba(0, 0, 0, 0.85);
    overflow: hidden;
}
.meta-ai-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 18px 20px 14px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.07);
}
.meta-ai-header-left {
    display: flex;
    align-items: center;
    gap: 12px;
}
.meta-ai-badge-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 38px;
    height: 38px;
    border-radius: 50%;
    background: rgba(127, 63, 252, 0.12);
    border: 1px solid rgba(127, 63, 252, 0.3);
}
.meta-ai-title {
    font-size: 16px;
    font-weight: 700;
    color: #ffffff;
    letter-spacing: -0.2px;
}
.meta-ai-subtitle {
    font-size: 12.5px;
    color: #8e8e8e;
}
.btn-close-meta-ai {
    background: transparent;
    border: none;
    color: #888888;
    font-size: 18px;
    line-height: 1;
    padding: 6px;
    border-radius: 50%;
    cursor: pointer;
    transition: color 0.15s;
}
.btn-close-meta-ai:hover {
    color: #ffffff;
}
.meta-ai-context-box {
    background: #1c1c1c;
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 12px;
    margin: 14px 20px 10px;
    padding: 10px 14px;
}
.meta-ai-context-author {
    font-size: 12px;
    font-weight: 600;
    color: #0095f6;
    margin-bottom: 4px;
}
.meta-ai-context-snippet {
    font-size: 13.5px;
    color: #d1d5db;
    line-height: 1.45;
    max-height: 60px;
    overflow-y: auto;
    word-break: break-word;
}
.meta-ai-chips-wrap {
    display: flex;
    gap: 8px;
    padding: 6px 20px 12px;
    overflow-x: auto;
    scrollbar-width: none;
}
.meta-ai-chips-wrap::-webkit-scrollbar {
    display: none;
}
.meta-ai-chip-btn {
    background: #202020;
    border: 1px solid rgba(255, 255, 255, 0.1);
    color: #e5e5e5;
    font-size: 12.5px;
    font-weight: 500;
    padding: 6px 12px;
    border-radius: 9999px;
    white-space: nowrap;
    cursor: pointer;
    transition: all 0.15s ease;
}
.meta-ai-chip-btn:hover {
    background: #2a2a2a;
    border-color: rgba(127, 63, 252, 0.5);
    color: #ffffff;
}
.meta-ai-chat-body {
    padding: 10px 20px 16px;
    max-height: 280px;
    min-height: 120px;
    overflow-y: auto;
    display: flex;
    flex-direction: column;
    gap: 12px;
}
.meta-ai-welcome-msg {
    background: rgba(255, 255, 255, 0.03);
    border-radius: 12px;
    padding: 12px 14px;
}
.meta-ai-msg-bubble {
    padding: 10px 14px;
    border-radius: 14px;
    font-size: 14px;
    line-height: 1.5;
    max-width: 85%;
    word-break: break-word;
}
.meta-ai-msg-user {
    align-self: flex-end;
    background: #0080fb;
    color: #ffffff;
    border-bottom-right-radius: 4px;
}
.meta-ai-msg-bot {
    align-self: flex-start;
    background: #222222;
    border: 1px solid rgba(255, 255, 255, 0.08);
    color: #f3f5f7;
    border-bottom-left-radius: 4px;
}
.meta-ai-typing-indicator {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 10px 14px;
    background: #222222;
    border-radius: 14px;
    width: fit-content;
}
.meta-ai-typing-dot {
    width: 6px;
    height: 6px;
    background: #7f3ffc;
    border-radius: 50%;
    animation: metaAiDotPulse 1.2s infinite ease-in-out;
}
.meta-ai-typing-dot:nth-child(2) { animation-delay: 0.2s; }
.meta-ai-typing-dot:nth-child(3) { animation-delay: 0.4s; }
@keyframes metaAiDotPulse {
    0%, 80%, 100% { transform: scale(0.6); opacity: 0.4; }
    40% { transform: scale(1.1); opacity: 1; }
}
.meta-ai-footer {
    padding: 12px 20px 18px;
    border-top: 1px solid rgba(255, 255, 255, 0.07);
}
.meta-ai-input-form {
    display: flex;
    align-items: center;
    gap: 8px;
    background: #1e1e1e;
    border: 1px solid rgba(255, 255, 255, 0.12);
    border-radius: 9999px;
    padding: 4px 6px 4px 16px;
    transition: border-color 0.15s;
}
.meta-ai-input-form:focus-within {
    border-color: #7f3ffc;
}
.meta-ai-input {
    flex: 1;
    background: transparent;
    border: none;
    outline: none;
    color: #ffffff;
    font-size: 14px;
}
.meta-ai-input::placeholder {
    color: #777777;
}
.meta-ai-send-btn {
    width: 34px;
    height: 34px;
    border-radius: 50%;
    background: #7f3ffc;
    border: none;
    color: #ffffff;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: background 0.15s, transform 0.1s;
    flex-shrink: 0;
}
.meta-ai-send-btn:hover {
    background: #9055ff;
}
.meta-ai-send-btn:active {
    transform: scale(0.95);
}
</style>

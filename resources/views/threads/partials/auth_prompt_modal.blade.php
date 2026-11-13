<!-- Auth Prompt Modal: Notifies guests to login when attempting interactive actions -->
<div class="modal fade" id="threadsAuthPromptModal" tabindex="-1" aria-labelledby="threadsAuthPromptTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 420px;">
        <div class="modal-content auth-prompt-modal-content">
            <div class="modal-body text-center p-4">
                <!-- Close Button -->
                <button type="button" class="auth-prompt-close" data-bs-dismiss="modal" aria-label="Close">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </button>

                <!-- Threads Logo -->
                <div class="auth-prompt-logo-wrap mb-3">
                    <svg viewBox="0 0 192 192" width="48" height="48" fill="currentColor">
                        <path d="M141.537 88.9883C140.71 88.5919 139.87 88.2109 139.019 87.8451C137.537 60.5382 122.616 44.905 97.5619 44.745C97.4111 44.744 97.2604 44.744 97.1096 44.744C75.2536 44.744 57.0707 58.751 51.5204 79.882C46.0691 100.638 53.6841 123.332 70.8251 137.416C87.9661 151.5 112.43 155.083 133.024 146.516C148.971 139.878 159.227 124.965 160.827 106.012H144.741C143.342 118.847 136.035 129.351 124.636 134.1C109.117 140.556 90.6811 137.856 77.7656 127.243C64.8501 116.63 59.1121 99.5306 63.2201 83.8906C67.4326 67.8546 81.2336 57.214 97.7331 57.214C97.8471 57.214 97.9611 57.215 98.0751 57.216C113.627 57.315 124.516 67.045 125.753 84.148C117.753 82.522 108.887 81.879 99.5371 82.261C73.1971 83.337 57.4981 97.575 58.3341 117.747C59.1381 137.147 75.8361 149.336 96.0601 148.471C111.455 147.813 124.12 140.174 130.407 127.818C134.825 136.195 142.155 142.365 150.941 145.42C157.65 147.752 164.838 148.33 171.936 147.108L174.524 134.42C169.176 135.337 163.743 134.901 158.673 133.138C150.771 130.392 144.974 123.86 142.753 115.228C148.513 107.035 151.706 97.234 151.706 87.051C151.706 84.821 151.482 82.607 151.042 80.441L141.537 88.9883ZM127.469 110.15C125.132 117.841 117.844 134.871 96.6961 135.773C83.8401 136.321 73.1201 128.528 72.5851 115.632C71.9961 101.444 83.2161 93.684 99.0741 93.037C105.748 92.765 112.186 93.284 118.067 94.55C122.956 95.602 126.155 97.214 127.469 110.15Z"/>
                    </svg>
                </div>

                <!-- Title & Dynamic Description -->
                <h4 id="threadsAuthPromptTitle" class="auth-prompt-title">Đăng nhập để tiếp tục</h4>
                <p id="threadsAuthPromptDesc" class="auth-prompt-desc">
                    Vui lòng đăng nhập để thích, bình luận, chia sẻ và tương tác cùng cộng đồng Threads.
                </p>

                <!-- Actions Buttons -->
                <div class="auth-prompt-actions mt-4">
                    <a href="{{ route('login') }}" id="threadsAuthPromptLoginBtn" class="btn-auth-prompt-primary">
                        Đăng nhập
                    </a>
                    <a href="{{ route('register') }}" class="btn-auth-prompt-secondary">
                        Tạo tài khoản mới
                    </a>
                    <button type="button" class="btn-auth-prompt-dismiss" data-bs-dismiss="modal">
                        Để sau
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.auth-prompt-modal-content {
    background: var(--journal-surface) !important;
    border: 1px solid var(--journal-line) !important;
    border-radius: 24px !important;
    box-shadow: var(--journal-shadow) !important;
    color: var(--journal-text);
    position: relative;
    overflow: hidden;
}
.auth-prompt-close {
    position: absolute;
    top: 16px;
    right: 16px;
    background: transparent;
    border: none;
    color: var(--journal-faint);
    cursor: pointer;
    padding: 6px;
    border-radius: 50%;
    transition: color 0.15s, background 0.15s;
    line-height: 1;
}
.auth-prompt-close:hover {
    color: var(--journal-text);
    background: color-mix(in srgb, var(--journal-text) 10%, transparent);
}
.auth-prompt-logo-wrap {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 68px;
    height: 68px;
    border-radius: 50%;
    background: var(--journal-surface);
    border: 1px solid var(--journal-line);
}
.auth-prompt-title {
    font-size: 20px;
    font-weight: 700;
    color: var(--journal-text);
    margin-bottom: 8px;
    letter-spacing: -0.3px;
}
.auth-prompt-desc {
    font-size: 14px;
    line-height: 1.5;
    color: var(--journal-muted);
    margin-bottom: 0;
    padding: 0 12px;
}
.auth-prompt-actions {
    display: flex;
    flex-direction: column;
    gap: 10px;
}
.btn-auth-prompt-primary {
    display: block;
    width: 100%;
    background: var(--journal-button-bg);
    color: var(--journal-button-text) !important;
    font-weight: 700;
    font-size: 15px;
    padding: 12px 20px;
    border-radius: 9999px;
    text-decoration: none;
    text-align: center;
    transition: background 0.15s, transform 0.1s;
    border: none;
}
.btn-auth-prompt-primary:hover {
    background: var(--journal-button-hover);
    transform: scale(0.99);
}
.btn-auth-prompt-secondary {
    display: block;
    width: 100%;
    background: var(--journal-surface);
    color: var(--journal-text) !important;
    font-weight: 600;
    font-size: 14px;
    padding: 11px 20px;
    border-radius: 9999px;
    text-decoration: none;
    text-align: center;
    border: 1px solid var(--journal-line);
    transition: background 0.15s, border-color 0.15s;
}
.btn-auth-prompt-secondary:hover {
    background: var(--journal-raised);
    border-color: var(--journal-line);
}
.btn-auth-prompt-dismiss {
    background: transparent;
    border: none;
    color: var(--journal-faint);
    font-size: 13px;
    font-weight: 500;
    padding: 6px;
    cursor: pointer;
    transition: color 0.15s;
}
.btn-auth-prompt-dismiss:hover {
    color: var(--journal-text);
}
</style>

<script>
// Global Auth Verification & Notification Helper
window.checkAuth = function(actionDesc = 'thực hiện thao tác này') {
    if (window.THREAD_AUTH_USER_ID) {
        return true;
    }

    const toastMsg = `Vui lòng đăng nhập để ${actionDesc}.`;
    
    // 1. Show notification toast
    if (typeof window.showThreadsToast === 'function') {
        window.showThreadsToast(toastMsg);
    } else {
        const toast = document.getElementById('threadsGlobalToast');
        if (toast) {
            toast.textContent = toastMsg;
            toast.classList.add('show');
            setTimeout(() => toast.classList.remove('show'), 2500);
        }
    }

    // 2. Open Auth Prompt Modal
    const modalEl = document.getElementById('threadsAuthPromptModal');
    const descEl = document.getElementById('threadsAuthPromptDesc');
    const loginBtn = document.getElementById('threadsAuthPromptLoginBtn');

    if (descEl) {
        descEl.textContent = `Vui lòng đăng nhập để ${actionDesc} và khám phá thêm nhiều nội dung thú vị trên Threads.`;
    }

    if (loginBtn) {
        const currentUrl = encodeURIComponent(window.location.href);
        loginBtn.href = `{{ route('login') }}?redirect=${currentUrl}`;
    }

    if (modalEl && window.bootstrap) {
        const modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
        modal.show();
    }

    return false;
};
</script>

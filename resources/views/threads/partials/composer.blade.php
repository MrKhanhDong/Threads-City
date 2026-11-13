@php
    $currentUser = auth()->user();
@endphp

<div class="threads-composer-bar" @auth data-bs-toggle="modal" data-bs-target="#newThreadModal" @else onclick="window.checkAuth('tạo bài viết mới')" @endauth>
    <div class="composer-bar-left">
        @auth
            @if($currentUser->avatar_path)
                <img src="{{ route('avatar.show', $currentUser->id) }}" alt="{{ $currentUser->name }}" class="user-avatar-round" style="width: 36px; height: 36px;">
            @else
                <div class="user-avatar-letter" style="width: 36px; height: 36px; font-size: 14px;">{{ mb_substr($currentUser->name, 0, 1) }}</div>
            @endif
        @else
            <div class="user-avatar-placeholder">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#777" stroke-width="2">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                    <circle cx="12" cy="7" r="4"></circle>
                </svg>
            </div>
        @endauth
        <span class="composer-placeholder-text">Có gì mới?</span>
    </div>
    <div class="composer-bar-right d-flex align-items-center gap-2">
        @auth
            <button type="button" class="btn-composer-icon-pill" onclick="event.stopPropagation(); window.openThreadComposerWithImagePicker ? window.openThreadComposerWithImagePicker() : document.getElementById('newThreadModal')?.click();" title="Đính kèm ảnh">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <rect x="3" y="3" width="18" height="18" rx="3" ry="3"></rect>
                    <circle cx="8.5" cy="8.5" r="1.5"></circle>
                    <polyline points="21 15 16 10 5 21"></polyline>
                </svg>
            </button>
        @else
            <button type="button" class="btn-composer-icon-pill" onclick="event.stopPropagation(); window.checkAuth('đính kèm hình ảnh');" title="Đính kèm ảnh">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <rect x="3" y="3" width="18" height="18" rx="3" ry="3"></rect>
                    <circle cx="8.5" cy="8.5" r="1.5"></circle>
                    <polyline points="21 15 16 10 5 21"></polyline>
                </svg>
            </button>
        @endauth
        <button type="button" class="btn-composer-post-pill" @guest onclick="event.stopPropagation(); window.checkAuth('đăng bài viết mới');" @endguest>
            Đăng
        </button>
    </div>
</div>

<style>
    .threads-composer-bar {
        background: var(--journal-surface);
        border: 1px solid var(--border-color);
        border-radius: 18px;
        padding: 12px 18px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        margin: 14px 20px 16px;
        cursor: pointer;
        transition: border-color 0.2s, background 0.2s;
    }
    .threads-composer-bar:hover {
        border-color: var(--journal-line);
        background: var(--journal-surface);
    }
    .composer-bar-left {
        display: flex;
        align-items: center;
        gap: 14px;
        flex: 1;
        min-width: 0;
    }
    .user-avatar-placeholder {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        background: var(--journal-raised);
        display: flex;
        align-items: center;
        justify-content: center;
        border: 1px solid var(--journal-line);
    }
    .composer-placeholder-text {
        color: var(--text-muted);
        font-size: 14px;
        font-weight: 500;
        user-select: none;
    }
    .btn-composer-post-pill {
        background: var(--journal-surface);
        color: var(--journal-faint);
        border: 1px solid var(--journal-line);
        font-size: 13px;
        font-weight: 700;
        padding: 6px 18px;
        border-radius: 9999px;
        cursor: pointer;
        transition: all 0.15s;
    }
    .threads-composer-bar:hover .btn-composer-post-pill {
        color: var(--journal-muted);
        border-color: var(--journal-line);
    }
    .btn-composer-icon-pill {
        background: transparent;
        color: var(--journal-faint);
        border: none;
        padding: 6px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: color 0.15s, background 0.15s, transform 0.15s;
    }
    .btn-composer-icon-pill:hover {
        color: var(--journal-text);
        background: var(--journal-raised);
        transform: scale(1.08);
    }
    .btn-composer-icon-pill {
        background: transparent;
        color: #777777;
        border: none;
        padding: 6px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: color 0.15s, background 0.15s, transform 0.15s;
    }
    .btn-composer-icon-pill:hover {
        color: #ffffff;
        background: #202020;
        transform: scale(1.08);
    }
</style>

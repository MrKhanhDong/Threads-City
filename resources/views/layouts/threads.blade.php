@php($site = ['site_name' => config('blog.name'), 'site_description' => config('blog.description')])
<!doctype html>
<html lang="vi" data-theme="dark" class="dark">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<link rel="manifest" href="{{ asset('app.webmanifest') }}"><link rel="apple-touch-icon" href="{{ asset('pwa-icon-192.png') }}"><meta name="theme-color" content="#101010"><meta name="apple-mobile-web-app-capable" content="yes">
@auth<meta name="mention-suggestions" content="{{ route('mentions.suggestions') }}">@endauth
<title>{{ $pageTitle ?? $site['site_name'] }}{{ isset($pageTitle) ? ' — '.$site['site_name'] : '' }}</title>
<meta name="description" content="{{ $seo['description'] ?? $site['site_description'] }}">
<meta name="robots" content="{{ ($preview ?? false) || request()->routeIs('auth.*','login','register','saved','liked.*','archives.*','insights.*','activity.*','account.*','password.*','verification.*','notifications.*') || request()->has('q') ? 'noindex,nofollow' : 'index,follow' }}">
<link rel="canonical" href="{{ $seo['canonical'] ?? url()->current() }}">
<meta property="og:type" content="{{ isset($post) ? 'article' : 'website' }}">
<meta property="og:title" content="{{ $seo['og_title'] ?? $pageTitle ?? $site['site_name'] }}">
<meta property="og:description" content="{{ $seo['og_description'] ?? $seo['description'] ?? $site['site_description'] }}">
<meta property="og:url" content="{{ $seo['canonical'] ?? url()->current() }}">
<meta property="og:site_name" content="{{ $site['site_name'] }}">
<meta name="twitter:card" content="{{ !empty($seo['image']) ? 'summary_large_image' : 'summary' }}">
<meta name="twitter:title" content="{{ $seo['og_title'] ?? $pageTitle ?? $site['site_name'] }}">
<meta name="twitter:description" content="{{ $seo['og_description'] ?? $seo['description'] ?? $site['site_description'] }}">
@if(!empty($seo['image']))<meta property="og:image" content="{{ $seo['image'] }}"><meta name="twitter:image" content="{{ $seo['image'] }}">@endif
@vite(['resources/css/app.css','resources/css/journal.css','resources/js/app.js'])
@stack('head')
<meta name="color-scheme" content="dark light">
<script>
(() => {
    try { if (window.frameElement?.hasAttribute('data-desktop-column-frame')) document.documentElement.classList.add('journal-column-frame'); } catch {}
    let preference = 'dark';
    try { const stored = localStorage.getItem('journal-theme'); if (['light', 'dark', 'auto'].includes(stored)) preference = stored; } catch {}
    const theme = preference === 'auto' ? (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light') : preference;
    document.documentElement.dataset.themePreference = preference;
    document.documentElement.dataset.theme = theme;
    document.documentElement.dataset.bsTheme = theme;
})();
</script>
@stack('threads-styles')
</head>
<body @class(['public-body', 'journal-body', 'threads-body', 'journal-search-page' => request()->routeIs('search.index'), 'journal-insights-page' => request()->routeIs('insights.index')]) data-live-enabled="{{ request()->routeIs('home', 'threads.index', 'threads.show', 'profile.show', 'saved', 'notifications.index') ? '1' : '0' }}">
<script>window.THREAD_AUTH_USER_ID = @json(auth()->id());</script>
<a href="#main" class="skip-link">Đến nội dung chính</a>
<div class="journal-shell">
    <aside class="journal-sidebar" aria-label="Thanh bên">
        <div class="journal-sidebar-heading"><a class="journal-brand" href="{{ route('home') }}" aria-label="Threads — Trang chủ"><span class="journal-mark">@</span><span class="journal-brand-copy">{{ $site['site_name'] }}</span></a><x-site-menu/></div>
        <x-sidebar-navigation/>
        @if(!empty($trendingTopics))
        <details class="journal-sidebar-trending"><summary>Chủ đề thịnh hành</summary>
            @foreach($trendingTopics as $topic)
                <a href="{{ route('home', ['q' => data_get($topic, 'tag')]) }}"><strong>{{ data_get($topic, 'title') }}</strong><small>{{ data_get($topic, 'count') }} bài viết</small></a>
            @endforeach
        </details>
        @endif
        @if(!empty($suggestedUsers))
        <details class="journal-sidebar-trending"><summary>Gợi ý theo dõi</summary>
            @foreach($suggestedUsers as $suggestion)
                @php($suggestedId = (int) data_get($suggestion, 'id'))
                @php($suggestedName = data_get($suggestion, 'name'))
                @php($suggestedFollowing = isset($sidebarFollowingIds[$suggestedId]))
                <div class="journal-suggested-person">
                    <a href="{{ route('profile.show', data_get($suggestion, 'username') ?: $suggestedId) }}">{{ $suggestedName }}</a>
                    @auth
                        <button type="button" class="btn-threads-outline btn-ajax-sidebar-follow {{ $suggestedFollowing ? 'following' : '' }}" data-user-id="{{ $suggestedId }}">{{ $suggestedFollowing ? 'Đang theo dõi' : 'Theo dõi' }}</button>
                    @else
                        <a class="btn-threads-outline" href="{{ route('login') }}">Theo dõi</a>
                    @endauth
                </div>
            @endforeach
        </details>
        @endif
    </aside>
    <div class="journal-workspace" data-column-workspace>
    <div class="journal-center" data-column-slot="primary">
        <header class="journal-topbar" data-column-drag="primary" tabindex="-1" aria-describedby="desktop-column-drag-help">
            <button class="journal-menu-button journal-sidebar-toggle" type="button" data-bs-toggle="offcanvas" data-bs-target="#journal-sidebar-drawer" aria-controls="journal-sidebar-drawer" aria-expanded="false" aria-label="Mở thanh bên"><x-journal-icon name="menu" :size="24"/></button>
            <a class="journal-mobile-brand" href="{{ route('home') }}" aria-label="Threads">@</a>
            @hasSection('search-bar')@yield('search-bar')@else<span>{{ $pageTitle ?? 'Dành cho bạn' }}</span>@endif
            <div class="journal-topbar-actions">
                @auth
                @php($unreadNavCount = app(\App\Services\UserNotifications::class)->forUser(auth()->user())->whereNull('read_at')->count())
                <a href="{{ route('notifications.index') }}" class="journal-menu-button journal-notification-link" aria-label="Thông báo"><x-journal-icon name="heart"/><span id="sidebarNotificationBadge" class="sidebar-badge-count" data-live-region="badge" style="{{ $unreadNavCount ? '' : 'display: none;' }}">{{ $unreadNavCount > 99 ? '99+' : $unreadNavCount }}</span></a>
                @endauth
                @auth
                <div class="dropdown journal-header-account"><button type="button" class="journal-account-button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Menu tài khoản"><x-avatar :user="auth()->user()"/></button><ul class="dropdown-menu dropdown-menu-end"><li><a class="dropdown-item" href="{{ route('account.edit') }}">Tài khoản của bạn</a></li><li><a class="dropdown-item" href="{{ route('saved') }}">Bài viết đã lưu</a></li><li><a class="dropdown-item" href="{{ route('safety.index') }}">Quyền riêng tư và ứng dụng</a></li>@if(auth()->user()->isAdmin())<li><a class="dropdown-item" href="{{ route('admin.index') }}">Quản trị</a></li>@endif<li><form method="post" action="{{ route('logout') }}">@csrf<button type="submit" class="dropdown-item">Đăng xuất</button></form></li></ul></div>
                @endauth
            </div>
        </header>
        <main id="main" class="journal-main" tabindex="-1"><x-flash/>@yield('content')</main>
        <footer class="journal-footer"><span>© {{ date('Y') }} {{ $site['site_name'] }}</span><a href="{{ route('contact') }}">Liên hệ</a><a href="{{ route('privacy') }}">Quyền riêng tư</a><a href="{{ route('terms') }}">Điều khoản</a></footer>
        <div class="desktop-column-shield" data-column-shield></div>
    </div>
    <x-desktop-columns/>
    </div>
</div>
<x-sidebar-drawer/>
<nav class="journal-mobile-nav" aria-label="Điều hướng trên điện thoại"><a href="{{ route('home') }}" aria-label="Dành cho bạn"><x-journal-icon name="home"/><span>Bảng tin</span></a><a href="{{ route('search.index') }}" aria-label="Tìm kiếm" @class(['is-active' => request()->routeIs('search.index')])><x-journal-icon name="search"/><span>Tìm kiếm</span></a>@auth<button type="button" data-compose="new" aria-label="Bài viết mới"><x-journal-icon name="plus"/><span>Bài viết mới</span></button>@else<a href="{{ route('login') }}" aria-label="Đăng nhập để đăng bài"><x-journal-icon name="plus"/><span>Bài viết mới</span></a>@endauth<a href="{{ route('notifications.index') }}" aria-label="Hoạt động" @class(['is-active' => request()->routeIs('activity.*', 'notifications.*')])><x-journal-icon name="heart"/><span>Hoạt động</span></a><a href="{{ auth()->check() ? route('profile.show', auth()->user()->username ?: auth()->id()) : route('login') }}" aria-label="Trang cá nhân"><x-journal-icon name="person"/><span>Cá nhân</span></a></nav>
@auth<button type="button" class="stream-floating-compose" data-compose="new" aria-label="Tạo Bài viết mới"><x-journal-icon name="plus" :size="30"/></button>@endauth
<div id="threadsGlobalToast" class="threads-toast" role="status" aria-live="polite">Thông báo</div>
    <!-- Modal Create Thread -->
    @auth
    @include('threads.partials.composer_modal')
    @include('threads.partials.quote_picker_modal')
    @include('threads.partials.text_document_modal')
    @include('threads.partials.location_picker_modal')
    @endauth

    <!-- Repost / Quote Modal -->
    @auth
    @include('threads.partials.repost_modal')
    @endauth

    @include('threads.partials.meta_ai_modal')
    @include('threads.partials.report_modal')
    @include('threads.partials.auth_prompt_modal')

    @include('threads.partials.page_actions')
    @include('threads.partials.poll_actions')
    @stack('threads-scripts')


<x-mobile-dialogs/>
<div hidden data-pwa-config data-worker-url="{{ asset('sw.js') }}" data-user-id="{{ auth()->id() }}" data-public-key="{{ app(\App\Services\PushSubscriptions::class)->configured() ? config('services.webpush.public_key') : '' }}" data-push-url="{{ route('push.store') }}" data-logout-url="{{ route('logout') }}"></div>
@stack('scripts')
    <script>
    // Global sidebar follow button handler
    document.addEventListener('DOMContentLoaded', function() {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        document.querySelectorAll('.btn-ajax-sidebar-follow').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                const userId = this.getAttribute('data-user-id');
                fetch(`/users/${userId}/follow`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    }
                })
                .then(res => {
                    if (res.status === 401) {
                        if (typeof window.checkAuth === 'function') window.checkAuth('theo dõi người dùng này');
                        return;
                    }
                    return res.json();
                })
                .then(data => {
                    if (data && data.success) {
                        if (data.following || data.requested) {
                            btn.textContent = data.follow_label || 'Đang theo dõi';
                            btn.classList.add('following');
                        } else {
                            btn.textContent = 'Theo dõi';
                            btn.classList.remove('following');
                        }
                    }
                })
                .catch(err => console.error(err));
            });
        });

        // Avatar follow plus badge handler
        document.addEventListener('click', function(e) {
            const avatarFollowBtn = e.target.closest('.btn-ajax-avatar-follow');
            if (!avatarFollowBtn) return;
            e.preventDefault();
            e.stopPropagation();

            if (typeof window.checkAuth === 'function' && !window.checkAuth('theo dõi tác giả này')) {
                return;
            }

            const userId = avatarFollowBtn.getAttribute('data-user-id');
            fetch(`/users/${userId}/follow`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                }
            })
            .then(res => {
                if (res.status === 401) {
                    if (typeof window.checkAuth === 'function') window.checkAuth('theo dõi tác giả này');
                    return;
                }
                return res.json();
            })
            .then(data => {
                if (data && data.success && (data.following || data.requested)) {
                    avatarFollowBtn.remove();
                }
            })
            .catch(err => console.error(err));
        });

        // Real-time notification unread badge polling
        @auth
        function pollUnreadNotifications() {
            fetch('{{ route("notifications.unread") }}', {
                headers: { 'Accept': 'application/json' }
            })
            .then(res => res.json())
            .then(data => {
                if (data && typeof data.unread !== 'undefined') {
                    const badge = document.getElementById('sidebarNotificationBadge');
                    if (badge) {
                        if (data.unread > 0) {
                            badge.textContent = data.unread > 99 ? '99+' : data.unread;
                            badge.style.display = 'inline-flex';
                        } else {
                            badge.style.display = 'none';
                        }
                    }
                }
            })
            .catch(() => {});
        }
        @unless(request()->routeIs('home', 'threads.index', 'threads.show', 'profile.show', 'saved', 'notifications.index'))
        setInterval(pollUnreadNotifications, 30000);
        @endunless
        @endauth
    });

    // Global Mention Autocomplete Helper
    window.setupMentionAutocomplete = function(textarea, dropdown) {
        if (!textarea || !dropdown) return;

        let activeIndex = -1;
        let suggestions = [];
        let queryTerm = '';
        let atIndex = -1;
        let mentionRevision = 0;
        textarea.form?.addEventListener('reset', () => { textarea.form.querySelectorAll('input[name="mention_ids[]"]').forEach(input => input.remove()); textarea.dataset.mentionIds = '[]'; });

        function closeDropdown() {
            mentionRevision++;
            dropdown.style.display = 'none';
            dropdown.innerHTML = '';
            suggestions = [];
            activeIndex = -1;
        }

        textarea.addEventListener('input', function() {
            const val = textarea.value;
            const cursorPos = textarea.selectionStart;
            const textBeforeCursor = val.slice(0, cursorPos);

            const match = textBeforeCursor.match(/(?<![\\p{L}\\p{N}_@-])@([\\p{L}\\p{N}_-]*)$/u);
            if (!match) {
                closeDropdown();
                return;
            }

            atIndex = cursorPos - match[0].length;
            queryTerm = match[1];

            const requestRevision = ++mentionRevision;
            fetch(`/api/users/mention-suggestions?q=${encodeURIComponent(queryTerm)}`, {
                headers: { 'Accept': 'application/json' }
            })
            .then(res => res.json())
            .then(users => {
                if (requestRevision !== mentionRevision) return;
                if (!users || users.length === 0) {
                    closeDropdown();
                    return;
                }
                suggestions = users;
                renderDropdown();
            })
            .catch(() => closeDropdown());
        });

        function renderDropdown() {
            dropdown.innerHTML = '';
            activeIndex = 0;
            suggestions.forEach((u, i) => {
                const item = document.createElement('div');
                item.className = 'mention-autocomplete-item' + (i === 0 ? ' selected' : '');
                
                const safe = value => { const span = document.createElement('span'); span.textContent = value || ''; return span.innerHTML; };
                const avatarHtml = u.avatar_url 
                    ? `<img src="${u.avatar_url}" class="mention-item-avatar">`
                    : `<div class="mention-item-letter">${safe((u.name || 'U').charAt(0).toUpperCase())}</div>`;
                
                item.innerHTML = `
                    ${avatarHtml}
                    <div class="d-flex flex-column overflow-hidden">
                        <span class="mention-item-name text-truncate">${safe(u.name)}</span>
                        <span class="mention-item-handle text-truncate">${safe(u.handle)}</span>
                    </div>
                `;
                item.addEventListener('mousedown', function(e) {
                    e.preventDefault();
                    insertMention(u);
                });
                dropdown.appendChild(item);
            });
            dropdown.style.display = 'block';
        }

        function insertMention(user) {
            const username = user.username;
            textarea.dataset.mentionIds = JSON.stringify([...new Set([...JSON.parse(textarea.dataset.mentionIds || '[]'), user.id])]);
            if (textarea.form) {
                let selected = [...textarea.form.querySelectorAll('input[name="mention_ids[]"]')].find(input => input.value === String(user.id));
                if (!selected) { selected = document.createElement('input'); selected.type = 'hidden'; selected.name = 'mention_ids[]'; selected.value = String(user.id); textarea.form.append(selected); }
            }
            const val = textarea.value;
            const cursorPos = textarea.selectionStart;
            const before = val.slice(0, atIndex);
            const after = val.slice(cursorPos);
            const insertText = `@${username} `;
            textarea.value = before + insertText + after;
            const newCursorPos = before.length + insertText.length;
            textarea.setSelectionRange(newCursorPos, newCursorPos);
            textarea.focus();
            closeDropdown();
        }

        textarea.addEventListener('keydown', function(e) {
            if (dropdown.style.display === 'none') return;

            const items = dropdown.querySelectorAll('.mention-autocomplete-item');
            if (e.key === 'ArrowDown') {
                e.preventDefault();
                activeIndex = (activeIndex + 1) % items.length;
                updateSelection(items);
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                activeIndex = (activeIndex - 1 + items.length) % items.length;
                updateSelection(items);
            } else if (e.key === 'Enter' || e.key === 'Tab') {
                if (activeIndex >= 0 && suggestions[activeIndex]) {
                    e.preventDefault();
                    insertMention(suggestions[activeIndex]);
                }
            } else if (e.key === 'Escape') {
                closeDropdown();
            }
        });

        function updateSelection(items) {
            items.forEach((item, idx) => {
                if (idx === activeIndex) {
                    item.classList.add('selected');
                    item.scrollIntoView({ block: 'nearest' });
                } else {
                    item.classList.remove('selected');
                }
            });
        }

        document.addEventListener('click', function(e) {
            if (!dropdown.contains(e.target) && e.target !== textarea) {
                closeDropdown();
            }
        });
    };
    </script>

</body>
</html>

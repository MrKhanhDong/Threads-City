<!doctype html>
<html lang="vi" data-theme="dark">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<link rel="manifest" href="{{ asset('app.webmanifest') }}">
<link rel="apple-touch-icon" href="{{ asset('pwa-icon-192.png') }}">
<meta name="theme-color" content="#101010">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-title" content="Threads">
<meta name="csrf-token" content="{{ csrf_token() }}">
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
<body @class(['public-body', 'journal-body', 'journal-search-page' => request()->routeIs('search.index'), 'journal-insights-page' => request()->routeIs('insights.index')])>
<a href="#main" class="skip-link">Đến nội dung chính</a>
<div class="journal-shell">
    <aside class="journal-sidebar" aria-label="Thanh bên">
        <div class="journal-sidebar-heading"><a class="journal-brand" href="{{ route('home') }}" aria-label="Threads — Trang chủ"><span class="journal-mark">@</span><span class="journal-brand-copy">{{ $site['site_name'] }}</span></a><x-site-menu/></div>
        <x-sidebar-navigation/>
    </aside>
    <div class="journal-workspace" data-column-workspace>
    <div class="journal-center" data-column-slot="primary">
        <header class="journal-topbar">
            <button class="journal-menu-button desktop-column-grip" type="button" draggable="true" data-column-drag="primary" aria-label="Đổi vị trí hai cột" title="Kéo để đổi vị trí cột"><x-journal-icon name="grip"/></button>
            <button class="journal-menu-button journal-sidebar-toggle" type="button" data-bs-toggle="offcanvas" data-bs-target="#journal-sidebar-drawer" aria-controls="journal-sidebar-drawer" aria-expanded="false" aria-label="Mở thanh bên"><x-journal-icon name="menu" :size="24"/></button>
            <a class="journal-mobile-brand" href="{{ route('home') }}" aria-label="Threads">@</a>
            @hasSection('search-bar')@yield('search-bar')@else<span>{{ $pageTitle ?? 'Dành cho bạn' }}</span>@endif
            <div class="journal-topbar-actions">
                @auth<x-notification-bell/>@endauth
                @auth
                <div class="dropdown journal-mobile-account"><button type="button" class="journal-account-button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Menu tài khoản"><x-avatar :user="auth()->user()"/></button><ul class="dropdown-menu dropdown-menu-end"><li><a class="dropdown-item" href="{{ route('account.edit') }}">Tài khoản của bạn</a></li><li><a class="dropdown-item" href="{{ route('saved') }}">Bài viết đã lưu</a></li>@if(auth()->user()->isAdmin())<li><a class="dropdown-item" href="{{ route('admin.index') }}">Quản trị blog</a></li>@endif<li><form method="post" action="{{ route('logout') }}">@csrf<button type="submit" class="dropdown-item">Đăng xuất</button></form></li></ul></div>
                @endauth
            </div>
        </header>
        <main id="main" class="journal-main" tabindex="-1">@if(!request()->routeIs('home', 'threads.index', 'blog.legacy_home'))<x-breadcrumbs :items="$breadcrumbs ?? [['label' => $pageTitle ?? 'Trang hiện tại']]"/>@endif<x-flash/>@yield('content')</main>
        <footer class="journal-footer"><span>© {{ date('Y') }} {{ $site['site_name'] }}</span><a href="{{ route('contact') }}">Liên hệ</a><a href="{{ route('privacy') }}">Quyền riêng tư</a><a href="{{ route('terms') }}">Điều khoản</a></footer>
        <div class="desktop-column-shield" data-column-shield></div>
    </div>
    <x-desktop-columns/>
    </div>
</div>
<x-sidebar-drawer/>
<nav class="journal-mobile-nav" aria-label="Điều hướng trên điện thoại"><a href="{{ route('home') }}" aria-label="Dành cho bạn"><x-journal-icon name="home"/><span>Bảng tin</span></a><a href="{{ route('search.index') }}" aria-label="Tìm kiếm" @class(['is-active' => request()->routeIs('search.index')])><x-journal-icon name="search"/><span>Tìm kiếm</span></a>@auth<button type="button" data-compose="new" aria-label="Bài viết mới"><x-journal-icon name="plus"/><span>Bài viết mới</span></button>@else<a href="{{ route('login') }}" aria-label="Đăng nhập để đăng bài"><x-journal-icon name="plus"/><span>Bài viết mới</span></a>@endauth<a href="{{ route('activity.index') }}" aria-label="Hoạt động" @class(['is-active' => request()->routeIs('activity.*', 'notifications.*')])><x-journal-icon name="heart"/><span>Hoạt động</span></a><a href="{{ auth()->check() ? route('profile.show', auth()->user()->username ?: auth()->id()) : route('login') }}" aria-label="Trang cá nhân"><x-journal-icon name="person"/><span>Cá nhân</span></a></nav>
@auth<button type="button" class="stream-floating-compose" data-compose="new" aria-label="Tạo Bài viết mới"><x-journal-icon name="plus" :size="30"/></button>@endauth
@auth @include('threads.partials.composer_modal') @endauth
<x-mobile-dialogs/>
<div hidden data-pwa-config data-worker-url="{{ asset('sw.js') }}" data-user-id="{{ auth()->id() }}" data-public-key="{{ app(\App\Services\PushSubscriptions::class)->configured() ? config('services.webpush.public_key') : '' }}" data-push-url="{{ route('push.store') }}" data-logout-url="{{ route('logout') }}"></div>
<div class="stream-toast" data-stream-toast role="status" aria-live="polite" hidden></div>
@stack('scripts')
@stack('threads-scripts')
</body>
</html>

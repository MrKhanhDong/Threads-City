<!doctype html>
<html lang="vi" data-theme="dark" data-bs-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex,nofollow">
    <meta name="theme-color" content="#10131c">
    <title>{{ $pageTitle ?? 'Quản trị' }} — {{ config('blog.name') }}</title>
    <script>
        (() => {
            let preference = 'dark';
            try { preference = localStorage.getItem('journal-theme') || 'dark'; } catch {}
            const theme = preference === 'auto' ? (matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light') : preference;
            document.documentElement.dataset.themePreference = preference;
            document.documentElement.dataset.theme = theme;
            document.documentElement.dataset.bsTheme = theme;
        })();
    </script>
    @vite(['resources/css/admin.css','resources/js/app.js'])
    @stack('head')
</head>
<body class="admin-body">
<a class="skip-link" href="#main">Đến nội dung chính</a>
<aside id="admin-navigation" class="admin-sidebar offcanvas-lg offcanvas-start" tabindex="-1" aria-labelledby="admin-brand" data-admin-navigation>
    <div class="admin-sidebar-heading">
        <a class="brand" href="{{ route('admin.index') }}"><span class="brand-mark" aria-hidden="true">@</span><span id="admin-brand">{{ config('blog.name') }}<small>Không gian quản trị</small></span></a>
        <button class="admin-icon-button admin-menu-close" type="button" data-bs-dismiss="offcanvas" data-bs-target="#admin-navigation" aria-label="Đóng menu"><x-journal-icon name="close" :size="20"/></button>
    </div>
    <nav class="admin-navigation-groups" aria-label="Quản trị">
        @foreach([
            ['Không gian làm việc', [['admin.index','Tổng quan','chart']]],
            ['Nội dung', [['admin.community.index','Bài đăng','book'], ['admin.replies.index','Trả lời','message']]],
            ['Cộng đồng', [['admin.moderation.index','Báo cáo vi phạm','info'], ['admin.users.index','Người dùng','people']]],
            ['Hệ thống', [['admin.history.index','Lịch sử xử lý','repost'], ['admin.settings.edit','Cài đặt website','settings']]],
        ] as [$groupLabel, $items])
            <div class="admin-nav-group">
                <p class="nav-caption">{{ $groupLabel }}</p>
                @foreach($items as [$route, $label, $icon])
                    @php($isActive = $route === 'admin.index' ? request()->routeIs($route) : request()->routeIs(\Illuminate\Support\Str::beforeLast($route, '.').'.*'))
                    <a @class(['active' => $isActive]) href="{{ route($route) }}" @if($isActive) aria-current="page" @endif><x-journal-icon :name="$icon" :size="20"/><span>{{ $label }}</span>@if($isActive)<span class="admin-nav-indicator" aria-hidden="true"></span>@endif</a>
                @endforeach
            </div>
        @endforeach
    </nav>
    <div class="sidebar-bottom">
        <a href="{{ route('home') }}"><x-journal-icon name="arrow-left" :size="19"/>Về trang chính<x-journal-icon name="arrow" :size="15"/></a>
        <form method="post" action="{{ route('logout') }}">@csrf<button type="submit"><x-journal-icon name="arrow" :size="19"/>Đăng xuất</button></form>
        <small>Threads City · Quản trị cộng đồng</small>
    </div>
</aside>
<div class="admin-content">
    <header class="admin-topbar">
        <div class="admin-topbar-title">
            <button type="button" class="admin-icon-button admin-menu-toggle" data-admin-menu-toggle data-bs-toggle="offcanvas" data-bs-target="#admin-navigation" aria-controls="admin-navigation" aria-expanded="false" aria-label="Mở menu quản trị"><x-journal-icon name="menu" :size="21"/></button>
            <span class="admin-breadcrumb">Quản trị <span aria-hidden="true">/</span></span><strong>{{ $pageTitle ?? 'Quản trị' }}</strong>
        </div>
        <div class="header-actions">
            <a class="admin-public-link" href="{{ route('home') }}"><span>Xem website</span><x-journal-icon name="arrow" :size="17"/></a>
            <button type="button" class="admin-icon-button" data-theme-toggle aria-label="Đổi giao diện sáng tối" title="Đổi giao diện sáng tối"><x-journal-icon name="sun" :size="20"/></button>
            <x-notification-bell/>
            <a class="admin-account" href="{{ route('account.edit') }}" aria-label="Cài đặt tài khoản"><x-avatar :user="auth()->user()"/><span>{{ auth()->user()->name }}</span></a>
        </div>
    </header>
    <main id="main" class="admin-main" tabindex="-1">
        <x-flash/>
        @if(request()->routeIs('admin.community.*', 'admin.replies.*'))
            <nav class="admin-section-tabs" aria-label="Quản lý nội dung">
                <a href="{{ route('admin.community.index') }}" @class(['active' => request()->routeIs('admin.community.*')]) @if(request()->routeIs('admin.community.*')) aria-current="page" @endif><x-journal-icon name="book" :size="17"/>Bài đăng</a>
                <a href="{{ route('admin.replies.index') }}" @class(['active' => request()->routeIs('admin.replies.*')]) @if(request()->routeIs('admin.replies.*')) aria-current="page" @endif><x-journal-icon name="message" :size="17"/>Trả lời</a>
            </nav>
        @endif
        @yield('content')
    </main>
    <footer class="admin-footer"><span>Threads City <span aria-hidden="true">/</span> Không gian quản trị</span><span>Kết nối cộng đồng. Giữ không gian an toàn.</span></footer>
</div>
<nav class="admin-mobile-nav" aria-label="Truy cập nhanh trên điện thoại">
    @foreach([['admin.index','Tổng quan','chart'], ['admin.community.index','Bài đăng','book'], ['admin.moderation.index','Báo cáo','info']] as [$route, $label, $icon])
        @php($isActive = request()->routeIs($route))
        <a href="{{ route($route) }}" @class(['active' => $isActive]) @if($isActive) aria-current="page" @endif><x-journal-icon :name="$icon" :size="21"/><span>{{ $label }}</span></a>
    @endforeach
    <button type="button" data-admin-menu-toggle data-bs-toggle="offcanvas" data-bs-target="#admin-navigation" aria-controls="admin-navigation" aria-expanded="false"><x-journal-icon name="menu" :size="21"/><span>Danh mục</span></button>
</nav>
@stack('scripts')
</body>
</html>

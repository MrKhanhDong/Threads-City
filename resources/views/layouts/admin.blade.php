<!doctype html>
<html lang="vi" data-theme="dark" data-bs-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex,nofollow">
    <meta name="theme-color" content="#0a0a0a">
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
<aside class="admin-sidebar" aria-label="Thanh quản trị">
    <a class="brand" href="{{ route('admin.index') }}"><span class="brand-mark" aria-hidden="true">@</span><span>{{ config('blog.name') }}<small>Không gian quản trị</small></span></a>
    <span class="nav-caption">CỘNG ĐỒNG</span>
    <nav aria-label="Quản trị">
        @foreach([
            ['admin.index','Tổng quan','chart'],
            ['admin.community.index','Bài đăng & trả lời','book'],
            ['admin.moderation.index','Báo cáo vi phạm','info'],
            ['admin.comments.index','Bình luận','message'],
            ['admin.users.index','Người dùng','people'],
            ['admin.history.index','Lịch sử xử lý','repost'],
            ['admin.settings.edit','Cài đặt website','settings'],
        ] as [$route,$label,$icon])
        @php($isActive = $route === 'admin.index' ? request()->routeIs($route) : request()->routeIs(\Illuminate\Support\Str::beforeLast($route, '.').'.*'))
        <a @class(['active'=>$isActive]) href="{{ route($route) }}" @if($isActive) aria-current="page" @endif><x-journal-icon :name="$icon" :size="21"/><span>{{ $label }}</span></a>
        @endforeach
    </nav>
    <div class="sidebar-bottom">
        <a href="{{ route('home') }}"><x-journal-icon name="arrow-left" :size="19"/> Về trang chính</a>
        <form method="post" action="{{ route('logout') }}">@csrf<button type="submit"><x-journal-icon name="arrow" :size="19"/> Đăng xuất</button></form>
        <small>Threads City · Quản trị cộng đồng</small>
    </div>
</aside>
<div class="admin-content">
    <header class="admin-topbar">
        <div class="admin-topbar-title"><span class="admin-breadcrumb">Threads City <span aria-hidden="true">/</span></span><strong>{{ $pageTitle ?? 'Quản trị' }}</strong></div>
        <div class="header-actions">
            <a class="admin-public-link" href="{{ route('home') }}"><span>Xem website</span><x-journal-icon name="arrow" :size="17"/></a>
            <button type="button" class="admin-icon-button" data-theme-toggle aria-label="Đổi giao diện sáng tối" title="Đổi giao diện sáng tối"><x-journal-icon name="sun" :size="20"/></button>
            <x-notification-bell/>
            <a class="admin-account" href="{{ route('account.edit') }}"><x-avatar :user="auth()->user()"/><span>{{ auth()->user()->name }}</span></a>
        </div>
    </header>
    <main id="main" class="admin-main" tabindex="-1"><x-flash/>@yield('content')</main>
    <footer class="admin-footer"><span>Threads City</span><span>Kết nối cộng đồng. Giữ không gian an toàn.</span></footer>
</div>
@stack('scripts')
</body>
</html>

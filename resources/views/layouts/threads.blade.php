<!doctype html>
<html lang="vi" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $pageTitle ?? 'Threads' }} — Cộng đồng chia sẻ ý tưởng</title>
    <meta name="description" content="Nền tảng chia sẻ suy nghĩ, thảo luận dạng chuỗi và kết nối cộng đồng phong cách Threads của Meta.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        :root {
            --bg-body: #000000;
            --bg-sidebar: #000000;
            --bg-card: #0a0a0a;
            --bg-card-hover: #121212;
            --bg-input: #141414;
            --border-color: #202020;
            --border-hover: #333333;
            --text-primary: #f3f5f7;
            --text-secondary: #777777;
            --text-muted: #555555;
            --accent-yellow: #facc15;
            --accent-pink: #ff3040;
            --accent-blue: #0095f6;
            --accent-green: #00ba7c;
        }
        * { box-sizing: border-box; }
        body.threads-body {
            background-color: var(--bg-body);
            color: var(--text-primary);
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            margin: 0;
            padding: 0;
            min-height: 100vh;
            -webkit-font-smoothing: antialiased;
            overflow-x: hidden;
        }
        a { color: inherit; text-decoration: none; }
        a:hover { color: inherit; }

        /* Master 3-Column Shell */
        .threads-app-shell {
            display: flex;
            justify-content: center;
            max-width: 1300px;
            margin: 0 auto;
            min-height: 100vh;
            padding: 0 16px;
            gap: 28px;
        }

        /* 1. Left Navigation Sidebar */
        .threads-left-sidebar {
            width: 236px;
            flex-shrink: 0;
            position: sticky;
            top: 0;
            height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 18px 0 24px;
            overflow-y: auto;
        }
        .left-sidebar-top {
            display: flex;
            flex-direction: column;
        }
        .threads-brand-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 10px 20px;
        }
        .brand-threads-logo {
            display: flex;
            align-items: center;
            gap: 4px;
            font-weight: 800;
            font-size: 21px;
            color: #ffffff;
            letter-spacing: -0.5px;
        }
        .brand-threads-logo svg {
            width: 28px;
            height: 28px;
            fill: #ffffff;
        }
        .menu-bars-btn {
            background: none;
            border: none;
            color: #ffffff;
            cursor: pointer;
            padding: 4px;
            font-size: 20px;
            line-height: 1;
        }

        /* Nav List */
        .sidebar-nav-list {
            display: flex;
            flex-direction: column;
            gap: 3px;
        }
        .sidebar-nav-item {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 10px 14px;
            border-radius: 12px;
            font-size: 15px;
            font-weight: 600;
            color: #e5e7eb;
            transition: background 0.15s, color 0.15s;
        }
        .sidebar-nav-item:hover {
            background: rgba(255, 255, 255, 0.05);
            color: #ffffff;
        }
        .sidebar-nav-item.active {
            background: #181818;
            color: #ffffff;
            font-weight: 700;
        }
        .sidebar-nav-item svg {
            width: 22px;
            height: 22px;
            flex-shrink: 0;
        }
        .sidebar-badge-count {
            position: absolute;
            top: -5px;
            right: -7px;
            background: #ff2d55;
            color: #ffffff;
            font-size: 10px;
            font-weight: 700;
            min-width: 16px;
            height: 16px;
            padding: 0 4px;
            border-radius: 9999px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 2px solid #101010;
            box-shadow: 0 1px 3px rgba(0,0,0,0.5);
            line-height: 1;
        }

        /* Feeds Sub-section */
        .sidebar-sub-section {
            margin-top: 24px;
            padding-top: 18px;
            border-top: 1px solid var(--border-color);
        }
        .sub-section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 14px 10px;
            font-size: 13px;
            color: var(--text-secondary);
            font-weight: 600;
        }
        .sub-section-header a {
            color: var(--text-muted);
            font-size: 12px;
        }
        .sub-section-header a:hover {
            color: var(--text-secondary);
        }
        .sub-nav-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 8px 14px;
            border-radius: 10px;
            font-size: 14px;
            color: #d1d5db;
            font-weight: 500;
            transition: all 0.15s;
        }
        .sub-nav-item:hover {
            color: #ffffff;
            background: rgba(255, 255, 255, 0.04);
        }
        .sub-nav-item.active {
            color: #ffffff;
            font-weight: 700;
        }
        .sub-nav-item svg {
            width: 18px;
            height: 18px;
            flex-shrink: 0;
        }

        .left-sidebar-bottom {
            padding: 12px 14px 0;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .left-sidebar-bottom-icon {
            color: var(--text-muted);
            cursor: pointer;
            transition: color 0.15s;
        }
        .left-sidebar-bottom-icon:hover {
            color: #ffffff;
        }

        /* 2. Center Feed Column */
        .threads-center-feed {
            width: 630px;
            max-width: 630px;
            flex-shrink: 0;
            min-height: 100vh;
            padding: 0 0 80px;
            border-left: 1px solid var(--border-color);
            border-right: 1px solid var(--border-color);
        }

        /* Feed Header Bar */
        .feed-header-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 14px 20px;
            border-bottom: 1px solid var(--border-color);
            position: sticky;
            top: 0;
            background: rgba(0, 0, 0, 0.88);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            z-index: 50;
        }
        .feed-header-title {
            font-size: 16px;
            font-weight: 700;
            color: #ffffff;
            display: flex;
            align-items: center;
            gap: 6px;
            margin: 0 auto;
            transform: translateX(20px);
        }
        .feed-header-tools {
            display: flex;
            align-items: center;
            gap: 10px;
            color: var(--text-secondary);
        }
        .feed-tool-btn {
            background: none;
            border: none;
            color: var(--text-secondary);
            cursor: pointer;
            padding: 4px;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: color 0.15s;
        }
        .feed-tool-btn:hover {
            color: #ffffff;
        }

        /* 3. Right Sidebar (Widgets) */
        .threads-right-sidebar {
            width: 330px;
            flex-shrink: 0;
            position: sticky;
            top: 0;
            height: 100vh;
            padding: 14px 0 40px;
            overflow-y: auto;
        }

        /* Search input */
        .threads-search-bar {
            position: relative;
            margin-bottom: 22px;
        }
        .threads-search-input {
            width: 100%;
            background: #121212;
            border: 1px solid var(--border-color);
            border-radius: 9999px;
            padding: 10px 18px 10px 42px;
            color: #ffffff;
            font-size: 14px;
            outline: none;
            transition: all 0.2s;
        }
        .threads-search-input:focus {
            background: #181818;
            border-color: #383838;
        }
        .threads-search-input::placeholder {
            color: var(--text-muted);
        }
        .search-icon-pos {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            pointer-events: none;
        }
        /* Trending Widget */
        .widget-box {
            background: #121212;
            border: 1px solid var(--border-color);
            border-radius: 18px;
            padding: 18px 18px;
            margin-bottom: 20px;
        }
        .trending-header-title {
            color: #facc15 !important;
            font-size: 18px;
            font-weight: 800;
            margin-bottom: 4px;
            letter-spacing: -0.2px;
        }
        .trending-header-sub {
            color: #888888;
            font-size: 13px;
            line-height: 1.4;
            margin-bottom: 14px;
        }
        .trending-item {
            padding: 12px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            display: block;
            text-decoration: none;
            transition: opacity 0.15s;
        }
        .trending-item:hover {
            opacity: 0.85;
        }
        .trending-item:last-of-type {
            border-bottom: none;
        }
        .trending-item-title {
            font-size: 15px;
            font-weight: 700;
            color: #ffffff;
            line-height: 1.35;
            margin-bottom: 4px;
        }
        .trending-item-desc {
            font-size: 13px;
            color: #999999;
            line-height: 1.45;
            margin-bottom: 5px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .trending-item-count {
            font-size: 12.5px;
            color: #666666;
            font-weight: 500;
        }
        .widget-see-more {
            display: inline-block;
            margin-top: 14px;
            font-size: 14px;
            color: #777777;
            font-weight: 500;
            text-decoration: none;
            transition: color 0.15s;
        }
        .widget-see-more:hover {
            color: #ffffff;
        }
            color: #ffffff;
        }

        /* Suggested Users Widget */
        .suggested-users-title {
            font-size: 16px;
            font-weight: 800;
            color: #ffffff;
            margin-bottom: 16px;
        }
        .suggested-user-item {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 12px;
            padding: 12px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.04);
        }
        .suggested-user-item:last-child {
            border-bottom: none;
        }
        .suggested-user-info {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            min-width: 0;
            flex: 1;
        }
        .suggested-user-names {
            min-width: 0;
            line-height: 1.35;
        }
        .suggested-user-name {
            font-size: 13px;
            font-weight: 700;
            color: #ffffff;
            display: flex;
            align-items: center;
            gap: 4px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .suggested-user-subname {
            font-size: 12px;
            color: var(--text-muted);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .suggested-user-bio {
            font-size: 12px;
            color: #d1d5db;
            margin-top: 2px;
            white-space: pre-line;
            line-height: 1.35;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .suggested-user-followers {
            font-size: 11px;
            color: var(--text-muted);
            margin-top: 2px;
        }
        .btn-follow-pill {
            background: #000000;
            color: #ffffff;
            border: 1px solid #333333;
            font-size: 12px;
            font-weight: 700;
            padding: 6px 16px;
            border-radius: 9999px;
            cursor: pointer;
            transition: all 0.15s;
            flex-shrink: 0;
        }
        .btn-follow-pill:hover {
            border-color: #666;
            background: #141414;
        }
        .btn-follow-pill.following {
            background: transparent;
            color: var(--text-muted);
            border-color: #222222;
        }
        .btn-follow-pill.following:hover {
            color: #ffffff;
            border-color: #444444;
        }

        /* Buttons & Components */
        .user-avatar-round {
            width: 38px !important;
            height: 38px !important;
            max-width: 38px !important;
            max-height: 38px !important;
            border-radius: 50% !important;
            object-fit: cover !important;
            background: #222 !important;
            display: block !important;
            flex-shrink: 0 !important;
        }
        .user-avatar-letter {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            background: #242424;
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 15px;
            border: 1px solid #333;
        }
        .btn-threads-primary {
            background: #f3f5f7;
            color: #000000;
            font-weight: 700;
            font-size: 14px;
            border-radius: 9999px;
            padding: 8px 18px;
            border: none;
            transition: all 0.15s ease;
            cursor: pointer;
        }
        .btn-threads-primary:hover {
            background: #ffffff;
        }
        .btn-threads-outline {
            background: transparent;
            color: #f3f5f7;
            font-weight: 600;
            font-size: 13px;
            border-radius: 9999px;
            padding: 6px 14px;
            border: 1px solid var(--border-color);
            transition: all 0.15s ease;
            cursor: pointer;
        }
        .btn-threads-outline:hover {
            border-color: #555;
            background: rgba(255, 255, 255, 0.06);
        }

        /* Responsive Breakpoints */
        @media (max-width: 1220px) {
            .threads-right-sidebar { display: none !important; }
            .threads-app-shell { justify-content: center; }
        }
        @media (max-width: 890px) {
            .threads-left-sidebar { width: 72px; }
            .sidebar-nav-item span, .brand-threads-logo span, .sub-section-header, .sub-nav-item span { display: none; }
            .sidebar-nav-item { justify-content: center; padding: 12px; }
            .sub-nav-item { justify-content: center; padding: 10px; }
            .threads-center-feed { width: 100%; border-right: none; }
        }
        @media (max-width: 600px) {
            .threads-left-sidebar { display: none !important; }
            .threads-center-feed { border: none; padding: 0 0 70px; }
        }

        /* Toast */
        .threads-toast {
            position: fixed;
            bottom: 32px;
            left: 50%;
            transform: translateX(-50%) translateY(100px);
            background: #242424;
            color: #ffffff;
            padding: 12px 24px;
            border-radius: 9999px;
            font-size: 14px;
            font-weight: 500;
            border: 1px solid #383838;
            box-shadow: 0 10px 30px rgba(0,0,0,0.5);
            z-index: 1000;
            opacity: 0;
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
            pointer-events: none;
        }
        .threads-toast.show {
            transform: translateX(-50%) translateY(0);
            opacity: 1;
        }

        /* Mention Autocomplete Dropdown */
        .mention-autocomplete-dropdown {
            position: absolute;
            bottom: calc(100% + 4px);
            left: 0;
            width: 280px;
            max-height: 220px;
            overflow-y: auto;
            background: #1e1e1e;
            border: 1px solid #333333;
            border-radius: 14px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.85);
            z-index: 1060;
            padding: 6px 0;
        }
        .mention-autocomplete-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px 12px;
            cursor: pointer;
            transition: background 0.15s ease;
        }
        .mention-autocomplete-item:hover,
        .mention-autocomplete-item.selected {
            background: #2a2a2a;
        }
        .mention-item-avatar {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            object-fit: cover;
            flex-shrink: 0;
        }
        .mention-item-letter {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: #333333;
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: 700;
            flex-shrink: 0;
        }
        .mention-item-name {
            font-size: 13px;
            font-weight: 600;
            color: #ffffff;
            line-height: 1.2;
        }
        .mention-item-handle {
            font-size: 12px;
            color: #777777;
            line-height: 1.2;
        }
    </style>
    @stack('threads-styles')
</head>
<body class="threads-body" data-live-enabled="{{ request()->routeIs('home', 'threads.index', 'threads.show', 'profile.show', 'saved', 'notifications.index') ? '1' : '0' }}">
    <script>
        window.THREAD_AUTH_USER_ID = {{ auth()->id() ? auth()->id() : 'null' }};
        window.IS_AUTHENTICATED = {{ auth()->check() ? 'true' : 'false' }};
    </script>

    <div class="threads-app-shell">

        <!-- 1. LEFT SIDEBAR: Brand & Navigation -->
        <aside class="threads-left-sidebar">
            <div class="left-sidebar-top">
                <!-- Brand logo -->
                <div class="threads-brand-row">
                    <a href="{{ route('home') }}" class="brand-threads-logo" title="Threads">
                        <svg viewBox="0 0 192 192">
                            <path d="M141.537 88.9883C140.71 88.5919 139.87 88.2109 139.019 87.8451C137.537 60.5382 122.616 44.905 97.5619 44.745C97.4111 44.744 97.2604 44.744 97.1096 44.744C75.2536 44.744 57.0707 58.751 51.5204 79.882C46.0691 100.638 53.6841 123.332 70.8251 137.416C87.9661 151.5 112.43 155.083 133.024 146.516C148.971 139.878 159.227 124.965 160.827 106.012H144.741C143.342 118.847 136.035 129.351 124.636 134.1C109.117 140.556 90.6811 137.856 77.7656 127.243C64.8501 116.63 59.1121 99.5306 63.2201 83.8906C67.4326 67.8546 81.2336 57.214 97.7331 57.214C97.8471 57.214 97.9611 57.215 98.0751 57.216C113.627 57.315 124.516 67.045 125.753 84.148C117.753 82.522 108.887 81.879 99.5371 82.261C73.1971 83.337 57.4981 97.575 58.3341 117.747C59.1381 137.147 75.8361 149.336 96.0601 148.471C111.455 147.813 124.12 140.174 130.407 127.818C134.825 136.195 142.155 142.365 150.941 145.42C157.65 147.752 164.838 148.33 171.936 147.108L174.524 134.42C169.176 135.337 163.743 134.901 158.673 133.138C150.771 130.392 144.974 123.86 142.753 115.228C148.513 107.035 151.706 97.234 151.706 87.051C151.706 84.821 151.482 82.607 151.042 80.441L141.537 88.9883ZM127.469 110.15C125.132 117.841 117.844 134.871 96.6961 135.773C83.8401 136.321 73.1201 128.528 72.5851 115.632C71.9961 101.444 83.2161 93.684 99.0741 93.037C105.748 92.765 112.186 93.284 118.067 94.55C122.956 95.602 126.155 97.214 127.469 110.15Z"/>
                        </svg>
                        <span>threads</span>
                    </a>
                    <button type="button" class="menu-bars-btn" data-bs-toggle="dropdown" aria-expanded="false" title="Tùy chọn">
                        ≡
                    </button>
                    <ul class="dropdown-menu dropdown-menu-dark threads-dropdown-menu">
                        @auth
                            <li><a class="dropdown-item" href="{{ route('account.edit') }}">Cài đặt tài khoản</a></li>
                            @if(auth()->user()->isAdmin())
                                <li><a class="dropdown-item" href="{{ route('admin.index') }}">Quản trị CMS</a></li>
                            @endif
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <form action="{{ route('logout') }}" method="post">
                                    @csrf
                                    <button type="submit" class="dropdown-item text-danger">Đăng xuất</button>
                                </form>
                            </li>
                        @else
                            <li><a class="dropdown-item" href="{{ route('login') }}">Đăng nhập</a></li>
                            <li><a class="dropdown-item" href="{{ route('register') }}">Đăng ký</a></li>
                        @endauth
                    </ul>
                </div>

                <!-- Main Nav Links -->
                <nav class="sidebar-nav-list">
                    <a href="{{ route('home', ['tab' => 'for-you']) }}" class="sidebar-nav-item {{ request()->routeIs('home') && request('tab', 'for-you') === 'for-you' ? 'active' : '' }}">
                        <svg viewBox="0 0 24 24" fill="{{ request()->routeIs('home') && request('tab', 'for-you') === 'for-you' ? 'currentColor' : 'none' }}" stroke="currentColor" stroke-width="2">
                            <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                            <polyline points="9 22 9 12 15 12 15 22"></polyline>
                        </svg>
                        <span>Dành cho bạn</span>
                    </a>

                    @auth
                    <a href="javascript:void(0)" class="sidebar-nav-item" data-bs-toggle="modal" data-bs-target="#newThreadModal">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                            <line x1="12" y1="5" x2="12" y2="19"></line>
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                        </svg>
                        <span>Thread mới</span>
                    </a>
                    @else
                    <a href="javascript:void(0)" class="sidebar-nav-item" onclick="window.checkAuth('tạo bài viết mới')">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                            <line x1="12" y1="5" x2="12" y2="19"></line>
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                        </svg>
                        <span>Thread mới</span>
                    </a>
                    @endauth

                    <a href="{{ route('posts.index') }}" class="sidebar-nav-item {{ request()->routeIs('posts.index') ? 'active' : '' }}">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="11" cy="11" r="8"></circle>
                            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                        </svg>
                        <span>Tìm kiếm</span>
                    </a>

                    <a href="javascript:void(0)" class="sidebar-nav-item text-muted">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                            <polyline points="22,6 12,13 2,6"></polyline>
                        </svg>
                        <span>Tin nhắn</span>
                    </a>

                    @auth
                    @php $unreadNavCount = auth()->user()->unreadNotifications()->count(); @endphp
                    <a href="{{ route('notifications.index') }}" class="sidebar-nav-item {{ request()->routeIs('notifications.*') ? 'active' : '' }}">
                        <div class="sidebar-icon-wrap" style="position: relative; display: inline-flex; align-items: center;">
                            <svg viewBox="0 0 24 24" fill="{{ request()->routeIs('notifications.*') ? 'currentColor' : 'none' }}" stroke="currentColor" stroke-width="2">
                                <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
                            </svg>
                            <span id="sidebarNotificationBadge" data-live-region="badge" class="sidebar-badge-count" style="{{ $unreadNavCount > 0 ? '' : 'display: none;' }}">
                                {{ $unreadNavCount > 99 ? '99+' : $unreadNavCount }}
                            </span>
                        </div>
                        <span>Hoạt động</span>
                    </a>

                    <a href="{{ route('profile.show', auth()->user()->username ?? auth()->user()->id) }}" class="sidebar-nav-item {{ request()->is('@*') ? 'active' : '' }}">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                            <circle cx="12" cy="7" r="4"></circle>
                        </svg>
                        <span>Trang cá nhân</span>
                    </a>
                    @endauth

                    <a href="{{ route('account.edit') }}" class="sidebar-nav-item {{ request()->routeIs('account.*') ? 'active' : '' }}">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="18" y1="20" x2="18" y2="10"></line>
                            <line x1="12" y1="20" x2="12" y2="4"></line>
                            <line x1="6" y1="20" x2="6" y2="14"></line>
                        </svg>
                        <span>Thông tin chi tiết</span>
                    </a>
                </nav>

                <!-- Sub-section: Bảng feed khác -->
                <div class="sidebar-sub-section">
                    <div class="sub-section-header">
                        <span>Bảng feed khác</span>
                        <a href="javascript:void(0)">Chỉnh sửa</a>
                    </div>
                    <a href="{{ route('home', ['tab' => 'following']) }}" class="sub-nav-item {{ request('tab') === 'following' ? 'active' : '' }}">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                        <span>Đang theo dõi</span>
                    </a>
                    @auth
                    <a href="{{ route('saved') }}" class="sub-nav-item {{ request()->routeIs('saved') ? 'active' : '' }}">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"></path></svg>
                        <span>Đã lưu</span>
                    </a>
                    @else
                    <a href="javascript:void(0)" class="sub-nav-item" onclick="window.checkAuth('xem bài viết đã lưu')">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"></path></svg>
                        <span>Đã lưu</span>
                    </a>
                    @endauth
                    @auth
                    <a href="{{ route('profile.show', ['username' => auth()->user()->username ?? auth()->user()->id, 'tab' => 'likes']) }}" class="sub-nav-item">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path></svg>
                        <span>Đã thích</span>
                    </a>
                    @endauth
                    <a href="javascript:void(0)" class="sub-nav-item text-muted">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"></path></svg>
                        <span>Bài viết tự hủy</span>
                    </a>
                    <a href="javascript:void(0)" class="sub-nav-item text-muted">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="21 8 21 21 3 21 3 8"></polyline><rect x="1" y="3" width="22" height="5"></rect><line x1="10" y1="12" x2="14" y2="12"></line></svg>
                        <span>Lưu trữ</span>
                    </a>
                </div>
            </div>

            <!-- Left Sidebar Bottom (User / Clock) -->
            <div class="left-sidebar-bottom">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="text-secondary">
                    <circle cx="12" cy="12" r="10"></circle>
                    <polyline points="12 6 12 12 16 14"></polyline>
                </svg>

                @auth
                    <div class="dropdown">
                        <button class="d-flex align-items-center gap-2 bg-transparent border-0 text-white cursor-pointer" data-bs-toggle="dropdown" aria-expanded="false">
                            @if(auth()->user()->avatar_path)
                                <img src="{{ route('avatar.show', auth()->user()->id) }}" class="user-avatar-round" style="width: 28px; height: 28px;">
                            @else
                                <div class="user-avatar-letter" style="width: 28px; height: 28px; font-size: 12px;">{{ mb_substr(auth()->user()->name, 0, 1) }}</div>
                            @endif
                        </button>
                        <ul class="dropdown-menu dropdown-menu-dark threads-dropdown-menu">
                            <li><span class="dropdown-item-text text-white fw-bold">{{ auth()->user()->name }}</span></li>
                            <li><a class="dropdown-item" href="{{ route('profile.show', auth()->user()->username ?? auth()->user()->id) }}">Trang cá nhân</a></li>
                            <li><a class="dropdown-item" href="{{ route('account.edit') }}">Cài đặt tài khoản</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <form action="{{ route('logout') }}" method="post">
                                    @csrf
                                    <button type="submit" class="dropdown-item text-danger">Đăng xuất</button>
                                </form>
                            </li>
                        </ul>
                    </div>
                @else
                    <a href="{{ route('login') }}" class="btn-threads-outline py-1 px-3" style="font-size: 12px;">Đăng nhập</a>
                @endauth
            </div>
        </aside>

        <!-- 2. CENTER FEED: Main Column -->
        <main class="threads-center-feed">
            @yield('content')
        </main>

        <!-- 3. RIGHT SIDEBAR: Search, Trending, Who to follow -->
        <aside class="threads-right-sidebar">
            <!-- Search bar -->
            <div class="threads-search-bar">
                <form action="{{ route('posts.index') }}" method="get">
                    <svg class="search-icon-pos" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="11" cy="11" r="8"></circle>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                    </svg>
                    <input type="text" name="q" class="threads-search-input" placeholder="Tìm kiếm" value="{{ request('q') }}">
                </form>
            </div>

            <!-- Trending Widget (Đang thịnh hành) -->
            <div class="widget-box">
                <div class="trending-header-title">Đang thịnh hành</div>
                <div class="trending-header-sub">Những gì mọi người đang bàn luận, do AI tóm tắt</div>

                @if(!empty($trendingTopics) && (is_countable($trendingTopics) ? count($trendingTopics) > 0 : true))
                    @foreach($trendingTopics as $tTopic)
                        @php
                            if (is_string($tTopic)) {
                                $topicTag = $tTopic;
                                $topicTitle = $tTopic;
                                $topicDesc = '';
                                $topicCount = '';
                            } else {
                                $topicTag = is_array($tTopic) ? ($tTopic['tag'] ?? $tTopic['title'] ?? '') : ($tTopic->tag ?? $tTopic->title ?? '');
                                $topicTitle = is_array($tTopic) ? ($tTopic['title'] ?? '') : ($tTopic->title ?? '');
                                $topicDesc = is_array($tTopic) ? ($tTopic['desc'] ?? '') : ($tTopic->desc ?? '');
                                $topicCount = is_array($tTopic) ? ($tTopic['count'] ?? '') : ($tTopic->display_count ?? $tTopic->count ?? '');
                            }
                        @endphp
                        <a href="{{ route('threads.index', ['q' => $topicTag]) }}" class="trending-item">
                            <div class="trending-item-title">{{ $topicTitle }}</div>
                            @if($topicDesc)
                                <div class="trending-item-desc">{{ $topicDesc }}</div>
                            @endif
                            @if($topicCount)
                                <div class="trending-item-count">{{ str_contains($topicCount, 'bài viết') ? $topicCount : $topicCount . ' bài viết' }}</div>
                            @endif
                        </a>
                    @endforeach
                @endif

                <a href="{{ route('threads.index') }}" class="widget-see-more">Hiển thị thêm</a>
            </div>

            <!-- Suggested Users Widget (Gợi ý theo dõi) -->
            <div class="widget-box">
                <div class="suggested-users-title">Gợi ý theo dõi</div>

                @if(!empty($suggestedUsers))
                    @foreach($suggestedUsers as $sUser)
                        @php
                            $sUserId = (int) data_get($sUser, 'id');
                            $sUserName = (string) (data_get($sUser, 'name') ?? '');
                            $sUserUsername = (string) (data_get($sUser, 'username') ?? $sUserName);
                            $sUserBio = (string) (data_get($sUser, 'bio') ?? '');
                            $sUserAvatar = (string) (data_get($sUser, 'avatar_path') ?? '');
                            $sUserIsAdmin = (bool) (data_get($sUser, 'is_admin') ?? false);
                            $isFollowing = isset($sidebarFollowingIds[$sUserId]);
                        @endphp
                        <div class="suggested-user-item">
                            <a href="{{ route('profile.show', $sUserUsername ?: $sUserId) }}" class="suggested-user-info">
                                @if($sUserAvatar)
                                    <img src="{{ route('avatar.show', $sUserId) }}" class="user-avatar-round" style="width: 36px; height: 36px;" alt="{{ $sUserName }}">
                                @else
                                    <div class="user-avatar-letter" style="width: 36px; height: 36px; font-size: 14px;">{{ mb_substr($sUserName, 0, 1) }}</div>
                                @endif
                                <div class="suggested-user-names">
                                    <div class="suggested-user-name">
                                        {{ $sUserUsername }}
                                        @if($sUserIsAdmin || in_array($sUserUsername, ['binhphatlien', 'dieu_nhiii']))
                                            <svg width="13" height="13" viewBox="0 0 24 24" fill="#0095f6"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>
                                        @endif
                                    </div>
                                    <div class="suggested-user-subname">{{ $sUserName }}</div>
                                    @if($sUserBio)
                                        <div class="suggested-user-bio">{{ $sUserBio }}</div>
                                    @endif
                                </div>
                            </a>
                            @auth
                                <button type="button" class="btn-follow-pill {{ $isFollowing ? 'following' : '' }} btn-ajax-sidebar-follow" data-user-id="{{ $sUserId }}">
                                    {{ $isFollowing ? 'Đang theo dõi' : 'Theo dõi' }}
                                </button>
                            @else
                                <button type="button" class="btn-follow-pill" onclick="window.checkAuth('theo dõi {{ e($sUserName ?: 'người dùng này') }}')">Theo dõi</button>
                            @endauth
                        </div>
                    @endforeach
                @endif
            </div>
        </aside>

    </div>

    <!-- Toast Notification for Ajax Feedback -->
    <div id="threadsGlobalToast" class="threads-toast">Thông báo</div>

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
                        if (data.following) {
                            btn.textContent = 'Đang theo dõi';
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
                if (data && data.success && data.following) {
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

        function closeDropdown() {
            dropdown.style.display = 'none';
            dropdown.innerHTML = '';
            suggestions = [];
            activeIndex = -1;
        }

        textarea.addEventListener('input', function() {
            const val = textarea.value;
            const cursorPos = textarea.selectionStart;
            const textBeforeCursor = val.slice(0, cursorPos);

            const match = textBeforeCursor.match(/@([A-Za-z0-9_]*)$/);
            if (!match) {
                closeDropdown();
                return;
            }

            atIndex = cursorPos - match[0].length;
            queryTerm = match[1];

            fetch(`/api/users/mention-suggestions?q=${encodeURIComponent(queryTerm)}`, {
                headers: { 'Accept': 'application/json' }
            })
            .then(res => res.json())
            .then(users => {
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
                
                const avatarHtml = u.avatar_url 
                    ? `<img src="${u.avatar_url}" class="mention-item-avatar">`
                    : `<div class="mention-item-letter">${(u.name || 'U').charAt(0).toUpperCase()}</div>`;
                
                item.innerHTML = `
                    ${avatarHtml}
                    <div class="d-flex flex-column overflow-hidden">
                        <span class="mention-item-name text-truncate">${u.name}</span>
                        <span class="mention-item-handle text-truncate">${u.handle}</span>
                    </div>
                `;
                item.addEventListener('mousedown', function(e) {
                    e.preventDefault();
                    insertMention(u.username);
                });
                dropdown.appendChild(item);
            });
            dropdown.style.display = 'block';
        }

        function insertMention(username) {
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
                    insertMention(suggestions[activeIndex].username);
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

<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="@yield('description', $author['bio'])">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @hasSection('robots')<meta name="robots" content="@yield('robots')">@endif
    <title>@yield('title', 'Trang chủ') · {{ $blogTitle }}</title>
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <script src="{{ asset('js/theme.js') . '?v=' . filemtime(public_path('js/theme.js')) }}"></script>
    <link rel="stylesheet" href="{{ asset('css/blog.css') . '?v=' . filemtime(public_path('css/blog.css')) }}">
    <script src="{{ asset('js/blog.js') }}" defer></script>
</head>
<body>
    <a class="skip-link" href="#main-content">Đến nội dung chính</a>
    <header class="site-header">
        <div class="header-inner">
            <a class="brand" href="{{ route('home') }}"><span class="brand-mark"><x-icon name="book" size="23" /></span><span>{{ $blogTitle }}<span class="brand-dot">.</span></span></a>
            <nav class="header-nav" aria-label="Điều hướng chính">
                <a href="{{ route('home') }}" @class(['is-active' => request()->routeIs('home')])>Trang chủ</a>
                <a href="{{ route('posts.index') }}" @class(['is-active' => request()->routeIs('posts.*')])>Bài viết</a>
                <a href="{{ route('about') }}" @class(['is-active' => request()->routeIs('about')])>Về mình</a>
            </nav>
            <button class="theme-toggle" type="button" data-theme-toggle aria-label="Chuyển sang giao diện tối" aria-pressed="false" hidden>
                <span class="theme-toggle__track" aria-hidden="true"><span class="theme-toggle__orb"></span></span>
                <span class="sr-only theme-toggle__label" data-theme-label>Chế độ tối</span>
            </button>
            @auth
                <a class="account-link" href="{{ route('account.edit') }}"><x-icon name="user" size="18" />{{ auth()->user()->name }}</a>
            @else
                <a class="button button--black" href="{{ route('login') }}">Đăng nhập</a>
            @endauth
        </div>
    </header>
    <div @class(['workspace', 'workspace--admin' => request()->routeIs('admin.*')])>
        <aside class="left-rail">
            <div class="author-intro">
                @if($author['avatar'])<img class="avatar" src="{{ $author['avatar'] }}" alt="Ảnh {{ $author['name'] }}">@else<span class="avatar">{{ $author['initials'] }}</span>@endif
                <strong>{{ $author['name'] }}</strong><span>{{ $author['role'] }}</span>
                <p>Một góc nhỏ để viết,<br>học hỏi và lưu giữ.</p>
            </div>
            <nav class="side-nav" aria-label="Khám phá blog">
                <a href="{{ route('home') }}" @class(['is-active' => request()->routeIs('home')])><x-icon name="home" />Trang chủ</a>
                <a href="{{ route('posts.index') }}" @class(['is-active' => request()->routeIs('posts.*')])><x-icon name="book" />Tất cả bài viết<span class="nav-count">{{ $postCount }}</span></a>
                <a href="{{ route('categories') }}" @class(['is-active' => request()->routeIs('categories')])><x-icon name="grid" />Chuyên mục</a>
                <a href="{{ route('saved') }}" @class(['is-active' => request()->routeIs('saved')])><x-icon name="bookmark" />Đọc sau</a>
                <a href="{{ route('about') }}" @class(['is-active' => request()->routeIs('about')])><x-icon name="user" />Về mình</a>
            </nav>
            <div class="rail-bottom">
                @auth
                    @if(auth()->user()->isAdmin())<a href="{{ route('admin.index') }}" class="admin-link"><x-icon name="edit" size="18" />Góc người viết</a>@endif
                    <form action="{{ route('logout') }}" method="post" class="logout-form">@csrf<button class="text-button" type="submit">Đăng xuất</button></form>
                @else
                    <a class="text-link" href="{{ route('register') }}">Tạo tài khoản độc giả <x-icon name="arrow" size="16" /></a>
                @endauth
                <p>Viết chậm. Nghĩ sâu. Chia sẻ thật.</p><small>© {{ date('Y') }} {{ $blogTitle }}</small>
            </div>
        </aside>
        <main id="main-content" class="main-column">
            @if(session('success'))<div class="flash flash--success" role="status">{{ session('success') }}</div>@endif
            @if($errors->any())<div class="flash flash--error" role="alert"><strong>Vui lòng kiểm tra lại:</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
            @yield('content')
        </main>
        @unless(request()->routeIs('admin.*'))
            <aside class="right-rail">
                @hasSection('toc')
                    <section class="rail-section"><span class="overline">TRONG BÀI VIẾT NÀY</span><nav class="toc" aria-label="Mục lục bài viết">@yield('toc')</nav></section>
                @else
                    <section class="rail-section"><span class="overline">MỘT CHÚT VỀ MÌNH</span><p>{{ $author['bio'] }}</p><a class="text-link" href="{{ route('about') }}">Đọc câu chuyện của mình <x-icon name="arrow" size="16" /></a></section>
                @endif
                <section class="rail-section">
                    <span class="overline">CHUYÊN MỤC</span>
                    <div class="category-links">@foreach($categories as $item)<a href="{{ route('posts.index', ['category' => $item['slug']]) }}"><span>{{ $item['name'] }}</span><small>{{ $item['count'] }}</small></a>@endforeach</div>
                </section>
                <section class="rail-section">
                    <span class="overline">GHI CHÉP GẦN ĐÂY</span>
                    <div class="recent-posts">@foreach($allPosts as $item)<a href="{{ route('posts.show', $item['slug']) }}"><strong>{{ $item['title'] }}</strong><span>{{ $item['read_time'] }} phút đọc</span></a>@endforeach</div>
                </section>
                <div class="rail-note"><x-icon name="coffee" size="25" /><p>Một bài viết hay,<br>một tách cà phê.<br><span>Chúc bạn đọc vui.</span></p></div>
            </aside>
        @endunless
    </div>
    <div class="toast" id="toast" role="status" aria-live="polite"></div>
</body>
</html>

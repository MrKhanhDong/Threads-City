<nav class="journal-nav journal-primary-nav" aria-label="Điều hướng chính">
    <a href="{{ route('home') }}" aria-label="Dành cho bạn" @class(['is-active' => request()->routeIs('home', 'threads.index') && request('tab') !== 'following'])><x-journal-icon name="home"/><span>Dành cho bạn</span></a>
    @auth<button type="button" class="stream-nav-compose" data-compose="new" aria-label="Bài viết mới"><x-journal-icon name="plus"/><span>Bài viết mới</span></button>@else<a href="{{ route('login') }}" aria-label="Đăng nhập để đăng bài"><x-journal-icon name="plus"/><span>Bài viết mới</span></a>@endauth
    <a href="{{ route('search.index') }}" aria-label="Tìm kiếm" @class(['is-active' => request()->routeIs('search.index')])><x-journal-icon name="search"/><span>Tìm kiếm</span></a>
    <a href="{{ route('chat.index') }}" aria-label="Chat" @class(['is-active' => request()->routeIs('chat.*')]) @auth data-chat-unread-url="{{ route('chat.unread') }}" @endauth><x-journal-icon name="message"/><span>Chat</span><b class="chat-nav-badge" data-chat-unread hidden></b></a>
    <a href="{{ route('notifications.index') }}" aria-label="Hoạt động" @class(['is-active' => request()->routeIs('activity.*', 'notifications.*')])><x-journal-icon name="heart"/><span>Hoạt động</span></a>
    <a href="{{ auth()->check() ? route('profile.show', auth()->user()->username ?: auth()->id()) : route('login') }}" aria-label="Trang cá nhân" @class(['is-active' => request()->routeIs('profile.show')])><x-journal-icon name="person"/><span>Trang cá nhân</span></a>
</nav>
<section class="journal-extra-feeds" aria-label="Bảng feed khác">
    <div class="journal-feed-settings"><span>Bảng feed khác</span><details class="journal-feed-editor"><summary>Chỉnh sửa</summary><div class="journal-feed-editor-panel"><strong>Hiển thị trong thanh bên</strong>@foreach(['following' => 'Đang theo dõi', 'saved' => 'Đã lưu', 'liked' => 'Đã thích'] as $key => $label)<label><input type="checkbox" data-feed-visibility="{{ $key }}" checked>{{ $label }}</label>@endforeach<small>Lưu trên trình duyệt này.</small></div></details></div>
    <nav class="journal-nav journal-feed-nav" aria-label="Bảng feed cá nhân">
        <a href="{{ route('home', ['tab' => 'following']) }}" aria-label="Đang theo dõi" data-feed-key="following" @class(['is-active' => request()->routeIs('home', 'threads.index') && request('tab') === 'following'])><x-journal-icon name="people"/><span>Đang theo dõi</span></a>
        <a href="{{ route('saved') }}" aria-label="Đã lưu" data-feed-key="saved" @class(['is-active' => request()->routeIs('saved')])><x-journal-icon name="bookmark"/><span>Đã lưu</span></a>
        <a href="{{ auth()->check() ? route('profile.show', ['username' => auth()->user()->username ?: auth()->id(), 'tab' => 'likes']) : route('login') }}" aria-label="Đã thích" data-feed-key="liked" @class(['is-active' => request()->routeIs('liked.*')])><x-journal-icon name="heart"/><span>Đã thích</span></a>
    </nav>
</section>

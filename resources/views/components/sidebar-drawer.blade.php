<aside class="offcanvas offcanvas-start journal-sidebar-drawer" id="journal-sidebar-drawer" tabindex="-1" aria-labelledby="journal-drawer-title" data-sidebar-drawer>
    <div class="journal-drawer-heading">
        <a class="journal-brand" href="{{ route('home') }}" aria-label="Threads — Trang chủ" id="journal-drawer-title"><span class="journal-mark">@</span><span class="journal-brand-copy">{{ config('blog.name') }}</span></a>
        <button type="button" class="journal-menu-button" data-bs-dismiss="offcanvas" aria-label="Đóng thanh bên"><x-journal-icon name="close" :size="24"/></button>
    </div>
    <div class="journal-drawer-body">
        <x-sidebar-navigation/>
        <section class="journal-drawer-settings" aria-label="Cài đặt Threads">
            <h2>Giao diện</h2>
            <x-theme-choices/>
            <nav class="journal-nav" aria-label="Cài đặt và giới thiệu">
                <a href="{{ route('account.edit') }}"><x-journal-icon name="settings"/><span>Cài đặt tài khoản</span></a>
                @auth<a href="{{ route('safety.index') }}"><x-journal-icon name="archive"/><span>Quyền riêng tư và an toàn</span></a>@endauth
                @auth<a href="{{ route('reports.index') }}"><x-journal-icon name="info"/><span>Báo cáo của tôi</span></a>@endauth
                @if(auth()->user()?->isAdmin())<a href="{{ route('admin.index') }}"><x-journal-icon name="chart"/><span>Quản trị</span></a>@endif
                <a href="{{ route('about') }}"><x-journal-icon name="info"/><span>Về Threads</span></a>
                @guest<a href="{{ route('login') }}"><x-journal-icon name="person"/><span>Đăng nhập</span></a>@endguest
            </nav>
            @auth<form method="post" action="{{ route('logout') }}" class="journal-drawer-logout">@csrf<button type="submit">Đăng xuất</button></form>@endauth
        </section>
    </div>
</aside>

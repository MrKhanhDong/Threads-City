@props(['mobile' => false])
<div @class(['dropdown', 'journal-site-menu', 'journal-mobile-menu' => $mobile]) data-site-menu>
    <button class="journal-menu-button" type="button" data-bs-toggle="dropdown" data-bs-auto-close="outside" data-bs-config='{"popperConfig":{"strategy":"fixed"}}' aria-expanded="false" aria-label="Menu Threads"><x-journal-icon name="menu" :size="23"/></button>
    <div @class(['dropdown-menu', 'journal-menu-panel', 'dropdown-menu-end' => $mobile])>
        <div data-menu-main>
            <button class="dropdown-item journal-appearance-link" type="button" data-menu-appearance aria-expanded="false"><span>Giao diện</span><x-journal-icon name="chevron" :size="19"/></button>
            <a class="dropdown-item" href="{{ route('account.edit') }}">Cài đặt tài khoản</a>
            @auth<a class="dropdown-item" href="{{ route('safety.index') }}">Quyền riêng tư và an toàn</a>@endauth
            @auth<a class="dropdown-item" href="{{ route('reports.index') }}">Báo cáo của tôi</a>@endauth
            @if($mobile)
                <details class="journal-menu-feeds"><summary>Bảng feed khác<x-journal-icon name="chevron" :size="17"/></summary><nav aria-label="Bảng feed trong menu">
                    <a class="dropdown-item" href="{{ route('activity.index') }}">Hoạt động</a>
                    <a class="dropdown-item" href="{{ route('insights.index') }}">Thông tin chi tiết</a>
                    <a class="dropdown-item" href="{{ route('home', ['tab' => 'following']) }}">Đang theo dõi</a>
                    <a class="dropdown-item" href="{{ route('saved') }}">Đã lưu</a>
                    <a class="dropdown-item" href="{{ route('liked.index') }}">Đã thích</a>
                    <a class="dropdown-item" href="{{ route('archives.index') }}">Lưu trữ</a>
                </nav></details>
            @endif
            @if(auth()->user()?->isAdmin())<a class="dropdown-item" href="{{ route('admin.index') }}">Quản trị</a>@endif
            <a class="dropdown-item" href="{{ route('about') }}">Về Threads</a>
            <div class="journal-menu-footer">
                @auth<form method="post" action="{{ route('logout') }}">@csrf<button class="dropdown-item journal-menu-logout" type="submit">Đăng xuất</button></form>
                @else<a class="dropdown-item" href="{{ route('login') }}">Đăng nhập</a>@endauth
            </div>
        </div>
        <div class="journal-appearance-panel" data-menu-theme hidden>
            <div class="journal-appearance-heading"><button type="button" class="journal-menu-back" data-menu-back aria-label="Quay lại menu"><x-journal-icon name="arrow-left" :size="24"/></button><h2>Giao diện</h2><span></span></div>
            <x-theme-choices/>
        </div>
    </div>
</div>

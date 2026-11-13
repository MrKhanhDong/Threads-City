@php
    $columnTabs = [
        'home' => ['label' => 'Dành cho bạn', 'url' => route('home')],
        'chat' => ['label' => 'Chat', 'url' => route('chat.index')],
        'search' => ['label' => 'Tìm kiếm', 'url' => route('search.index')],
        'activity' => ['label' => 'Hoạt động', 'url' => route('notifications.index')],
        'profile' => ['label' => 'Trang cá nhân', 'url' => auth()->check() ? route('profile.show', auth()->user()->username ?: auth()->id()) : route('login')],
        'following' => ['label' => 'Đang theo dõi', 'url' => route('home', ['tab' => 'following'])],
        'saved' => ['label' => 'Đã lưu', 'url' => route('saved')],
        'liked' => ['label' => 'Đã thích', 'url' => auth()->check() ? route('profile.show', ['username' => auth()->user()->username ?: auth()->id(), 'tab' => 'likes']) : route('login')],
    ];
@endphp
@foreach(['secondary', 'tertiary'] as $columnSlot)
<section class="desktop-secondary-column" data-column-slot="{{ $columnSlot }}" aria-label="Cột bổ sung" hidden>
    <header class="desktop-column-heading" data-column-drag="{{ $columnSlot }}" tabindex="-1" aria-describedby="desktop-column-drag-help">
        <span class="desktop-column-title" data-column-title>Cột bổ sung</span>
        <div class="dropdown">
            <button class="journal-menu-button" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Tùy chọn cột"><x-journal-icon name="more"/></button>
            <div class="dropdown-menu dropdown-menu-end">
                <label class="desktop-column-menu-label" for="column-tab-{{ $columnSlot }}">Nội dung cột</label>
                <select id="column-tab-{{ $columnSlot }}" class="desktop-column-select" data-column-select aria-label="Đổi nội dung cột">@foreach($columnTabs as $key => $column)<option value="{{ $key }}">{{ $column['label'] }}</option>@endforeach</select>
                <div class="dropdown-divider"></div>
                <button class="dropdown-item" type="button" data-column-close>Gỡ cột <x-journal-icon name="close" :size="17"/></button>
            </div>
        </div>
    </header>
    <div class="desktop-column-surface">
        <p class="desktop-column-status" role="status" data-column-status hidden>Đang tải nội dung…</p>
        <iframe title="Nội dung cột bổ sung" data-desktop-column-frame src="about:blank" allow="autoplay; fullscreen" allowfullscreen></iframe>
    </div>
    <div class="desktop-column-shield" data-column-shield></div>
</section>
@endforeach
<div class="desktop-column-rail">
<div class="dropdown dropstart desktop-column-control" data-column-control>
    <button class="journal-menu-button desktop-column-add" type="button" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false" aria-label="Thêm cột" title="Thêm cột"><x-journal-icon name="add-column" :size="25"/></button>
    <span class="desktop-column-count" data-column-count-label aria-hidden="true">1/3</span>
    <div class="dropdown-menu dropdown-menu-end desktop-column-menu">
        <span class="desktop-column-menu-label">Thêm cột</span>
        <span class="desktop-column-limit" data-column-limit role="status" hidden>Đã mở tối đa 3 cột. Gỡ một cột để mở thêm.</span>
        @foreach(array_slice($columnTabs, 0, 4, true) as $key => $column)<button class="dropdown-item" type="button" data-column-choice="{{ $key }}" data-column-url="{{ $column['url'] }}">{{ $column['label'] }}</button>@endforeach
        <details data-column-feed-group><summary>Bảng feed khác <x-journal-icon name="chevron" :size="17"/></summary>@foreach(array_slice($columnTabs, 4, null, true) as $key => $column)<button class="dropdown-item" type="button" data-column-choice="{{ $key }}" data-column-url="{{ $column['url'] }}">{{ $column['label'] }}</button>@endforeach</details>
    </div>
</div>
</div>
<span class="visually-hidden" id="desktop-column-drag-help">Giữ và kéo phần tiêu đề để sắp xếp cột. Khi tiêu đề được chọn bằng bàn phím, dùng mũi tên trái và phải để di chuyển.</span>
<span class="visually-hidden" data-column-announcement aria-live="polite"></span>

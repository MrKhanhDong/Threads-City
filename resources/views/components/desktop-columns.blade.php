@php
    $columnTabs = [
        'home' => ['label' => 'Dành cho bạn', 'url' => route('home')],
        'search' => ['label' => 'Tìm kiếm', 'url' => route('search.index')],
        'activity' => ['label' => 'Hoạt động', 'url' => route('activity.index')],
        'profile' => ['label' => 'Trang cá nhân', 'url' => auth()->check() ? route('profile.show', auth()->user()->username ?: auth()->id()) : route('login')],
        'insights' => ['label' => 'Thông tin chi tiết', 'url' => route('insights.index')],
        'following' => ['label' => 'Đang theo dõi', 'url' => route('home', ['tab' => 'following'])],
        'saved' => ['label' => 'Đã lưu', 'url' => route('saved')],
        'liked' => ['label' => 'Đã thích', 'url' => route('liked.index')],
        'archives' => ['label' => 'Lưu trữ', 'url' => route('archives.index')],
    ];
@endphp
<section class="desktop-secondary-column" data-column-slot="secondary" aria-label="Cột bổ sung" hidden>
    <header class="desktop-column-heading">
        <button class="journal-menu-button desktop-column-grip" type="button" draggable="true" data-column-drag="secondary" aria-label="Đổi vị trí hai cột" title="Kéo để đổi vị trí cột"><x-journal-icon name="grip"/></button>
        <span class="visually-hidden" data-column-title>Cột bổ sung</span>
        <select class="desktop-column-select" data-column-select aria-label="Đổi nội dung cột">@foreach($columnTabs as $key => $column)<option value="{{ $key }}">{{ $column['label'] }}</option>@endforeach</select>
        <div class="dropdown"><button class="journal-menu-button" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Tùy chọn cột"><x-journal-icon name="more"/></button><div class="dropdown-menu dropdown-menu-end"><button class="dropdown-item" type="button" data-column-close>Gỡ cột <x-journal-icon name="close" :size="17"/></button></div></div>
    </header>
    <div class="desktop-column-surface">
        <p class="desktop-column-status" role="status" data-column-status hidden>Đang tải nội dung…</p>
        <iframe title="Nội dung cột bổ sung" data-desktop-column-frame src="about:blank"></iframe>
    </div>
    <div class="desktop-column-shield" data-column-shield></div>
</section>
<div class="dropdown dropstart desktop-column-control" data-column-control>
    <button class="journal-menu-button desktop-column-add" type="button" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false" aria-label="Thêm cột" title="Thêm cột"><x-journal-icon name="add-column" :size="25"/></button>
    <div class="dropdown-menu dropdown-menu-end desktop-column-menu">
        <span class="desktop-column-menu-label">Thêm cột</span>
        @foreach(array_slice($columnTabs, 0, 5, true) as $key => $column)<button class="dropdown-item" type="button" data-column-choice="{{ $key }}" data-column-url="{{ $column['url'] }}">{{ $column['label'] }}</button>@endforeach
        <details data-column-feed-group><summary>Bảng feed khác <x-journal-icon name="chevron" :size="17"/></summary>@foreach(array_slice($columnTabs, 5, null, true) as $key => $column)<button class="dropdown-item" type="button" data-column-choice="{{ $key }}" data-column-url="{{ $column['url'] }}">{{ $column['label'] }}</button>@endforeach</details>
    </div>
</div>
<span class="visually-hidden" data-column-announcement aria-live="polite"></span>

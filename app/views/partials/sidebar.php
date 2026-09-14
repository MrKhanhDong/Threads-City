<?php

declare(strict_types=1);

$navItems = [
    ['page' => 'home', 'label' => 'Trang chủ', 'icon' => 'bi-house-door-fill'],
    ['page' => 'search', 'label' => 'Tìm kiếm', 'icon' => 'bi-search'],
    ['page' => 'categories', 'label' => 'Chủ đề', 'icon' => 'bi-list-ul'],
    ['page' => 'saved', 'label' => 'Đã lưu', 'icon' => 'bi-bookmark'],
];
?>
<aside class="sidebar" aria-label="Điều hướng chính">
    <div class="sidebar__inner">
        <a class="brand" href="<?= e(url('home')) ?>" aria-label="Personal Blog - Trang chủ">
            <span class="brand__mark">P</span>
            <span>Personal Blog</span>
        </a>

        <nav class="primary-nav">
            <?php foreach ($navItems as $item): ?>
                <a class="nav-item<?= nav_is_active($page, $item['page']) ? ' is-active' : '' ?>"
                   href="<?= e(url($item['page'])) ?>">
                    <i class="bi <?= e($item['icon']) ?>" aria-hidden="true"></i>
                    <span><?= e($item['label']) ?></span>
                </a>
            <?php endforeach; ?>

            <button class="nav-item nav-item--button" type="button" data-open-modal="create-post-modal">
                <i class="bi bi-plus-lg" aria-hidden="true"></i>
                <span>Tạo bài viết</span>
            </button>

            <a class="nav-item<?= nav_is_active($page, 'profile') ? ' is-active' : '' ?>"
               href="<?= e(url('profile')) ?>">
                <i class="bi bi-person" aria-hidden="true"></i>
                <span>Trang cá nhân</span>
            </a>
        </nav>

        <div class="sidebar__footer">
            <a class="nav-item<?= nav_is_active($page, 'admin') ? ' is-active' : '' ?>"
               href="<?= e(url('admin')) ?>">
                <i class="bi bi-gear" aria-hidden="true"></i>
                <span>Khu quản trị</span>
            </a>
            <button class="nav-item nav-item--button nav-item--danger" type="button" data-action="logout">
                <i class="bi bi-box-arrow-right" aria-hidden="true"></i>
                <span>Đăng xuất</span>
            </button>
        </div>
    </div>
</aside>


<?php

declare(strict_types=1);

$adminStats = [
    ['label' => 'Tổng bài viết', 'value' => '1,284', 'trend' => '+12.5%', 'icon' => 'bi-file-earmark-text'],
    ['label' => 'Tổng người dùng', 'value' => '8,429', 'trend' => '+8.2%', 'icon' => 'bi-people'],
    ['label' => 'Tổng bình luận', 'value' => '24,680', 'trend' => '+16.4%', 'icon' => 'bi-chat-left-text'],
    ['label' => 'Tổng lượt thích', 'value' => '92,410', 'trend' => '+21.8%', 'icon' => 'bi-heart'],
];
?>
<header class="page-header page-header--admin">
    <div>
        <span class="eyebrow">Hệ thống quản trị</span>
        <h1>Tổng quan</h1>
    </div>
    <div class="admin-profile">
        <span>
            <strong><?= e($currentUser['name']) ?></strong>
            <small>Quản trị viên</small>
        </span>
        <span class="avatar avatar--small <?= e($currentUser['avatar_class']) ?>"><?= e(initials($currentUser['name'])) ?></span>
    </div>
</header>

<nav class="admin-tabs" aria-label="Các mục quản trị" data-admin-tabs>
    <button class="is-active" type="button" data-admin-tab="overview"><i class="bi bi-grid"></i>Tổng quan</button>
    <button type="button" data-admin-tab="posts"><i class="bi bi-file-earmark-text"></i>Bài viết</button>
    <button type="button" data-admin-tab="categories"><i class="bi bi-folder"></i>Chủ đề</button>
    <button type="button" data-admin-tab="tags"><i class="bi bi-hash"></i>Thẻ</button>
    <button type="button" data-admin-tab="comments"><i class="bi bi-chat-left-text"></i>Bình luận</button>
    <button type="button" data-admin-tab="users"><i class="bi bi-people"></i>Người dùng</button>
</nav>

<section class="admin-panel is-active" data-admin-panel="overview">
    <div class="stats-grid">
        <?php foreach ($adminStats as $stat): ?>
            <article class="stat-card">
                <div class="stat-card__top">
                    <span><?= e($stat['label']) ?></span>
                    <i class="bi <?= e($stat['icon']) ?>"></i>
                </div>
                <strong><?= e($stat['value']) ?></strong>
                <small><b><?= e($stat['trend']) ?></b> so với tháng trước</small>
            </article>
        <?php endforeach; ?>
    </div>

    <div class="admin-grid">
        <section class="admin-card chart-card">
            <div class="admin-card__header">
                <div>
                    <h2>Lượt tương tác</h2>
                    <p>Thống kê 7 tháng gần nhất</p>
                </div>
                <select aria-label="Khoảng thời gian"><option>7 tháng</option><option>12 tháng</option></select>
            </div>
            <div class="chart" aria-label="Biểu đồ lượt tương tác theo tháng">
                <?php foreach ([38, 52, 44, 68, 61, 82, 94] as $index => $height): ?>
                    <div class="chart__column">
                        <span class="chart__bar" style="height: <?= e($height) ?>%"><b><?= e($height * 120) ?></b></span>
                        <small>T<?= e($index + 3) ?></small>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="admin-card">
            <div class="admin-card__header">
                <div>
                    <h2>Chủ đề nổi bật</h2>
                    <p>Theo số bài viết</p>
                </div>
            </div>
            <div class="progress-list">
                <?php foreach (array_slice($categories, 0, 4) as $index => $category): ?>
                    <div>
                        <span><b><?= e($category['name']) ?></b><small><?= e($category['count']) ?></small></span>
                        <i><b style="width: <?= e(92 - ($index * 15)) ?>%"></b></i>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    </div>

    <section class="admin-card">
        <div class="admin-card__header">
            <div><h2>Bài viết gần đây</h2><p>Nội dung mới cập nhật trên hệ thống</p></div>
            <button class="text-button" type="button" data-admin-go="posts">Xem tất cả <i class="bi bi-arrow-right"></i></button>
        </div>
        <?php $adminTableType = 'posts'; require dirname(__DIR__) . '/partials/admin_table.php'; ?>
    </section>
</section>

<?php foreach (['posts' => 'Quản lý bài viết', 'categories' => 'Quản lý chủ đề', 'tags' => 'Quản lý thẻ', 'comments' => 'Quản lý bình luận', 'users' => 'Quản lý người dùng'] as $panelKey => $panelTitle): ?>
    <section class="admin-panel" data-admin-panel="<?= e($panelKey) ?>" hidden>
        <div class="admin-section-header">
            <div>
                <h2><?= e($panelTitle) ?></h2>
                <p>Thêm, chỉnh sửa và kiểm soát dữ liệu hiển thị trên hệ thống.</p>
            </div>
            <?php if (!in_array($panelKey, ['comments', 'users'], true)): ?>
                <button class="button button--light" type="button" data-action="admin-add"><i class="bi bi-plus-lg"></i> Thêm mới</button>
            <?php endif; ?>
        </div>
        <div class="admin-card admin-card--table">
            <div class="table-toolbar">
                <label><i class="bi bi-search"></i><input type="search" placeholder="Tìm trong danh sách..." data-table-search></label>
                <select aria-label="Lọc trạng thái"><option>Tất cả trạng thái</option><option>Đã xuất bản</option><option>Bản nháp</option></select>
            </div>
            <?php $adminTableType = $panelKey; require dirname(__DIR__) . '/partials/admin_table.php'; ?>
        </div>
    </section>
<?php endforeach; ?>

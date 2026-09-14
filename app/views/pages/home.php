<?php

declare(strict_types=1);
?>
<header class="page-header page-header--home">
    <h1>Trang chủ</h1>
    <div class="segmented-control" data-feed-tabs>
        <button class="is-active" type="button" data-feed-tab="for-you">Dành cho bạn</button>
        <button type="button" data-feed-tab="latest">Mới nhất</button>
    </div>
</header>

<section class="composer" aria-label="Tạo bài viết nhanh">
    <span class="avatar <?= e($currentUser['avatar_class']) ?>"><?= e(initials($currentUser['name'])) ?></span>
    <button class="composer__prompt" type="button" data-open-modal="create-post-modal">Bắt đầu một bài viết hoặc blog thread...</button>
    <button class="button button--light button--small" type="button" data-open-modal="create-post-modal">Đăng</button>
</section>

<section class="feed" id="post-feed" aria-label="Danh sách bài viết">
    <?php foreach ($posts as $post): ?>
        <?php require dirname(__DIR__) . '/partials/post_card.php'; ?>
    <?php endforeach; ?>
</section>


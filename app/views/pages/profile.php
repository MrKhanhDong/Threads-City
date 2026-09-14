<?php

declare(strict_types=1);

$profilePosts = array_values(array_filter($posts, static fn (array $post): bool => $post['author'] === 'Duy Khang'));
$likedPosts = array_values(array_filter($posts, static fn (array $post): bool => $post['liked']));
$savedPosts = array_values(array_filter($posts, static fn (array $post): bool => $post['saved']));
?>
<header class="page-header">
    <h1>Trang cá nhân</h1>
    <button class="icon-button" type="button" aria-label="Tùy chọn hồ sơ"><i class="bi bi-three-dots"></i></button>
</header>

<section class="profile-card">
    <div class="profile-card__top">
        <div>
            <h2><?= e($currentUser['name']) ?></h2>
            <p><?= e($currentUser['handle']) ?></p>
        </div>
        <span class="avatar avatar--profile <?= e($currentUser['avatar_class']) ?>"><?= e(initials($currentUser['name'])) ?></span>
    </div>
    <p class="profile-card__bio"><?= e($currentUser['bio']) ?></p>
    <a class="profile-card__website" href="#"><i class="bi bi-link-45deg"></i><?= e($currentUser['website']) ?></a>
    <div class="profile-card__stats">
        <span><strong><?= e($currentUser['posts_count']) ?></strong> bài viết</span>
        <span><strong><?= e(number_format($currentUser['followers_count'])) ?></strong> người theo dõi</span>
        <span><strong><?= e($currentUser['following_count']) ?></strong> đang theo dõi</span>
    </div>
    <button class="button button--outline button--block" type="button" data-open-modal="edit-profile-modal">Chỉnh sửa hồ sơ</button>
</section>

<div class="profile-tabs" data-profile-tabs>
    <button class="is-active" type="button" data-profile-tab="posts">Bài viết</button>
    <button type="button" data-profile-tab="liked">Đã thích</button>
    <button type="button" data-profile-tab="saved">Đã lưu</button>
</div>

<section class="profile-tab-panel is-active" data-profile-panel="posts">
    <?php foreach ($profilePosts as $post): ?>
        <?php $compact = true; ?>
        <?php require dirname(__DIR__) . '/partials/post_card.php'; ?>
    <?php endforeach; ?>
</section>
<section class="profile-tab-panel" data-profile-panel="liked" hidden>
    <?php foreach ($likedPosts as $post): ?>
        <?php $compact = true; ?>
        <?php require dirname(__DIR__) . '/partials/post_card.php'; ?>
    <?php endforeach; ?>
</section>
<section class="profile-tab-panel" data-profile-panel="saved" hidden>
    <?php foreach ($savedPosts as $post): ?>
        <?php $compact = true; ?>
        <?php require dirname(__DIR__) . '/partials/post_card.php'; ?>
    <?php endforeach; ?>
</section>

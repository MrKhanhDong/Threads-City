<?php

declare(strict_types=1);

$savedPosts = array_values(array_filter($posts, static fn (array $post): bool => $post['saved']));
?>
<header class="page-header">
    <div>
        <span class="eyebrow">Thư viện cá nhân</span>
        <h1>Đã lưu</h1>
    </div>
    <span class="header-count"><?= e(count($savedPosts)) ?> bài viết</span>
</header>

<section class="feed">
    <?php foreach ($savedPosts as $post): ?>
        <?php require dirname(__DIR__) . '/partials/post_card.php'; ?>
    <?php endforeach; ?>
</section>


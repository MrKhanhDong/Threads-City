<?php

declare(strict_types=1);

$selectedSlug = strtolower((string) ($_GET['category'] ?? $categories[1]['slug']));
$selectedCategory = $categories[1];

foreach ($categories as $category) {
    if ($category['slug'] === $selectedSlug) {
        $selectedCategory = $category;
        break;
    }
}

$categoryPosts = array_values(array_filter(
    $posts,
    static fn (array $post): bool => strtolower($post['category']) === $selectedCategory['slug']
));
?>
<header class="page-header">
    <div>
        <span class="eyebrow">Khám phá theo lĩnh vực</span>
        <h1>Chủ đề</h1>
    </div>
</header>

<section class="category-browser">
    <?php foreach ($categories as $category): ?>
        <a class="category-tile<?= $category['slug'] === $selectedCategory['slug'] ? ' is-active' : '' ?>"
           href="<?= e(url('categories', ['category' => $category['slug']])) ?>">
            <span class="category-tile__icon"><i class="bi <?= match ($category['slug']) {
                'technology' => 'bi-cpu',
                'programming' => 'bi-code-slash',
                'lifestyle' => 'bi-cup-hot',
                'travel' => 'bi-compass',
                default => 'bi-mortarboard',
            } ?>"></i></span>
            <strong><?= e($category['name']) ?></strong>
            <span><?= e($category['count']) ?> bài viết</span>
        </a>
    <?php endforeach; ?>
</section>

<div class="section-heading">
    <h2><?= e($selectedCategory['name']) ?></h2>
    <span><?= e(count($categoryPosts)) ?> bài viết đang hiển thị</span>
</div>

<section class="feed feed--compact">
    <?php if ($categoryPosts): ?>
        <?php foreach ($categoryPosts as $post): ?>
            <?php $compact = true; ?>
            <?php require dirname(__DIR__) . '/partials/post_card.php'; ?>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="empty-state">
            <i class="bi bi-folder2-open"></i>
            <h2>Chưa có bài viết</h2>
            <p>Chủ đề này đang chờ bài viết đầu tiên.</p>
        </div>
    <?php endif; ?>
</section>


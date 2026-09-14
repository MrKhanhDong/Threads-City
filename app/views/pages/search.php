<?php

declare(strict_types=1);

$query = trim((string) ($_GET['q'] ?? ''));
$filter = strtolower(trim((string) ($_GET['category'] ?? 'all')));
$searchResults = array_values(array_filter($posts, static function (array $post) use ($query, $filter): bool {
    $matchesCategory = $filter === 'all' || strtolower($post['category']) === $filter;
    $haystack = $post['title'] . ' ' . $post['excerpt'] . ' ' . implode(' ', $post['tags']);
    $matchesQuery = $query === '' || stripos($haystack, ltrim($query, '#')) !== false;

    return $matchesCategory && $matchesQuery;
}));
?>
<header class="page-header">
    <div>
        <span class="eyebrow">Khám phá nội dung</span>
        <h1>Tìm kiếm</h1>
    </div>
</header>

<section class="search-panel">
    <form class="search-box" action="index.php" method="get">
        <input type="hidden" name="page" value="search">
        <i class="bi bi-search" aria-hidden="true"></i>
        <input type="search" name="q" value="<?= e($query) ?>" placeholder="Tìm bài viết, chủ đề hoặc tác giả..." autofocus>
        <button class="button button--light" type="submit">Tìm kiếm</button>
    </form>
    <div class="filter-pills" aria-label="Lọc theo chủ đề">
        <a class="<?= $filter === 'all' ? 'is-active' : '' ?>" href="<?= e(url('search', ['q' => $query])) ?>">Tất cả</a>
        <?php foreach ($categories as $category): ?>
            <a class="<?= $filter === $category['slug'] ? 'is-active' : '' ?>"
               href="<?= e(url('search', ['q' => $query, 'category' => $category['slug']])) ?>">
                <?= e($category['name']) ?>
            </a>
        <?php endforeach; ?>
    </div>
</section>

<div class="section-heading">
    <h2><?= $query === '' ? 'Bài viết nổi bật' : 'Kết quả cho “' . e($query) . '”' ?></h2>
    <span><?= e(count($searchResults)) ?> kết quả</span>
</div>

<section class="feed feed--compact">
    <?php if ($searchResults): ?>
        <?php foreach ($searchResults as $post): ?>
            <?php $compact = true; ?>
            <?php require dirname(__DIR__) . '/partials/post_card.php'; ?>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="empty-state">
            <i class="bi bi-search"></i>
            <h2>Chưa tìm thấy kết quả</h2>
            <p>Thử một từ khóa ngắn hơn hoặc chọn chủ đề khác.</p>
        </div>
    <?php endif; ?>
</section>

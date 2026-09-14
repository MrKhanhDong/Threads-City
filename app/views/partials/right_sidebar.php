<?php

declare(strict_types=1);
?>
<aside class="right-rail" aria-label="Khám phá">
    <div class="right-rail__inner">
        <form class="rail-search" action="index.php" method="get">
            <input type="hidden" name="page" value="search">
            <i class="bi bi-search" aria-hidden="true"></i>
            <input type="search" name="q" value="<?= e($_GET['q'] ?? '') ?>" placeholder="Tìm kiếm bài viết..." aria-label="Tìm kiếm bài viết">
        </form>

        <section class="rail-card">
            <h2 class="rail-card__title">Chủ đề phổ biến</h2>
            <div class="category-list">
                <?php foreach ($categories as $category): ?>
                    <a href="<?= e(url('categories', ['category' => $category['slug']])) ?>">
                        <span><?= e($category['name']) ?></span>
                        <span class="count-pill"><?= e($category['count']) ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="rail-card">
            <h2 class="rail-card__title">Thẻ phổ biến</h2>
            <div class="tag-cloud">
                <?php foreach ($tags as $tag): ?>
                    <a href="<?= e(url('search', ['q' => $tag])) ?>"><?= e($tag) ?></a>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="rail-card">
            <h2 class="rail-card__title">Tác giả gợi ý</h2>
            <div class="people-list">
                <?php foreach ($suggestedAuthors as $author): ?>
                    <div class="person-row">
                        <span class="avatar avatar--small <?= e($author['avatar_class']) ?>"><?= e(initials($author['name'])) ?></span>
                        <span class="person-row__info">
                            <strong><?= e($author['name']) ?></strong>
                            <small><?= e($author['handle']) ?></small>
                        </span>
                        <button class="follow-button" type="button" data-action="follow">Theo dõi</button>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    </div>
</aside>

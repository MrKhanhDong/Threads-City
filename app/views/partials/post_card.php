<?php

declare(strict_types=1);

$compact = $compact ?? false;
?>
<article class="post-card<?= $compact ? ' post-card--compact' : '' ?>" data-post-id="<?= e($post['id']) ?>">
    <div class="post-card__timeline" aria-hidden="true"></div>
    <div class="post-card__avatar">
        <span class="avatar <?= e($post['avatar_class']) ?>"><?= e(initials($post['author'])) ?></span>
    </div>

    <div class="post-card__body">
        <div class="post-card__meta">
            <div class="author-line">
                <strong><?= e($post['author']) ?></strong>
                <span><?= e($post['handle']) ?></span>
                <span>·</span>
                <time><?= e($post['time']) ?></time>
            </div>
            <a class="category-pill" href="<?= e(url('categories', ['category' => strtolower($post['category'])])) ?>">
                <?= e($post['category']) ?>
            </a>
        </div>

        <a class="post-card__link" href="<?= e(url('post', ['id' => $post['id']])) ?>">
            <h2><?= e($post['title']) ?></h2>
            <p><?= e($post['excerpt']) ?></p>
            <?php if (!$compact && $post['image']): ?>
                <img class="post-card__image" src="<?= e($post['image']) ?>" alt="Ảnh minh họa cho bài viết <?= e($post['title']) ?>">
            <?php endif; ?>
        </a>

        <div class="post-actions" aria-label="Tương tác bài viết">
            <button class="post-action<?= $post['liked'] ? ' is-active' : '' ?>" type="button" data-action="like" aria-label="Thích bài viết">
                <i class="bi <?= $post['liked'] ? 'bi-heart-fill' : 'bi-heart' ?>" aria-hidden="true"></i>
                <span><?= e($post['likes']) ?></span>
            </button>
            <a class="post-action" href="<?= e(url('post', ['id' => $post['id'], 'focus' => 'comments'])) ?>" aria-label="Xem bình luận">
                <i class="bi bi-chat" aria-hidden="true"></i>
                <span><?= e($post['comments_count']) ?></span>
            </a>
            <button class="post-action" type="button" data-action="share" aria-label="Chia sẻ bài viết">
                <i class="bi bi-share" aria-hidden="true"></i>
            </button>
            <button class="post-action post-action--save<?= $post['saved'] ? ' is-active' : '' ?>" type="button" data-action="save" aria-label="Lưu bài viết">
                <i class="bi <?= $post['saved'] ? 'bi-bookmark-fill' : 'bi-bookmark' ?>" aria-hidden="true"></i>
            </button>
        </div>
    </div>
</article>

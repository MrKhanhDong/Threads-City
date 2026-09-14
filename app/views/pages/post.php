<?php

declare(strict_types=1);

$postId = max(1, (int) ($_GET['id'] ?? 1));
$post = find_post($posts, $postId);

if ($post === null) {
    http_response_code(404);
}

$postComments = $post ? ($comments[$postId] ?? []) : [];
?>
<?php if ($post === null): ?>
    <header class="page-header">
        <a class="back-button" href="<?= e(url('home')) ?>"><i class="bi bi-arrow-left"></i></a>
        <h1>Không tìm thấy bài viết</h1>
    </header>
    <div class="empty-state empty-state--page">
        <i class="bi bi-file-earmark-x"></i>
        <h2>Bài viết không tồn tại</h2>
        <p>Nội dung có thể đã được xóa hoặc đường dẫn chưa chính xác.</p>
        <a class="button button--light" href="<?= e(url('home')) ?>">Về trang chủ</a>
    </div>
<?php else: ?>
    <header class="page-header page-header--detail">
        <a class="back-button" href="<?= e(url('home')) ?>" aria-label="Quay lại"><i class="bi bi-arrow-left"></i></a>
        <h1>Chi tiết bài viết</h1>
        <button class="icon-button" type="button" aria-label="Tùy chọn"><i class="bi bi-three-dots"></i></button>
    </header>

    <article class="post-detail" data-post-id="<?= e($post['id']) ?>">
        <div class="post-detail__author">
            <span class="avatar <?= e($post['avatar_class']) ?>"><?= e(initials($post['author'])) ?></span>
            <div>
                <strong><?= e($post['author']) ?></strong>
                <span><?= e($post['handle']) ?> · <?= e($post['time']) ?></span>
            </div>
            <button class="follow-button follow-button--outline" type="button" data-action="follow">Theo dõi</button>
        </div>

        <a class="category-pill" href="<?= e(url('categories', ['category' => strtolower($post['category'])])) ?>"><?= e($post['category']) ?></a>
        <h2><?= e($post['title']) ?></h2>
        <p class="post-detail__lead"><?= e($post['excerpt']) ?></p>

        <?php if ($post['image']): ?>
            <img class="post-detail__image" src="<?= e($post['image']) ?>" alt="Ảnh minh họa cho <?= e($post['title']) ?>">
        <?php endif; ?>

        <div class="post-detail__content">
            <?php foreach ($post['content'] as $paragraph): ?>
                <p><?= e($paragraph) ?></p>
            <?php endforeach; ?>
        </div>

        <div class="tag-cloud tag-cloud--detail">
            <?php foreach ($post['tags'] as $tag): ?>
                <a href="<?= e(url('search', ['q' => '#' . $tag])) ?>">#<?= e($tag) ?></a>
            <?php endforeach; ?>
        </div>

        <div class="post-actions post-actions--detail">
            <button class="post-action<?= $post['liked'] ? ' is-active' : '' ?>" type="button" data-action="like">
                <i class="bi <?= $post['liked'] ? 'bi-heart-fill' : 'bi-heart' ?>"></i><span><?= e($post['likes']) ?> lượt thích</span>
            </button>
            <a class="post-action" href="#comments"><i class="bi bi-chat"></i><span><?= e($post['comments_count']) ?> bình luận</span></a>
            <button class="post-action" type="button" data-action="share"><i class="bi bi-share"></i><span>Chia sẻ</span></button>
            <button class="post-action post-action--save<?= $post['saved'] ? ' is-active' : '' ?>" type="button" data-action="save">
                <i class="bi <?= $post['saved'] ? 'bi-bookmark-fill' : 'bi-bookmark' ?>"></i><span>Lưu</span>
            </button>
        </div>
    </article>

    <section class="comments" id="comments">
        <div class="section-heading section-heading--comments">
            <h2>Bình luận</h2>
            <span><?= e($post['comments_count']) ?> phản hồi</span>
        </div>

        <form class="comment-composer" data-form="comment">
            <span class="avatar <?= e($currentUser['avatar_class']) ?>"><?= e(initials($currentUser['name'])) ?></span>
            <textarea name="comment" rows="2" placeholder="Viết bình luận của bạn..." required></textarea>
            <button class="button button--light button--small" type="submit">Gửi</button>
        </form>

        <div class="comment-list">
            <?php foreach ($postComments as $comment): ?>
                <article class="comment" data-comment-id="<?= e($comment['id']) ?>">
                    <span class="avatar avatar--small <?= e($comment['avatar_class']) ?>"><?= e(initials($comment['author'])) ?></span>
                    <div class="comment__body">
                        <div class="author-line">
                            <strong><?= e($comment['author']) ?></strong>
                            <span><?= e($comment['handle']) ?> · <?= e($comment['time']) ?></span>
                        </div>
                        <p><?= e($comment['body']) ?></p>
                        <div class="comment__actions">
                            <button type="button" data-action="comment-like"><i class="bi bi-heart"></i> <?= e($comment['likes']) ?></button>
                            <button type="button" data-action="reply">Trả lời</button>
                            <button type="button" data-action="delete-comment">Xóa</button>
                        </div>

                        <?php foreach ($comment['replies'] as $reply): ?>
                            <article class="comment comment--reply" data-comment-id="<?= e($reply['id']) ?>">
                                <span class="avatar avatar--small <?= e($reply['avatar_class']) ?>"><?= e(initials($reply['author'])) ?></span>
                                <div class="comment__body">
                                    <div class="author-line">
                                        <strong><?= e($reply['author']) ?></strong>
                                        <span><?= e($reply['handle']) ?> · <?= e($reply['time']) ?></span>
                                    </div>
                                    <p><?= e($reply['body']) ?></p>
                                    <div class="comment__actions">
                                        <button type="button" data-action="comment-like"><i class="bi bi-heart"></i> <?= e($reply['likes']) ?></button>
                                        <button type="button" data-action="reply">Trả lời</button>
                                    </div>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </section>
<?php endif; ?>


<?php

declare(strict_types=1);
?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Personal Blog - giao diện blog cộng đồng phong cách Threads">
    <title><?= e($pageTitle) ?> · Personal Blog</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="public/css/style.css">
</head>
<body data-page="<?= e($page) ?>" data-authenticated="true">
    <div class="app-shell<?= $isWidePage ? ' app-shell--wide' : '' ?>">
        <?php require __DIR__ . '/partials/sidebar.php'; ?>

        <main class="content-column<?= $isWidePage ? ' content-column--wide' : '' ?>">
            <?= $content ?>
        </main>

        <?php if ($showRightRail): ?>
            <?php require __DIR__ . '/partials/right_sidebar.php'; ?>
        <?php endif; ?>
    </div>

    <?php require __DIR__ . '/partials/modals.php'; ?>

    <div class="toast" id="toast" role="status" aria-live="polite"></div>
    <script src="public/js/app.js"></script>
</body>
</html>

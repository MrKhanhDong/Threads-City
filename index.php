<?php

declare(strict_types=1);

require __DIR__ . '/app/helpers.php';
require __DIR__ . '/app/data/mock_data.php';

$allowedPages = ['home', 'search', 'categories', 'saved', 'profile', 'post', 'admin'];
$page = (string) ($_GET['page'] ?? 'home');

if (!in_array($page, $allowedPages, true)) {
    http_response_code(404);
    $page = 'home';
}

$pageTitles = [
    'home' => 'Trang chủ',
    'search' => 'Tìm kiếm',
    'categories' => 'Chủ đề',
    'saved' => 'Đã lưu',
    'profile' => 'Trang cá nhân',
    'post' => 'Chi tiết bài viết',
    'admin' => 'Quản trị',
];

$pageTitle = $pageTitles[$page];
$showRightRail = $page !== 'admin';
$isWidePage = $page === 'admin';

ob_start();
require __DIR__ . '/app/views/pages/' . $page . '.php';
$content = (string) ob_get_clean();

require __DIR__ . '/app/views/layout.php';


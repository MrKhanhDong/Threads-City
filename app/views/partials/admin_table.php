<?php

declare(strict_types=1);

$adminTableType = $adminTableType ?? 'posts';

$tableHeaders = [];
$tableRows = [];

if ($adminTableType === 'posts') {
    $tableHeaders = ['Bài viết', 'Tác giả', 'Chủ đề', 'Trạng thái', 'Tương tác', ''];
    foreach ($posts as $item) {
        $tableRows[] = [
            '<span class="table-title">' . e($item['title']) . '</span>',
            e($item['author']),
            '<span class="table-chip">' . e($item['category']) . '</span>',
            '<span class="status status--success">Đã xuất bản</span>',
            e($item['likes'] + $item['comments_count']),
            '<span class="row-actions"><button type="button" data-action="admin-edit" aria-label="Sửa"><i class="bi bi-pencil"></i></button><button type="button" data-action="admin-delete" aria-label="Xóa"><i class="bi bi-trash"></i></button></span>',
        ];
    }
} elseif ($adminTableType === 'categories') {
    $tableHeaders = ['Tên chủ đề', 'Đường dẫn', 'Số bài viết', 'Cập nhật', ''];
    foreach ($categories as $item) {
        $tableRows[] = [
            '<span class="table-title">' . e($item['name']) . '</span>',
            '<code>/' . e($item['slug']) . '</code>',
            e($item['count']),
            'Hôm nay',
            '<span class="row-actions"><button type="button" data-action="admin-edit" aria-label="Sửa"><i class="bi bi-pencil"></i></button><button type="button" data-action="admin-delete" aria-label="Xóa"><i class="bi bi-trash"></i></button></span>',
        ];
    }
} elseif ($adminTableType === 'tags') {
    $tableHeaders = ['Tên thẻ', 'Số bài viết', 'Ngày tạo', ''];
    foreach ($tags as $index => $item) {
        $tableRows[] = [
            '<span class="table-chip">' . e($item) . '</span>',
            e(12 + ($index * 7)),
            e(($index + 8) . '/09/2026'),
            '<span class="row-actions"><button type="button" data-action="admin-edit" aria-label="Sửa"><i class="bi bi-pencil"></i></button><button type="button" data-action="admin-delete" aria-label="Xóa"><i class="bi bi-trash"></i></button></span>',
        ];
    }
} elseif ($adminTableType === 'comments') {
    $tableHeaders = ['Bình luận', 'Người viết', 'Bài viết', 'Ngày đăng', ''];
    foreach ($comments[1] as $item) {
        $tableRows[] = [
            '<span class="table-comment">' . e($item['body']) . '</span>',
            e($item['author']),
            '<span class="table-title">' . e($posts[0]['title']) . '</span>',
            e($item['time']),
            '<span class="row-actions"><button type="button" data-action="admin-edit" aria-label="Duyệt"><i class="bi bi-check2"></i></button><button type="button" data-action="admin-delete" aria-label="Xóa"><i class="bi bi-trash"></i></button></span>',
        ];
    }
} else {
    $tableHeaders = ['Người dùng', 'Vai trò', 'Bài viết', 'Trạng thái', 'Ngày tham gia', ''];
    $users = [
        ['name' => 'Duy Khang', 'email' => 'khang@example.com', 'role' => 'Admin', 'posts' => 18, 'locked' => false],
        ['name' => 'Minh Anh', 'email' => 'minhanh@example.com', 'role' => 'Thành viên', 'posts' => 42, 'locked' => false],
        ['name' => 'Thanh Hà', 'email' => 'thanhha@example.com', 'role' => 'Thành viên', 'posts' => 16, 'locked' => false],
        ['name' => 'Hoàng Nam', 'email' => 'nam@example.com', 'role' => 'Thành viên', 'posts' => 24, 'locked' => true],
    ];
    foreach ($users as $index => $item) {
        $tableRows[] = [
            '<span class="table-user"><span class="avatar avatar--table avatar-' . ['blue', 'gold', 'pink', 'purple'][$index] . '">' . e(initials($item['name'])) . '</span><span><b>' . e($item['name']) . '</b><small>' . e($item['email']) . '</small></span></span>',
            e($item['role']),
            e($item['posts']),
            $item['locked'] ? '<span class="status status--danger">Đã khóa</span>' : '<span class="status status--success">Hoạt động</span>',
            e(($index + 10) . '/08/2026'),
            '<span class="row-actions"><button type="button" data-action="toggle-lock" aria-label="Khóa hoặc mở khóa"><i class="bi ' . ($item['locked'] ? 'bi-unlock' : 'bi-lock') . '"></i></button><button type="button" data-action="admin-delete" aria-label="Xóa"><i class="bi bi-trash"></i></button></span>',
        ];
    }
}
?>
<div class="data-table-wrap">
    <table class="data-table" data-admin-table="<?= e($adminTableType) ?>">
        <thead>
            <tr>
                <?php foreach ($tableHeaders as $header): ?>
                    <th><?= e($header) ?></th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($tableRows as $row): ?>
                <tr>
                    <?php foreach ($row as $cell): ?>
                        <td><?= $cell ?></td>
                    <?php endforeach; ?>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

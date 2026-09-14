<?php

declare(strict_types=1);
?>
<div class="modal" id="auth-modal" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="auth-title">
    <button class="modal__backdrop" type="button" data-close-modal aria-label="Đóng"></button>
    <div class="modal__panel modal__panel--small">
        <button class="modal__close" type="button" data-close-modal aria-label="Đóng"><i class="bi bi-x-lg"></i></button>
        <div class="auth-brand"><span class="brand__mark">P</span></div>
        <h2 id="auth-title">Chào mừng trở lại</h2>
        <p class="modal__intro">Đăng nhập để viết bài, bình luận và lưu nội dung bạn yêu thích.</p>
        <div class="segmented-control segmented-control--full" data-auth-tabs>
            <button class="is-active" type="button" data-auth-tab="login">Đăng nhập</button>
            <button type="button" data-auth-tab="register">Đăng ký</button>
        </div>
        <form class="form-stack" data-form="auth">
            <label class="form-field register-only" hidden>
                <span>Họ và tên</span>
                <input type="text" name="name" placeholder="Nguyễn Văn A">
            </label>
            <label class="form-field">
                <span>Email</span>
                <input type="email" name="email" placeholder="name@example.com" required>
            </label>
            <label class="form-field">
                <span>Mật khẩu</span>
                <input type="password" name="password" placeholder="Tối thiểu 8 ký tự" required minlength="8">
            </label>
            <button class="button button--primary button--block" type="submit">Đăng nhập</button>
        </form>
    </div>
</div>

<div class="modal" id="login-required-modal" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="login-required-title">
    <button class="modal__backdrop" type="button" data-close-modal aria-label="Đóng"></button>
    <div class="modal__panel modal__panel--tiny">
        <button class="modal__close" type="button" data-close-modal aria-label="Đóng"><i class="bi bi-x-lg"></i></button>
        <div class="modal-icon"><i class="bi bi-lock"></i></div>
        <h2 id="login-required-title">Bạn cần đăng nhập</h2>
        <p class="modal__intro">Đăng nhập để tiếp tục tương tác với cộng đồng.</p>
        <button class="button button--primary button--block" type="button" data-open-modal="auth-modal">Đăng nhập ngay</button>
    </div>
</div>

<div class="modal" id="create-post-modal" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="create-post-title">
    <button class="modal__backdrop" type="button" data-close-modal aria-label="Đóng"></button>
    <div class="modal__panel">
        <button class="modal__close" type="button" data-close-modal aria-label="Đóng"><i class="bi bi-x-lg"></i></button>
        <h2 id="create-post-title">Tạo bài viết mới</h2>
        <p class="modal__intro">Chia sẻ câu chuyện hoặc kiến thức của bạn với cộng đồng.</p>
        <form class="form-stack" data-form="create-post">
            <div class="form-row">
                <label class="form-field form-field--grow">
                    <span>Tiêu đề</span>
                    <input type="text" name="title" placeholder="Tiêu đề bài viết" required>
                </label>
                <label class="form-field form-field--category">
                    <span>Chủ đề</span>
                    <select name="category">
                        <?php foreach ($categories as $category): ?>
                            <option><?= e($category['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
            </div>
            <label class="form-field">
                <span>Mô tả ngắn</span>
                <textarea name="excerpt" rows="3" placeholder="Tóm tắt ngắn gọn nội dung bài viết" required></textarea>
            </label>
            <label class="form-field">
                <span>Nội dung</span>
                <textarea name="content" rows="9" placeholder="Bắt đầu viết câu chuyện của bạn..." required></textarea>
            </label>
            <label class="form-field">
                <span>Thẻ</span>
                <input type="text" name="tags" placeholder="PHP, Backend, OpenSource">
            </label>
            <div class="modal__actions">
                <button class="button button--ghost" type="button" data-close-modal>Hủy</button>
                <button class="button button--primary" type="submit">Đăng bài</button>
            </div>
        </form>
    </div>
</div>

<div class="modal" id="edit-profile-modal" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="edit-profile-title">
    <button class="modal__backdrop" type="button" data-close-modal aria-label="Đóng"></button>
    <div class="modal__panel modal__panel--medium">
        <button class="modal__close" type="button" data-close-modal aria-label="Đóng"><i class="bi bi-x-lg"></i></button>
        <h2 id="edit-profile-title">Chỉnh sửa hồ sơ</h2>
        <form class="form-stack" data-form="edit-profile">
            <label class="form-field">
                <span>Họ và tên</span>
                <input type="text" name="name" value="<?= e($currentUser['name']) ?>" required>
            </label>
            <label class="form-field">
                <span>Tiểu sử</span>
                <textarea name="bio" rows="4"><?= e($currentUser['bio']) ?></textarea>
            </label>
            <label class="form-field">
                <span>Website</span>
                <input type="text" name="website" value="<?= e($currentUser['website']) ?>">
            </label>
            <div class="modal__actions">
                <button class="button button--ghost" type="button" data-close-modal>Hủy</button>
                <button class="button button--primary" type="submit">Lưu thay đổi</button>
            </div>
        </form>
    </div>
</div>

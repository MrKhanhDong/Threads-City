(() => {
    'use strict';

    const body = document.body;
    const toast = document.getElementById('toast');
    let toastTimer;
    let authenticated = body.dataset.authenticated === 'true';

    const showToast = (message) => {
        if (!toast) return;
        toast.textContent = message;
        toast.classList.add('is-visible');
        window.clearTimeout(toastTimer);
        toastTimer = window.setTimeout(() => toast.classList.remove('is-visible'), 2600);
    };

    const closeModals = () => {
        document.querySelectorAll('.modal.is-open').forEach((modal) => {
            modal.classList.remove('is-open');
            modal.setAttribute('aria-hidden', 'true');
        });
        body.classList.remove('modal-open');
    };

    const openModal = (id) => {
        const protectedModals = ['create-post-modal', 'edit-profile-modal'];
        const targetId = !authenticated && protectedModals.includes(id) ? 'login-required-modal' : id;
        const modal = document.getElementById(targetId);
        if (!modal) return;

        closeModals();
        modal.classList.add('is-open');
        modal.setAttribute('aria-hidden', 'false');
        body.classList.add('modal-open');
        window.setTimeout(() => modal.querySelector('input, textarea, button:not(.modal__backdrop)')?.focus(), 30);
    };

    const requireLogin = () => {
        if (authenticated) return false;
        openModal('login-required-modal');
        return true;
    };

    const updateAuthNavigation = () => {
        const authButton = document.querySelector('[data-action="logout"]');
        if (!authButton) return;
        const label = authButton.querySelector('span');
        const icon = authButton.querySelector('i');

        if (authenticated) {
            label.textContent = 'Đăng xuất';
            icon.className = 'bi bi-box-arrow-right';
            authButton.classList.add('nav-item--danger');
        } else {
            label.textContent = 'Đăng nhập';
            icon.className = 'bi bi-box-arrow-in-right';
            authButton.classList.remove('nav-item--danger');
        }
    };

    document.addEventListener('click', (event) => {
        const openTrigger = event.target.closest('[data-open-modal]');
        if (openTrigger) {
            event.preventDefault();
            openModal(openTrigger.dataset.openModal);
            return;
        }

        if (event.target.closest('[data-close-modal]')) {
            closeModals();
            return;
        }

        const actionButton = event.target.closest('[data-action]');
        if (!actionButton) return;

        const action = actionButton.dataset.action;

        if (action === 'logout') {
            if (!authenticated) {
                openModal('auth-modal');
                return;
            }
            authenticated = false;
            body.dataset.authenticated = 'false';
            updateAuthNavigation();
            showToast('Bạn đã đăng xuất khỏi tài khoản demo.');
            return;
        }

        if (['like', 'save', 'comment-like', 'reply'].includes(action) && requireLogin()) return;

        if (action === 'like') {
            const isActive = actionButton.classList.toggle('is-active');
            const icon = actionButton.querySelector('i');
            const count = actionButton.querySelector('span');
            if (icon) icon.className = `bi ${isActive ? 'bi-heart-fill' : 'bi-heart'}`;
            if (count) {
                const current = Number.parseInt(count.textContent, 10) || 0;
                count.textContent = String(Math.max(0, current + (isActive ? 1 : -1)));
            }
            return;
        }

        if (action === 'save') {
            const isActive = actionButton.classList.toggle('is-active');
            const icon = actionButton.querySelector('i');
            if (icon) icon.className = `bi ${isActive ? 'bi-bookmark-fill' : 'bi-bookmark'}`;
            showToast(isActive ? 'Đã lưu bài viết.' : 'Đã bỏ bài viết khỏi mục đã lưu.');
            return;
        }

        if (action === 'share') {
            const shareUrl = window.location.href;
            if (navigator.clipboard?.writeText) {
                navigator.clipboard.writeText(shareUrl).then(() => showToast('Đã sao chép liên kết bài viết.'));
            } else {
                showToast('Liên kết bài viết đã sẵn sàng để chia sẻ.');
            }
            return;
        }

        if (action === 'follow') {
            if (requireLogin()) return;
            const following = actionButton.classList.toggle('is-following');
            actionButton.textContent = following ? 'Đang theo dõi' : 'Theo dõi';
            showToast(following ? 'Đã theo dõi tác giả.' : 'Đã bỏ theo dõi tác giả.');
            return;
        }

        if (action === 'comment-like') {
            const icon = actionButton.querySelector('i');
            const text = actionButton.textContent.trim();
            const count = Number.parseInt(text, 10) || 0;
            const active = actionButton.classList.toggle('is-active');
            if (icon) icon.className = `bi ${active ? 'bi-heart-fill' : 'bi-heart'}`;
            actionButton.lastChild.textContent = ` ${Math.max(0, count + (active ? 1 : -1))}`;
            return;
        }

        if (action === 'reply') {
            const comment = actionButton.closest('.comment');
            const author = comment?.querySelector('.author-line strong')?.textContent || 'tác giả';
            const composer = document.querySelector('.comment-composer textarea');
            if (composer) {
                composer.value = `@${author.replace(/\s+/g, '')} `;
                composer.focus();
            }
            return;
        }

        if (action === 'delete-comment' || action === 'admin-delete') {
            const target = actionButton.closest(action === 'delete-comment' ? '.comment' : 'tr');
            if (target && window.confirm('Bạn chắc chắn muốn xóa mục này khỏi bản demo?')) {
                target.remove();
                showToast('Đã xóa khỏi giao diện demo.');
            }
            return;
        }

        if (action === 'toggle-lock') {
            const row = actionButton.closest('tr');
            const status = row?.querySelector('.status');
            const icon = actionButton.querySelector('i');
            if (!status || !icon) return;
            const isLocked = status.classList.contains('status--danger');
            status.className = `status ${isLocked ? 'status--success' : 'status--danger'}`;
            status.textContent = isLocked ? 'Hoạt động' : 'Đã khóa';
            icon.className = `bi ${isLocked ? 'bi-lock' : 'bi-unlock'}`;
            showToast(isLocked ? 'Đã mở khóa tài khoản.' : 'Đã khóa tài khoản.');
            return;
        }

        if (action === 'admin-edit' || action === 'admin-add') {
            showToast(action === 'admin-edit' ? 'Đã mở chế độ chỉnh sửa demo.' : 'Đã mở biểu mẫu thêm mới demo.');
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') closeModals();
    });

    document.querySelectorAll('[data-auth-tab]').forEach((button) => {
        button.addEventListener('click', () => {
            const isRegister = button.dataset.authTab === 'register';
            document.querySelectorAll('[data-auth-tab]').forEach((item) => item.classList.toggle('is-active', item === button));
            document.querySelectorAll('.register-only').forEach((field) => { field.hidden = !isRegister; });
            const modal = document.getElementById('auth-modal');
            const title = modal?.querySelector('h2');
            const submit = modal?.querySelector('[type="submit"]');
            if (title) title.textContent = isRegister ? 'Tạo tài khoản mới' : 'Chào mừng trở lại';
            if (submit) submit.textContent = isRegister ? 'Đăng ký' : 'Đăng nhập';
        });
    });

    document.querySelectorAll('form[data-form]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            event.preventDefault();
            const type = form.dataset.form;
            if (type !== 'auth' && requireLogin()) return;

            if (type === 'auth') {
                authenticated = true;
                body.dataset.authenticated = 'true';
                updateAuthNavigation();
                closeModals();
                form.reset();
                showToast('Đăng nhập thành công ở chế độ giao diện demo.');
            } else if (type === 'create-post') {
                const title = new FormData(form).get('title');
                closeModals();
                form.reset();
                showToast(`Đã tạo bản nháp “${title}”.`);
            } else if (type === 'edit-profile') {
                const data = new FormData(form);
                const name = String(data.get('name') || '');
                const bio = String(data.get('bio') || '');
                document.querySelector('.profile-card__top h2')?.replaceChildren(name);
                document.querySelector('.profile-card__bio')?.replaceChildren(bio);
                closeModals();
                showToast('Đã cập nhật hồ sơ trên giao diện demo.');
            } else if (type === 'comment') {
                const input = form.querySelector('textarea');
                const value = input?.value.trim();
                if (!value) return;

                const article = document.createElement('article');
                article.className = 'comment';
                const avatar = document.createElement('span');
                avatar.className = 'avatar avatar--small avatar-blue';
                avatar.textContent = 'DK';
                const content = document.createElement('div');
                content.className = 'comment__body';
                const meta = document.createElement('div');
                meta.className = 'author-line';
                const strong = document.createElement('strong');
                strong.textContent = 'Duy Khang';
                const time = document.createElement('span');
                time.textContent = '@duykhang · vừa xong';
                const paragraph = document.createElement('p');
                paragraph.textContent = value;
                meta.append(strong, time);
                content.append(meta, paragraph);
                article.append(avatar, content);
                document.querySelector('.comment-list')?.prepend(article);
                input.value = '';
                showToast('Đã thêm bình luận vào giao diện demo.');
            }
        });
    });

    const feed = document.getElementById('post-feed');
    if (feed) {
        const originalPosts = Array.from(feed.children);
        document.querySelectorAll('[data-feed-tab]').forEach((button) => {
            button.addEventListener('click', () => {
                document.querySelectorAll('[data-feed-tab]').forEach((item) => item.classList.toggle('is-active', item === button));
                const ordered = button.dataset.feedTab === 'latest' ? [...originalPosts].reverse() : originalPosts;
                ordered.forEach((post) => feed.append(post));
            });
        });
    }

    document.querySelectorAll('[data-profile-tab]').forEach((button) => {
        button.addEventListener('click', () => {
            const key = button.dataset.profileTab;
            document.querySelectorAll('[data-profile-tab]').forEach((item) => item.classList.toggle('is-active', item === button));
            document.querySelectorAll('[data-profile-panel]').forEach((panel) => {
                const active = panel.dataset.profilePanel === key;
                panel.classList.toggle('is-active', active);
                panel.hidden = !active;
            });
        });
    });

    const switchAdminTab = (key) => {
        document.querySelectorAll('[data-admin-tab]').forEach((button) => button.classList.toggle('is-active', button.dataset.adminTab === key));
        document.querySelectorAll('[data-admin-panel]').forEach((panel) => {
            const active = panel.dataset.adminPanel === key;
            panel.classList.toggle('is-active', active);
            panel.hidden = !active;
        });
    };

    document.querySelectorAll('[data-admin-tab]').forEach((button) => {
        button.addEventListener('click', () => switchAdminTab(button.dataset.adminTab));
    });

    document.querySelectorAll('[data-admin-go]').forEach((button) => {
        button.addEventListener('click', () => switchAdminTab(button.dataset.adminGo));
    });

    document.querySelectorAll('[data-table-search]').forEach((input) => {
        input.addEventListener('input', () => {
            const table = input.closest('.admin-card')?.querySelector('table');
            const query = input.value.trim().toLocaleLowerCase('vi');
            table?.querySelectorAll('tbody tr').forEach((row) => {
                row.hidden = query !== '' && !row.textContent.toLocaleLowerCase('vi').includes(query);
            });
        });
    });

    if (new URLSearchParams(window.location.search).get('focus') === 'comments') {
        document.getElementById('comments')?.scrollIntoView();
    }
})();

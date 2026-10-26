@push('threads-scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    // Helper: Global Toast message
    function showToast(message) {
        const toast = document.getElementById('threadsGlobalToast');
        if (!toast) return;
        toast.textContent = message;
        toast.classList.add('show');
        setTimeout(() => {
            toast.classList.remove('show');
        }, 2500);
    }

    // 1. AJAX Like Action
    document.addEventListener('click', function(e) {
        const likeBtn = e.target.closest('.action-like-btn');
        if (!likeBtn) return;
        e.preventDefault();

        const threadId = likeBtn.getAttribute('data-thread-id');
        const heartSvg = likeBtn.querySelector('.heart-icon');
        const countSpan = likeBtn.querySelector('.like-count-span');

        // Optimistic UI toggle
        const isLiked = likeBtn.classList.contains('liked');
        likeBtn.classList.toggle('liked');
        heartSvg.setAttribute('fill', !isLiked ? '#ff3040' : 'none');
        heartSvg.setAttribute('stroke', !isLiked ? '#ff3040' : 'currentColor');

        fetch(`/threads/${threadId}/like`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            }
        })
        .then(res => {
            if (res.status === 401) {
                window.location.href = '{{ route("login") }}';
                return;
            }
            return res.json();
        })
        .then(data => {
            if (data && data.success) {
                countSpan.textContent = data.likes_count > 0 ? data.likes_count : '';
            }
        })
        .catch(err => {
            console.error('Like error:', err);
            // Revert
            likeBtn.classList.toggle('liked', isLiked);
            heartSvg.setAttribute('fill', isLiked ? '#ff3040' : 'none');
            heartSvg.setAttribute('stroke', isLiked ? '#ff3040' : 'currentColor');
        });
    });

    // 2. AJAX Bookmark Action
    document.addEventListener('click', function(e) {
        const bookmarkBtn = e.target.closest('.action-bookmark-btn');
        if (!bookmarkBtn) return;
        e.preventDefault();

        const threadId = bookmarkBtn.getAttribute('data-thread-id');
        const bookmarkSvg = bookmarkBtn.querySelector('.bookmark-icon');
        const isBookmarked = bookmarkBtn.classList.contains('bookmarked');

        bookmarkBtn.classList.toggle('bookmarked');
        bookmarkSvg.setAttribute('fill', !isBookmarked ? 'currentColor' : 'none');

        fetch(`/threads/${threadId}/bookmark`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            }
        })
        .then(res => {
            if (res.status === 401) {
                window.location.href = '{{ route("login") }}';
                return;
            }
            return res.json();
        })
        .then(data => {
            if (data && data.success) {
                showToast(data.bookmarked ? 'Đã lưu vào danh sách bài viết' : 'Đã bỏ lưu bài viết');
            }
        })
        .catch(err => {
            console.error('Bookmark error:', err);
            bookmarkBtn.classList.toggle('bookmarked', isBookmarked);
            bookmarkSvg.setAttribute('fill', isBookmarked ? 'currentColor' : 'none');
        });
    });

    // 3. Repost / Quote Modal trigger
    let activeRepostThreadId = null;
    let activeRepostMeta = null;

    document.addEventListener('click', function(e) {
        const repostBtn = e.target.closest('.action-repost-btn');
        if (!repostBtn) return;
        e.preventDefault();

        activeRepostThreadId = repostBtn.getAttribute('data-thread-id');
        activeRepostMeta = {
            name: repostBtn.getAttribute('data-author-name'),
            handle: repostBtn.getAttribute('data-author-handle'),
            snippet: repostBtn.getAttribute('data-content-snippet')
        };

        const modalEl = document.getElementById('repostActionModal');
        if (modalEl && window.bootstrap) {
            const modal = new bootstrap.Modal(modalEl);
            modal.show();
        }
    });

    // Direct Repost click
    const btnDirectRepost = document.getElementById('btnActionDirectRepost');
    if (btnDirectRepost) {
        btnDirectRepost.addEventListener('click', function() {
            if (!activeRepostThreadId) return;

            const modalEl = document.getElementById('repostActionModal');
            if (modalEl && window.bootstrap) {
                bootstrap.Modal.getInstance(modalEl)?.hide();
            }

            fetch(`/threads/${activeRepostThreadId}/repost`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                }
            })
            .then(res => res.json())
            .then(data => {
                if (data && data.success) {
                    showToast(data.reposted ? 'Đã đăng lại lên trang cá nhân' : 'Đã hủy đăng lại');
                    window.dispatchEvent(new Event('threads:refresh'));
                }
            })
            .catch(err => console.error(err));
        });
    }

    // Quote thread click
    const btnQuoteThread = document.getElementById('btnActionQuoteThread');
    if (btnQuoteThread) {
        btnQuoteThread.addEventListener('click', function() {
            if (!activeRepostThreadId || !activeRepostMeta) return;

            const repostModalEl = document.getElementById('repostActionModal');
            if (repostModalEl && window.bootstrap) {
                bootstrap.Modal.getInstance(repostModalEl)?.hide();
            }

            const newModalEl = document.getElementById('newThreadModal');
            if (newModalEl && window.bootstrap) {
                document.getElementById('modalRepostOfId').value = activeRepostThreadId;
                document.getElementById('modalPostType').value = 'quote';
                document.getElementById('newThreadModalTitle').textContent = 'Trích dẫn bài viết';

                const quoteContainer = document.getElementById('modalQuotedThreadContainer');
                quoteContainer.style.display = 'block';
                quoteContainer.innerHTML = `
                    <div class="fw-bold fs-6 text-white">${activeRepostMeta.name} <span class="text-secondary small">${activeRepostMeta.handle}</span></div>
                    <div class="text-secondary small mt-1">${activeRepostMeta.snippet}</div>
                `;

                const modal = new bootstrap.Modal(newModalEl);
                modal.show();
            }
        });
    }

    // 4. Copy thread link to clipboard
    document.addEventListener('click', function(e) {
        const copyBtn = e.target.closest('.btn-copy-thread-link');
        if (!copyBtn) return;
        e.preventDefault();

        const url = copyBtn.getAttribute('data-url');
        if (url) {
            navigator.clipboard.writeText(url).then(() => {
                showToast('Đã sao chép liên kết vào clipboard');
            }).catch(() => {
                showToast('Không thể sao chép liên kết');
            });
        }
    });

    // 5. Inline Composer Logic
    const inlineText = document.getElementById('inlineComposerText');
    const inlineSubmitBtn = document.getElementById('inlineSubmitThreadBtn');
    const inlineFileInput = document.getElementById('inlineComposerFileInput');
    const inlineAddImgBtn = document.getElementById('inlineAddImageBtn');
    const inlinePreview = document.getElementById('inlineComposerPreview');
    const inlineCharCount = document.getElementById('inlineCharCount');
    let inlineMediaIds = [];

    if (inlineText && inlineSubmitBtn) {
        inlineText.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = (this.scrollHeight) + 'px';
            const len = this.value.trim().length;
            inlineCharCount.textContent = `${len}/2000`;
            inlineSubmitBtn.disabled = (len === 0 && inlineMediaIds.length === 0);
        });

        if (inlineAddImgBtn && inlineFileInput) {
            inlineAddImgBtn.addEventListener('click', () => inlineFileInput.click());

            inlineFileInput.addEventListener('change', function() {
                const file = this.files[0];
                if (!file) return;

                const formData = new FormData();
                formData.append('image', file);

                showToast('Đang tải ảnh lên...');
                fetch('{{ route("threads.upload-media") }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    },
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    if (data && data.id) {
                        inlineMediaIds.push(data.id);
                        inlinePreview.style.display = 'flex';
                        const item = document.createElement('div');
                        item.className = 'composer-media-item';
                        item.innerHTML = `
                            <img src="${data.url}" alt="Attachment">
                            <button type="button" class="composer-media-remove" data-id="${data.id}">✕</button>
                        `;
                        inlinePreview.appendChild(item);
                        inlineSubmitBtn.disabled = false;
                        showToast('Đã đính kèm ảnh');
                    }
                })
                .catch(err => {
                    console.error(err);
                    showToast('Tải ảnh thất bại');
                });
            });
        }

        inlinePreview?.addEventListener('click', function(e) {
            const rmBtn = e.target.closest('.composer-media-remove');
            if (!rmBtn) return;
            const id = parseInt(rmBtn.getAttribute('data-id'));
            inlineMediaIds = inlineMediaIds.filter(x => x !== id);
            rmBtn.closest('.composer-media-item')?.remove();
            if (inlineMediaIds.length === 0) {
                inlinePreview.style.display = 'none';
                if (inlineText.value.trim().length === 0) {
                    inlineSubmitBtn.disabled = true;
                }
            }
        });

        inlineSubmitBtn.addEventListener('click', function() {
            const content = inlineText.value.trim();
            if (!content && inlineMediaIds.length === 0) return;

            inlineSubmitBtn.disabled = true;
            inlineSubmitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span>';

            fetch('{{ route("threads.store") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    content: content,
                    media_ids: inlineMediaIds,
                    type: 'post'
                })
            })
            .then(res => res.json())
            .then(data => {
                if (data && data.success) {
                    showToast('Đã đăng bài viết thành công!');
                    inlineText.value = '';
                    inlineMediaIds = [];
                    inlinePreview.innerHTML = '';
                    inlinePreview.style.display = 'none';
                    inlineSubmitBtn.textContent = '\u0110\u0103ng';
                    window.dispatchEvent(new Event('threads:refresh'));
                } else {
                    showToast(data.message || 'Đăng bài thất bại');
                    inlineSubmitBtn.disabled = false;
                    inlineSubmitBtn.textContent = 'Đăng';
                }
            })
            .catch(err => {
                console.error(err);
                showToast('Lỗi gửi yêu cầu');
                inlineSubmitBtn.disabled = false;
                inlineSubmitBtn.textContent = 'Đăng';
            });
        });
    }

    // 6. Infinite Scroll / Load more
    const btnLoadMore = document.getElementById('btnLoadMoreThreads');
    if (btnLoadMore) {
        btnLoadMore.addEventListener('click', function() {
            const nextUrl = this.getAttribute('data-next-url');
            if (!nextUrl) return;

            const textSpan = document.getElementById('loadMoreText');
            const spinner = document.getElementById('loadMoreSpinner');
            textSpan.textContent = 'Đang tải...';
            spinner.style.display = 'inline-block';
            this.disabled = true;

            fetch(nextUrl, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            })
            .then(res => res.json())
            .then(data => {
                if (data && data.html) {
                    const container = document.getElementById('threadsFeedContainer');
                    const page = document.createElement('template');
                    page.innerHTML = data.html;
                    page.content.querySelectorAll('[data-live-key]').forEach(card => {
                        if (document.getElementById(card.id)) card.remove();
                    });
                    container.append(page.content);

                    if (data.hasMore && data.nextPageUrl) {
                        btnLoadMore.setAttribute('data-next-url', data.nextPageUrl);
                        btnLoadMore.disabled = false;
                        textSpan.textContent = 'Tải thêm bài viết';
                        spinner.style.display = 'none';
                    } else {
                        document.getElementById('loadMoreSection')?.remove();
                    }
                }
            })
            .catch(err => {
                console.error(err);
                btnLoadMore.disabled = false;
                textSpan.textContent = 'Thử lại';
                spinner.style.display = 'none';
            });
        });
    }
});
</script>
@endpush

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

        if (typeof window.checkAuth === 'function' && !window.checkAuth('thích bài viết này')) {
            return;
        }

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
                if (typeof window.checkAuth === 'function') window.checkAuth('thích bài viết này');
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

        if (typeof window.checkAuth === 'function' && !window.checkAuth('lưu bài viết này')) {
            return;
        }

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
                if (typeof window.checkAuth === 'function') window.checkAuth('lưu bài viết này');
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

        if (typeof window.checkAuth === 'function' && !window.checkAuth('đăng lại hoặc trích dẫn bài viết')) {
            return;
        }

        activeRepostThreadId = repostBtn.getAttribute('data-thread-id');
        activeRepostMeta = {
            name: repostBtn.getAttribute('data-author-name'),
            handle: repostBtn.getAttribute('data-author-handle'),
            snippet: repostBtn.getAttribute('data-content-snippet')
        };

        const isCurrentlyReposted = repostBtn.classList.contains('reposted');
        const titleEl = document.getElementById('repostOptionTitle');
        const descEl = document.getElementById('repostOptionDesc');
        const iconBox = document.getElementById('repostOptionIconBox');

        if (titleEl && descEl) {
            if (isCurrentlyReposted) {
                titleEl.textContent = 'Hủy đăng lại';
                titleEl.classList.add('text-danger');
                titleEl.classList.remove('text-white');
                descEl.textContent = 'Gỡ bài viết này khỏi trang cá nhân của bạn';
                if (iconBox) iconBox.style.color = '#ff4433';
            } else {
                titleEl.textContent = 'Đăng lại';
                titleEl.classList.remove('text-danger');
                titleEl.classList.add('text-white');
                descEl.textContent = 'Chia sẻ trực tiếp bài này đến trang cá nhân của bạn';
                if (iconBox) iconBox.style.color = '#00ba7c';
            }
        }

        const modalEl = document.getElementById('repostActionModal');
        if (modalEl && window.bootstrap) {
            const modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
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

            const targetThreadId = activeRepostThreadId;

            fetch(`/threads/${targetThreadId}/repost`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                }
            })
            .then(res => {
                if (res.status === 401) {
                    if (typeof window.checkAuth === 'function') window.checkAuth('đăng lại bài viết');
                    return;
                }
                return res.json();
            })
            .then(data => {
                if (data && data.success) {
                    showToast(data.reposted ? 'Đã đăng lại lên trang cá nhân' : 'Đã hủy đăng lại');

                    // Synchronize all repost buttons for this thread on the page
                    document.querySelectorAll(`.action-repost-btn[data-thread-id="${targetThreadId}"]`).forEach(btn => {
                        btn.classList.toggle('reposted', !!data.reposted);
                        const icon = btn.querySelector('.repost-icon');
                        if (icon) {
                            icon.setAttribute('stroke', data.reposted ? '#00ba7c' : 'currentColor');
                        }
                        const countSpan = btn.querySelector('.repost-count-span');
                        if (countSpan) {
                            if (data.reposts_count > 0) {
                                countSpan.textContent = data.reposts_count >= 1000 
                                    ? (data.reposts_count / 1000).toFixed(1).replace('.', ',') + 'K' 
                                    : data.reposts_count;
                            } else {
                                countSpan.textContent = '';
                            }
                        }
                    });

                    window.dispatchEvent(new Event('threads:refresh'));
                }
            })
            .catch(err => {
                console.error(err);
                showToast('Lỗi khi thao tác đăng lại bài viết');
            });
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
                const repostOfInput = document.getElementById('modalRepostOfId');
                const postTypeInput = document.getElementById('modalPostType');
                const modalTitle = document.getElementById('newThreadModalTitle');
                const quoteContainer = document.getElementById('modalQuotedThreadContainer');
                const composerText = document.getElementById('modalComposerText');
                const modalSubmitBtn = document.getElementById('modalSubmitBtn');

                if (repostOfInput) repostOfInput.value = activeRepostThreadId;
                if (postTypeInput) postTypeInput.value = 'quote';
                if (modalTitle) modalTitle.textContent = 'Trích dẫn bài viết';

                if (quoteContainer) {
                    quoteContainer.style.display = 'block';
                    quoteContainer.innerHTML = `
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <div class="d-flex align-items-center gap-2">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" class="text-secondary"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
                                <span class="fw-bold text-white small">${activeRepostMeta.name}</span>
                                <span class="text-secondary small">${activeRepostMeta.handle}</span>
                            </div>
                            <button type="button" class="btn-close-quote-preview" id="btnCancelQuoteAttachment" title="Gỡ trích dẫn">✕</button>
                        </div>
                        <div class="text-secondary small" style="line-height: 1.4;">${activeRepostMeta.snippet || '(Không có văn bản)'}</div>
                    `;

                    document.getElementById('btnCancelQuoteAttachment')?.addEventListener('click', function() {
                        if (repostOfInput) repostOfInput.value = '';
                        if (postTypeInput) postTypeInput.value = 'post';
                        if (modalTitle) modalTitle.textContent = 'Thread mới';
                        if (composerText) composerText.placeholder = 'Có gì mới?';
                        quoteContainer.style.display = 'none';
                        quoteContainer.innerHTML = '';
                        if (typeof window.updateComposerSubmitState === 'function') {
                            window.updateComposerSubmitState();
                        }
                    });
                }

                if (composerText) {
                    composerText.placeholder = 'Thêm suy nghĩ của bạn...';
                }

                if (modalSubmitBtn) {
                    modalSubmitBtn.disabled = false;
                    modalSubmitBtn.classList.add('is-active');
                }

                const modal = bootstrap.Modal.getInstance(newModalEl) || new bootstrap.Modal(newModalEl);
                modal.show();
                setTimeout(() => composerText?.focus(), 300);
            }
        });
    }

    // 4. Thread 3-Dots Post Menu Actions
    // 4.1 Copy thread link to clipboard
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

    // 4.2 Embed Thread Code
    document.addEventListener('click', function(e) {
        const embedBtn = e.target.closest('.btn-embed-thread');
        if (!embedBtn) return;
        e.preventDefault();

        const url = embedBtn.getAttribute('data-url');
        if (url) {
            const embedCode = `<iframe src="${url}" width="500" height="300" frameborder="0" allowfullscreen></iframe>`;
            navigator.clipboard.writeText(embedCode).then(() => {
                showToast('Đã sao chép mã nhúng bài viết vào clipboard');
            }).catch(() => {
                showToast('Không thể sao chép mã nhúng');
            });
        }
    });

    // 4.3 AJAX Dropdown Bookmark action
    document.addEventListener('click', function(e) {
        const bookmarkOpt = e.target.closest('.btn-ajax-thread-bookmark');
        if (!bookmarkOpt) return;
        e.preventDefault();

        if (typeof window.checkAuth === 'function' && !window.checkAuth('lưu bài viết này')) {
            return;
        }

        const threadId = bookmarkOpt.getAttribute('data-thread-id');
        const labelEl = bookmarkOpt.querySelector('.bookmark-label');
        const svgEl = bookmarkOpt.querySelector('.bookmark-svg-icon');
        const isBookmarked = bookmarkOpt.getAttribute('data-bookmarked') === '1';

        // Optimistic toggle
        const nextState = !isBookmarked;
        bookmarkOpt.setAttribute('data-bookmarked', nextState ? '1' : '0');
        if (labelEl) labelEl.textContent = nextState ? 'Bỏ lưu' : 'Lưu';
        if (svgEl) svgEl.setAttribute('fill', nextState ? 'currentColor' : 'none');

        // Sync with any bottom action bar bookmark button for this thread
        const bottomBtn = document.querySelector(`.action-bookmark-btn[data-thread-id="${threadId}"]`);
        if (bottomBtn) {
            bottomBtn.classList.toggle('bookmarked', nextState);
            const bSvg = bottomBtn.querySelector('.bookmark-icon');
            if (bSvg) bSvg.setAttribute('fill', nextState ? 'currentColor' : 'none');
        }

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
                if (typeof window.checkAuth === 'function') window.checkAuth('lưu bài viết này');
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
            // Revert
            bookmarkOpt.setAttribute('data-bookmarked', isBookmarked ? '1' : '0');
            if (labelEl) labelEl.textContent = isBookmarked ? 'Bỏ lưu' : 'Lưu';
            if (svgEl) svgEl.setAttribute('fill', isBookmarked ? 'currentColor' : 'none');
            if (bottomBtn) {
                bottomBtn.classList.toggle('bookmarked', isBookmarked);
                const bSvg = bottomBtn.querySelector('.bookmark-icon');
                if (bSvg) bSvg.setAttribute('fill', isBookmarked ? 'currentColor' : 'none');
            }
        });
    });

    // 4.4 Not interested (fade out, remove card, save to DB)
    document.addEventListener('click', function(e) {
        const notIntBtn = e.target.closest('.btn-not-interested');
        if (!notIntBtn) return;
        e.preventDefault();

        if (typeof window.checkAuth === 'function' && !window.checkAuth('tùy chỉnh gợi ý bài viết')) {
            return;
        }

        const threadId = notIntBtn.getAttribute('data-thread-id');
        const card = notIntBtn.closest('.thread-card-wrapper');
        if (card) {
            card.style.transition = 'all 0.35s cubic-bezier(0.4, 0, 0.2, 1)';
            card.style.opacity = '0';
            card.style.transform = 'scale(0.96)';
            setTimeout(() => {
                card.remove();
            }, 350);
        }

        if (threadId) {
            fetch(`/threads/${threadId}/not-interested`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                }
            })
            .then(res => res.json())
            .then(data => {
                showToast(data.message || 'Đã ẩn bài viết. Bạn sẽ ít thấy bài viết tương tự hơn.');
            })
            .catch(() => {
                showToast('Đã ẩn bài viết');
            });
        }
    });

    // 4.5 Add to custom feed
    document.addEventListener('click', function(e) {
        const feedBtn = e.target.closest('.btn-add-to-custom-feed');
        if (!feedBtn) return;
        e.preventDefault();

        if (typeof window.checkAuth === 'function' && !window.checkAuth('thêm bài viết vào Bảng Feed cá nhân')) {
            return;
        }

        const threadId = feedBtn.getAttribute('data-thread-id');
        if (!threadId) return;

        fetch(`/threads/${threadId}/add-to-feed`, {
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
                showToast(data.message || 'Đã thêm bài viết vào Bảng Feed tùy chỉnh');
            }
        })
        .catch(err => {
            console.error(err);
            showToast('Không thể thêm vào bảng feed');
        });
    });

    // 4.6 Mute notifications
    document.addEventListener('click', function(e) {
        const muteBtn = e.target.closest('.btn-mute-thread');
        if (!muteBtn) return;
        e.preventDefault();

        if (typeof window.checkAuth === 'function' && !window.checkAuth('cài đặt thông báo bài viết')) {
            return;
        }

        const threadId = muteBtn.getAttribute('data-thread-id');
        const labelEl = muteBtn.querySelector('.mute-label');

        fetch(`/threads/${threadId}/mute`, {
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
                muteBtn.setAttribute('data-muted', data.muted ? '1' : '0');
                if (labelEl) labelEl.textContent = data.muted ? 'Bật thông báo' : 'Tắt thông báo';
                showToast(data.message);
            }
        })
        .catch(err => {
            console.error(err);
            showToast('Lỗi khi thao tác tắt thông báo');
        });
    });

    // 4.7 Restrict user
    document.addEventListener('click', function(e) {
        const resBtn = e.target.closest('.btn-restrict-user');
        if (!resBtn) return;
        e.preventDefault();

        if (typeof window.checkAuth === 'function' && !window.checkAuth('hạn chế người dùng này')) {
            return;
        }

        const userId = resBtn.getAttribute('data-user-id');
        const labelEl = resBtn.querySelector('.restrict-label');
        if (!userId) return;

        fetch(`/users/${userId}/restrict`, {
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
                resBtn.setAttribute('data-restricted', data.restricted ? '1' : '0');
                if (labelEl) labelEl.textContent = data.restricted ? 'Bỏ hạn chế' : 'Hạn chế';
                showToast(data.message);
            }
        })
        .catch(err => {
            console.error(err);
            showToast('Lỗi khi thao tác hạn chế người dùng');
        });
    });

    // 4.8 Block user
    document.addEventListener('click', function(e) {
        const blockBtn = e.target.closest('.btn-block-user');
        if (!blockBtn) return;
        e.preventDefault();

        if (typeof window.checkAuth === 'function' && !window.checkAuth('chặn người dùng này')) {
            return;
        }

        const userName = blockBtn.getAttribute('data-user-name') || 'người dùng';
        const userId = blockBtn.getAttribute('data-user-id');
        const isBlocked = blockBtn.getAttribute('data-blocked') === '1';

        const confirmMsg = isBlocked 
            ? `Bạn có chắc chắn muốn bỏ chặn ${userName}?`
            : `Bạn có chắc chắn muốn chặn ${userName}? Bạn sẽ không thấy bài viết hoặc thông báo từ người này nữa.`;

        if (!confirm(confirmMsg)) return;

        fetch(`/users/${userId}/block`, {
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
                if (data.blocked) {
                    document.querySelectorAll(`.btn-block-user[data-user-id="${userId}"]`).forEach(btn => {
                        const card = btn.closest('.thread-card-wrapper');
                        if (card) {
                            card.style.transition = 'opacity 0.3s ease';
                            card.style.opacity = '0';
                            setTimeout(() => card.remove(), 300);
                        }
                    });
                }
                showToast(data.message);
            }
        })
        .catch(err => {
            console.error(err);
            showToast('Lỗi khi thao tác chặn người dùng');
        });
    });

    // 4.9 Report thread modal trigger
    document.addEventListener('click', function(e) {
        const reportBtn = e.target.closest('.btn-report-thread');
        if (!reportBtn) return;
        e.preventDefault();

        if (typeof window.checkAuth === 'function' && !window.checkAuth('gửi báo cáo bài viết')) {
            return;
        }

        const threadId = reportBtn.getAttribute('data-thread-id');
        const authorName = reportBtn.getAttribute('data-author-name') || 'tác giả';

        const authorSpan = document.getElementById('reportModalAuthorName');
        const inputId = document.getElementById('reportModalThreadId');
        if (authorSpan) authorSpan.textContent = authorName;
        if (inputId) inputId.value = threadId;

        const modalEl = document.getElementById('reportThreadModal');
        if (modalEl && window.bootstrap) {
            const modal = new bootstrap.Modal(modalEl);
            modal.show();
        }
    });

    // Report modal submit
    const btnSubmitReport = document.getElementById('btnSubmitReport');
    if (btnSubmitReport) {
        btnSubmitReport.addEventListener('click', function() {
            const threadId = document.getElementById('reportModalThreadId')?.value;
            const selectedRadio = document.querySelector('input[name="reportReason"]:checked');
            const reason = selectedRadio ? selectedRadio.value : 'spam';

            if (!threadId) return;

            const modalEl = document.getElementById('reportThreadModal');
            if (modalEl && window.bootstrap) {
                bootstrap.Modal.getInstance(modalEl)?.hide();
            }

            fetch(`/threads/${threadId}/report`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ reason: reason })
            })
            .then(res => {
                if (res.status === 401) {
                    window.location.href = '{{ route("login") }}';
                    return;
                }
                return res.json();
            })
            .then(data => {
                showToast(data?.message || 'Cảm ơn bạn đã báo cáo. Chúng tôi đã tiếp nhận thông tin.');
            })
            .catch(err => {
                console.error(err);
                showToast('Lỗi gửi báo cáo');
            });
        });
    }

    // 4.10 Meta AI Modal Interaction
    let currentMetaAiContext = { threadId: null, author: '', content: '' };

    document.addEventListener('click', function(e) {
        const aiBtn = e.target.closest('.btn-ask-meta-ai');
        if (!aiBtn) return;
        e.preventDefault();

        currentMetaAiContext = {
            threadId: aiBtn.getAttribute('data-thread-id'),
            author: aiBtn.getAttribute('data-author-name') || 'người dùng',
            content: aiBtn.getAttribute('data-thread-content') || ''
        };

        const authorEl = document.getElementById('metaAiThreadAuthor');
        const snippetEl = document.getElementById('metaAiThreadSnippet');
        const chatBody = document.getElementById('metaAiChatBody');
        const inputEl = document.getElementById('metaAiInput');

        if (authorEl) authorEl.textContent = '@' + currentMetaAiContext.author;
        if (snippetEl) snippetEl.textContent = currentMetaAiContext.content || '(Bài viết không có văn bản)';
        if (inputEl) inputEl.value = '';

        if (chatBody) {
            chatBody.innerHTML = `
                <div class="meta-ai-welcome-msg">
                    <p class="text-secondary small mb-0">Chào bạn! Tôi có thể tóm tắt, giải thích ngữ cảnh hoặc dịch nội dung bài viết này. Hãy chọn gợi ý bên trên hoặc đặt câu hỏi bên dưới.</p>
                </div>
            `;
        }

        const modalEl = document.getElementById('metaAiModal');
        if (modalEl && window.bootstrap) {
            const modal = new bootstrap.Modal(modalEl);
            modal.show();
        }
    });

    function sendMetaAiQuery(queryText) {
        if (!queryText || !queryText.trim()) return;
        const chatBody = document.getElementById('metaAiChatBody');
        const inputEl = document.getElementById('metaAiInput');
        if (!chatBody) return;

        // Append User Message
        const userMsg = document.createElement('div');
        userMsg.className = 'meta-ai-msg-bubble meta-ai-msg-user';
        userMsg.textContent = queryText;
        chatBody.appendChild(userMsg);
        chatBody.scrollTop = chatBody.scrollHeight;

        if (inputEl) inputEl.value = '';

        // Append Typing Indicator
        const typingEl = document.createElement('div');
        typingEl.className = 'meta-ai-typing-indicator';
        typingEl.innerHTML = '<div class="meta-ai-typing-dot"></div><div class="meta-ai-typing-dot"></div><div class="meta-ai-typing-dot"></div>';
        chatBody.appendChild(typingEl);
        chatBody.scrollTop = chatBody.scrollHeight;

        // Generate Contextual Response after 600ms
        setTimeout(() => {
            typingEl.remove();
            const botMsg = document.createElement('div');
            botMsg.className = 'meta-ai-msg-bubble meta-ai-msg-bot';

            const rawContent = currentMetaAiContext.content;
            const author = currentMetaAiContext.author;
            const q = queryText.toLowerCase();

            if (q.includes('tóm tắt') || q.includes('tom tat') || q.includes('summary')) {
                botMsg.innerHTML = `<strong>📌 Tóm tắt chính:</strong><br>${rawContent ? rawContent : 'Bài viết ngắn gọn, tập trung truyền tải thông điệp nhanh.'}<br><br><span class="text-secondary small">💡 Bài viết của @${author} đang nhận được sự tương tác của cộng đồng Threads.</span>`;
            } else if (q.includes('bối cảnh') || q.includes('giải thích') || q.includes('context')) {
                botMsg.innerHTML = `<strong>🔍 Phân tích bối cảnh:</strong><br>Bài viết này được tác giả @${author} chia sẻ nhằm thảo luận về quan điểm đời sống hoặc các vấn đề thịnh hành. Giọng điệu chân thật, tạo không gian để người đọc cùng bình luận và trao đổi.`;
            } else if (q.includes('dịch') || q.includes('tiếng anh') || q.includes('english')) {
                botMsg.innerHTML = `<strong>🌐 English translation:</strong><br><em>"${rawContent}"</em><br><br><span class="text-secondary small">*(Bản dịch tự động được tạo bởi Meta AI)*</span>`;
            } else {
                botMsg.innerHTML = `<strong>🤖 Meta AI:</strong><br>Về câu hỏi của bạn đối với bài đăng của @${author}: Bài viết mang thông điệp thảo luận cởi mở. Bạn có thể nhấn theo dõi tác giả hoặc để lại một câu trả lời để đóng góp thêm góc nhìn nhé!`;
            }

            chatBody.appendChild(botMsg);
            chatBody.scrollTop = chatBody.scrollHeight;
        }, 650);
    }

    // Meta AI Prompt chips click
    document.addEventListener('click', function(e) {
        const chip = e.target.closest('.meta-ai-chip-btn');
        if (!chip) return;
        e.preventDefault();
        const query = chip.getAttribute('data-query');
        sendMetaAiQuery(query);
    });

    // Meta AI Form submit
    const metaAiForm = document.getElementById('metaAiChatForm');
    if (metaAiForm) {
        metaAiForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const input = document.getElementById('metaAiInput');
            sendMetaAiQuery(input ? input.value : '');
        });
    }

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

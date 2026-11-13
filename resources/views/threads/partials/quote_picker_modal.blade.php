<!-- Quote Picker Modal (Matches Meta Threads "Chọn bài viết để trích dẫn" exactly) -->
<div class="modal fade" id="quotePickerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 620px;">
        <div class="modal-content quote-picker-modal-content">
            
            <!-- Header: Back Arrow (left) | Title (center) -->
            <div class="quote-picker-header">
                <button type="button" class="quote-picker-back-btn" id="btnQuotePickerBack" title="Quay lại">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                        <line x1="19" y1="12" x2="5" y2="12"></line>
                        <polyline points="12 19 5 12 12 5"></polyline>
                    </svg>
                </button>
                <h5 class="quote-picker-title mb-0">Chọn bài viết để trích dẫn</h5>
                <div style="width: 28px;"></div> <!-- Spacer to keep title perfectly centered -->
            </div>

            <!-- Tab Navigation: Đã lưu | Đã thích | Bài viết của bạn -->
            <div class="quote-picker-tabs">
                <button type="button" class="quote-tab-btn active" data-tab="saved" id="tabQuoteSaved">
                    <span>Đã lưu</span>
                </button>
                <button type="button" class="quote-tab-btn" data-tab="liked" id="tabQuoteLiked">
                    <span>Đã thích</span>
                </button>
                <button type="button" class="quote-tab-btn" data-tab="mine" id="tabQuoteMine">
                    <span>Bài viết của bạn</span>
                </button>
            </div>

            <!-- Tab Content Area -->
            <div class="quote-picker-body" id="quotePickerBody">
                
                <!-- Loading Spinner -->
                <div id="quotePickerLoading" class="quote-picker-loading text-center py-5" style="display: none;">
                    <div class="spinner-border text-secondary spinner-border-sm" role="status"></div>
                </div>

                <!-- Empty State (Matches screenshot exactly: "Bài viết bạn lưu sẽ hiển thị ở đây.") -->
                <div id="quotePickerEmpty" class="quote-picker-empty-state">
                    <span id="quotePickerEmptyText">Bài viết bạn lưu sẽ hiển thị ở đây.</span>
                </div>

                <!-- List of Selectable Threads -->
                <div id="quotePickerList" class="quote-picker-list" style="display: none;"></div>

            </div>

        </div>
    </div>
</div>

<style>
/* Quote Picker Modal Container */
.quote-picker-modal-content {
    background: var(--journal-surface) !important;
    border: 1px solid var(--journal-line) !important;
    border-radius: 20px !important;
    color: var(--journal-text) !important;
    box-shadow: var(--journal-shadow) !important;
    overflow: hidden;
    min-height: 440px;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
}

/* Header */
.quote-picker-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 14px 18px;
    border-bottom: 1px solid var(--journal-line);
}
.quote-picker-back-btn {
    background: transparent;
    border: none;
    color: var(--journal-text);
    display: flex;
    align-items: center;
    justify-content: center;
    width: 28px;
    height: 28px;
    padding: 0;
    cursor: pointer;
    border-radius: 50%;
    transition: opacity 0.15s, background 0.15s;
}
.quote-picker-back-btn:hover {
    background: color-mix(in srgb, var(--journal-text) 8%, transparent);
}
.quote-picker-title {
    font-size: 16px;
    font-weight: 700;
    color: var(--journal-text);
    letter-spacing: -0.2px;
}

/* Tabs */
.quote-picker-tabs {
    display: flex;
    border-bottom: 1px solid var(--journal-line);
}
.quote-tab-btn {
    flex: 1;
    background: transparent;
    border: none;
    color: var(--journal-faint);
    font-size: 14.5px;
    font-weight: 600;
    padding: 13px 0 11px;
    text-align: center;
    cursor: pointer;
    position: relative;
    transition: color 0.15s;
    user-select: none;
}
.quote-tab-btn:hover {
    color: var(--journal-muted);
}
.quote-tab-btn.active {
    color: var(--journal-text);
    font-weight: 700;
}
.quote-tab-btn.active::after {
    content: '';
    position: absolute;
    bottom: -1px;
    left: 0;
    right: 0;
    height: 2px;
    background: var(--journal-button-bg);
}

/* Body & Empty State */
.quote-picker-body {
    min-height: 360px;
    max-height: 520px;
    overflow-y: auto;
    position: relative;
}
.quote-picker-empty-state {
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 340px;
    padding: 20px;
    text-align: center;
}
#quotePickerEmptyText {
    color: var(--journal-faint);
    font-size: 14.5px;
    font-weight: 400;
    user-select: none;
}

/* Selectable Threads List */
.quote-picker-list {
    padding: 8px 12px;
}
.quote-item-card {
    display: flex;
    gap: 12px;
    padding: 12px 14px;
    border-radius: 14px;
    border: 1px solid transparent;
    cursor: pointer;
    transition: background 0.15s, border-color 0.15s;
    margin-bottom: 6px;
}
.quote-item-card:hover {
    background: color-mix(in srgb, var(--journal-text) 5%, transparent);
    border-color: var(--journal-line);
}
.quote-item-avatar {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    object-fit: cover;
    flex-shrink: 0;
    background: var(--journal-raised);
}
.quote-item-avatar-letter {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: var(--journal-raised);
    color: var(--journal-text);
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 14px;
    flex-shrink: 0;
    border: 1px solid var(--journal-line);
}
.quote-item-content {
    flex: 1;
    min-width: 0;
}
.quote-item-author-line {
    display: flex;
    align-items: center;
    gap: 6px;
    margin-bottom: 2px;
}
.quote-item-name {
    font-weight: 700;
    color: var(--journal-text);
    font-size: 14px;
}
.quote-item-time {
    color: var(--journal-faint);
    font-size: 12.5px;
}
.quote-item-text {
    color: var(--journal-text);
    font-size: 13.5px;
    line-height: 1.45;
    word-break: break-word;
}
.quote-item-thumb {
    width: 48px;
    height: 48px;
    border-radius: 8px;
    object-fit: cover;
    flex-shrink: 0;
    margin-left: 8px;
    border: 1px solid var(--journal-line);
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const quoteModalEl = document.getElementById('quotePickerModal');
    const newThreadModalEl = document.getElementById('newThreadModal');
    if (!quoteModalEl) return;

    let currentQuoteTab = 'saved';
    const emptyTexts = {
        saved: 'Bài viết bạn lưu sẽ hiển thị ở đây.',
        liked: 'Bài viết bạn thích sẽ hiển thị ở đây.',
        mine: 'Bài viết của bạn sẽ hiển thị ở đây.'
    };

    const quoteTabs = document.querySelectorAll('.quote-tab-btn');
    const emptyContainer = document.getElementById('quotePickerEmpty');
    const emptyTextSpan = document.getElementById('quotePickerEmptyText');
    const listContainer = document.getElementById('quotePickerList');
    const loadingContainer = document.getElementById('quotePickerLoading');
    const btnBack = document.getElementById('btnQuotePickerBack');

    // Switch Tabs
    quoteTabs.forEach(tabBtn => {
        tabBtn.addEventListener('click', function() {
            quoteTabs.forEach(t => t.classList.remove('active'));
            this.classList.add('active');
            currentQuoteTab = this.getAttribute('data-tab');
            loadQuotePickerThreads(currentQuoteTab);
        });
    });

    // Back button goes back to newThreadModal
    if (btnBack) {
        btnBack.addEventListener('click', function() {
            if (window.bootstrap) {
                bootstrap.Modal.getInstance(quoteModalEl)?.hide();
                if (newThreadModalEl) {
                    const composerModal = bootstrap.Modal.getInstance(newThreadModalEl) || new bootstrap.Modal(newThreadModalEl);
                    composerModal.show();
                }
            }
        });
    }

    // Trigger opening quote picker from Quote button in composer
    const modalAddQuoteBtn = document.getElementById('modalAddQuoteBtn');
    if (modalAddQuoteBtn) {
        modalAddQuoteBtn.addEventListener('click', function(e) {
            e.preventDefault();
            if (window.bootstrap) {
                bootstrap.Modal.getInstance(newThreadModalEl)?.hide();
                const quoteModal = bootstrap.Modal.getInstance(quoteModalEl) || new bootstrap.Modal(quoteModalEl);
                quoteModal.show();
                loadQuotePickerThreads(currentQuoteTab);
            }
        });
    }

    function loadQuotePickerThreads(tab) {
        if (loadingContainer) loadingContainer.style.display = 'block';
        if (emptyContainer) emptyContainer.style.display = 'none';
        if (listContainer) listContainer.style.display = 'none';

        fetch(`/api/quote-picker-threads?tab=${tab}`, {
            headers: {
                'Accept': 'application/json'
            }
        })
        .then(res => res.json())
        .then(threads => {
            if (loadingContainer) loadingContainer.style.display = 'none';

            if (!threads || threads.length === 0) {
                if (emptyTextSpan) {
                    emptyTextSpan.textContent = emptyTexts[tab] || 'Không có bài viết nào.';
                }
                if (emptyContainer) emptyContainer.style.display = 'flex';
                if (listContainer) listContainer.style.display = 'none';
                return;
            }

            // Render list
            if (emptyContainer) emptyContainer.style.display = 'none';
            if (listContainer) {
                listContainer.innerHTML = '';
                threads.forEach(t => {
                    const card = document.createElement('div');
                    card.className = 'quote-item-card';
                    card.setAttribute('data-thread-id', t.id);

                    const avatarHtml = t.author_avatar 
                        ? `<img src="${t.author_avatar}" class="quote-item-avatar" alt="${t.author_name}">`
                        : `<div class="quote-item-avatar-letter">${(t.author_name || 'U').charAt(0).toUpperCase()}</div>`;

                    const thumbHtml = t.media_url 
                        ? `<img src="${t.media_url}" class="quote-item-thumb" alt="Ảnh đính kèm">`
                        : '';

                    card.innerHTML = `
                        ${avatarHtml}
                        <div class="quote-item-content">
                            <div class="quote-item-author-line">
                                <span class="quote-item-name">${t.author_username || t.author_name}</span>
                                <span class="quote-item-time">· ${t.time_ago}</span>
                            </div>
                            <div class="quote-item-text">${t.content || 'Bài viết đính kèm hình ảnh'}</div>
                        </div>
                        ${thumbHtml}
                    `;

                    // When clicked, select this thread to quote!
                    card.addEventListener('click', function() {
                        selectThreadToQuote(t);
                    });

                    listContainer.appendChild(card);
                });
                listContainer.style.display = 'block';
            }
        })
        .catch(err => {
            console.error(err);
            if (loadingContainer) loadingContainer.style.display = 'none';
            if (emptyTextSpan) {
                emptyTextSpan.textContent = emptyTexts[tab] || 'Không thể tải bài viết.';
            }
            if (emptyContainer) emptyContainer.style.display = 'flex';
        });
    }

    function selectThreadToQuote(thread) {
        if (!thread) return;

        // Set quote in composer form
        const repostInput = document.getElementById('modalRepostOfId');
        const postTypeInput = document.getElementById('modalPostType');
        const titleHeading = document.getElementById('newThreadModalTitle');
        const quoteContainer = document.getElementById('modalQuotedThreadContainer');

        if (repostInput) repostInput.value = thread.id;
        if (postTypeInput) postTypeInput.value = 'quote';
        if (titleHeading) titleHeading.textContent = 'Trích dẫn';

        const submitBtn = document.getElementById('modalSubmitBtn');
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.classList.add('is-active');
        }

        if (quoteContainer) {
            const avatarHtml = thread.author_avatar 
                ? `<img src="${thread.author_avatar}" style="width: 22px; height: 22px; border-radius: 50%; object-fit: cover;" alt="${thread.author_name}">`
                : `<div style="width: 22px; height: 22px; border-radius: 50%; background: #333; color: #fff; font-size: 11px; display: inline-flex; align-items: center; justify-content: center;">${(thread.author_name || 'U').charAt(0).toUpperCase()}</div>`;

            quoteContainer.style.display = 'block';
            quoteContainer.innerHTML = `
                <div class="d-flex align-items-center justify-content-between mb-1">
                    <div class="d-flex align-items-center gap-2">
                        ${avatarHtml}
                        <span class="fw-bold text-white fs-6">${thread.author_username || thread.author_name}</span>
                        <span class="text-secondary small">· ${thread.time_ago}</span>
                    </div>
                    <button type="button" class="btn btn-sm text-secondary p-0 border-0" id="btnCancelQuote" title="Gỡ trích dẫn">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                    </button>
                </div>
                <div class="text-secondary small text-truncate-2">${thread.content || 'Bài viết đính kèm hình ảnh'}</div>
            `;

            document.getElementById('btnCancelQuote')?.addEventListener('click', function(e) {
                e.preventDefault();
                quoteContainer.style.display = 'none';
                quoteContainer.innerHTML = '';
                if (repostInput) repostInput.value = '';
                if (postTypeInput) postTypeInput.value = 'post';
                if (titleHeading) titleHeading.textContent = 'Thread mới';
                if (typeof window.updateComposerSubmitState === 'function') {
                    window.updateComposerSubmitState();
                }
            });
        }

        // Close quote picker modal & reopen composer modal
        if (window.bootstrap) {
            bootstrap.Modal.getInstance(quoteModalEl)?.hide();
            if (newThreadModalEl) {
                const composerModal = bootstrap.Modal.getInstance(newThreadModalEl) || new bootstrap.Modal(newThreadModalEl);
                composerModal.show();
                setTimeout(() => {
                    document.getElementById('modalComposerText')?.focus();
                }, 300);
            }
        }
    }

    // Expose global for feed quote triggers
    window.openQuotePickerForThread = function(thread) {
        selectThreadToQuote(thread);
    };
});
</script>

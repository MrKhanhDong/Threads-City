import { Modal } from 'bootstrap';
import { composerValidation, threadRequest } from './thread-api';
import { composerText, createThreadEditor, plainComposerHtml } from './thread-editor';

export function setupThreadStream() {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
    const modalElement = document.querySelector('[data-stream-composer]');
    const toast = document.querySelector('[data-stream-toast]');
    let toastTimer;
    const notify = message => {
        if (!toast) return;
        toast.textContent = message;
        toast.hidden = false;
        clearTimeout(toastTimer);
        toastTimer = setTimeout(() => { toast.hidden = true; }, 4200);
    };
    const request = (url, options = {}) => threadRequest(url, { ...options, csrf });
    let openComposer;

    if (modalElement) {
        const modal = Modal.getOrCreateInstance(modalElement);
        const form = modalElement.querySelector('[data-stream-form]');
        const textarea = form.querySelector('[data-compose-text]');
        const submit = form.querySelector('[data-compose-submit]');
        const attach = form.querySelector('[data-compose-attach]');
        const fileInput = form.querySelector('[data-compose-file]');
        const mediaContainer = form.querySelector('[data-compose-media]');
        const continuationContainer = form.querySelector('[data-compose-continuations]');
        const addButton = form.querySelector('[data-compose-add]');
        const errorBox = form.querySelector('[data-compose-error]');
        const contextBox = form.querySelector('[data-compose-context]');
        const draftKey = `thread-draft-${modalElement.dataset.userId}`;
        let state = { mode: 'new', target: null, updateUrl: '', media: [], busy: false };
        let continuationIndex = 0;
        const editors = new Map();
        const pendingEditors = new Map();
        let updatingEditor = false;
        const continuationTexts = () => [...continuationContainer.querySelectorAll('textarea')].map(input => input.value);
        const continuationPlainTexts = () => continuationTexts().map(value => composerText(value));
        const setContent = (input, html) => {
            updatingEditor = true;
            try { input.value = html; editors.get(input)?.setContent(html); }
            finally { updatingEditor = false; }
        };
        const showError = message => { errorBox.textContent = message; errorBox.hidden = !message; };
        const saveDraft = () => {
            if (state.mode !== 'new') return;
            try { sessionStorage.setItem(draftKey, JSON.stringify({ content: textarea.value, content_format: 'html', media: state.media, continuations: continuationTexts() })); } catch {}
        };
        const refresh = () => {
            const count = Array.from(composerText(textarea.value)).length;
            const countLabel = form.querySelector('[data-compose-count]');
            countLabel.textContent = `${count}/500`;
            countLabel.classList.toggle('is-over-limit', count > 500);
            continuationContainer.querySelectorAll('textarea').forEach(input => {
                const length = Array.from(composerText(input.value)).length;
                const label = input.closest('.stream-continuation').querySelector('[data-continuation-count]');
                label.textContent = `${length}/500`;
                label.classList.toggle('is-over-limit', length > 500);
            });
            submit.disabled = state.busy || pendingEditors.size > 0 || !editors.has(textarea) || Boolean(composerValidation(composerText(textarea.value), state.media.length, continuationPlainTexts()));
            editors.forEach(editor => {
                const mode = state.busy ? 'readonly' : 'design';
                if (editor.mode.get() !== mode) editor.mode.set(mode);
            });
            attach.disabled = state.busy || state.media.length >= 4;
            addButton.disabled = state.busy || continuationTexts().length >= 9;
            addButton.hidden = state.mode === 'edit';
            submit.textContent = state.busy ? 'Đang xử lý…' : (state.mode === 'edit' ? 'Lưu thay đổi' : 'Đăng');
        };
        const ensureEditor = input => {
            if (editors.has(input)) return Promise.resolve(editors.get(input));
            if (pendingEditors.has(input)) return pendingEditors.get(input);
            const pending = createThreadEditor(input, modalElement, () => { if (!updatingEditor) { showError(''); refresh(); saveDraft(); } })
                .then(editor => { if (editor) editors.set(input, editor); return editor; })
                .catch(() => { showError('Chưa tải được trình soạn thảo. Nội dung vẫn được giữ lại; hãy đóng và mở lại cửa sổ để thử lại.'); return null; })
                .finally(() => { pendingEditors.delete(input); refresh(); });
            pendingEditors.set(input, pending);
            refresh();
            return pending;
        };
        const renderMedia = () => {
            mediaContainer.replaceChildren();
            state.media.forEach(media => {
                const item = document.createElement('div');
                item.className = 'stream-preview-image';
                const image = document.createElement('img');
                image.src = media.url;
                image.alt = media.alt || 'Ảnh đính kèm';
                const remove = document.createElement('button');
                remove.type = 'button';
                remove.textContent = '×';
                remove.setAttribute('aria-label', 'Gỡ ảnh đính kèm');
                remove.addEventListener('click', () => {
                    if (state.busy) return;
                    state.media = state.media.filter(item => item.id !== media.id);
                    renderMedia(); refresh(); saveDraft();
                });
                item.append(image, remove);
                mediaContainer.append(item);
            });
        };
        const addContinuation = (value = '') => {
            const row = document.createElement('div');
            row.className = 'stream-continuation';
            const label = document.createElement('label');
            const input = document.createElement('textarea');
            input.id = `stream-continuation-${++continuationIndex}`;
            input.name = 'continuations[]';
            input.rows = 3;
            input.placeholder = 'Thêm vào câu chuyện…';
            input.value = value;
            label.htmlFor = input.id;
            label.textContent = 'Bài tiếp theo trong chuỗi';
            const count = document.createElement('span');
            count.className = 'stream-counter';
            count.dataset.continuationCount = '';
            const remove = document.createElement('button');
            remove.type = 'button';
            remove.className = 'stream-remove-continuation';
            remove.textContent = '×';
            remove.setAttribute('aria-label', 'Gỡ bài khỏi chuỗi');
            remove.addEventListener('click', () => { if (state.busy) return; editors.get(input)?.remove(); editors.delete(input); row.remove(); refresh(); saveDraft(); });
            row.append(label, input, count, remove);
            continuationContainer.append(row);
            refresh();
            return input;
        };
        const reset = () => {
            setContent(textarea, '');
            state.media = [];
            continuationContainer.querySelectorAll('textarea').forEach(input => { editors.get(input)?.remove(); editors.delete(input); });
            continuationContainer.replaceChildren();
            renderMedia(); showError('');
        };
        const restoreDraft = () => {
            try {
                const draft = JSON.parse(sessionStorage.getItem(draftKey) || 'null');
                if (!draft) return;
                const html = value => draft.content_format === 'html' ? String(value) : plainComposerHtml(value);
                setContent(textarea, typeof draft.content === 'string' ? html(draft.content) : '');
                state.media = Array.isArray(draft.media) ? draft.media.slice(0, 4) : [];
                (draft.continuations || []).slice(0, 9).forEach(value => addContinuation(html(value)));
            } catch {}
        };
        openComposer = trigger => {
            if (state.busy) return;
            const mode = trigger.dataset.compose || 'new';
            const target = trigger.dataset.targetId || null;
            if (mode !== state.mode || target !== state.target || mode === 'edit') {
                if ((composerText(textarea.value) || state.media.length || continuationTexts().length) && !window.confirm('Đổi sang bài khác? Bản đang soạn trong cửa sổ này sẽ được thay thế.')) return;
                saveDraft(); reset();
                state.mode = mode; state.target = target; state.updateUrl = '';
                if (mode === 'new') {
                    restoreDraft();
                }
            }
            if (mode === 'edit') {
                try {
                    const edit = JSON.parse(trigger.dataset.editThread);
                    state.target = String(edit.id); state.updateUrl = edit.url; setContent(textarea, edit.content); state.media = edit.media;
                } catch { showError('Chưa mở được bài viết để sửa.'); return; }
            }
            contextBox.textContent = trigger.dataset.context || '';
            contextBox.hidden = !contextBox.textContent;
            modalElement.querySelector('.modal-title').textContent = ({ new: 'Bài viết mới', reply: 'Trả lời', quote: 'Trích dẫn', edit: 'Chỉnh sửa bài viết' })[mode];
            renderMedia(); refresh(); modal.show();
        };
        modalElement.addEventListener('shown.bs.modal', async () => {
            const inputs = [textarea, ...continuationContainer.querySelectorAll('textarea')];
            await Promise.all(inputs.map(ensureEditor));
            editors.get(textarea)?.focus();
        });
        document.addEventListener('focusin', event => {
            if (modalElement.classList.contains('show') && event.target.closest('.tox-tinymce-aux')) event.stopImmediatePropagation();
        });
        modalElement.addEventListener('hide.bs.modal', event => { if (state.busy) event.preventDefault(); else saveDraft(); });
        form.addEventListener('input', () => { showError(''); refresh(); saveDraft(); });
        addButton.addEventListener('click', async () => {
            if (!state.busy && continuationTexts().length < 9) {
                const input = addContinuation();
                saveDraft();
                (await ensureEditor(input))?.focus();
            }
        });
        attach.addEventListener('click', () => fileInput.click());
        fileInput.addEventListener('change', async () => {
            const files = [...fileInput.files];
            fileInput.value = '';
            if (!files.length) return;
            if (files.length + state.media.length > 4) { showError('Mỗi bài viết tối đa 4 ảnh.'); return; }
            if (files.some(file => !['image/jpeg', 'image/png', 'image/webp'].includes(file.type) || file.size > 5 * 1024 * 1024)) { showError('Chọn ảnh JPEG, PNG hoặc WebP, tối đa 5 MB mỗi ảnh.'); return; }
            state.busy = true; showError(''); refresh();
            try {
                for (const file of files) {
                    const data = new FormData(); data.append('image', file);
                    const media = await request(modalElement.dataset.uploadUrl, { method: 'POST', body: data });
                    if (!Number.isInteger(media.id) || typeof media.url !== 'string') throw new Error('Chưa tải ảnh thành công. Vui lòng thử lại.');
                    state.media.push(media); renderMedia(); saveDraft();
                }
            } catch (error) { showError(error.message); }
            finally { state.busy = false; refresh(); }
        });
        form.addEventListener('submit', async event => {
            event.preventDefault();
            if (state.busy || pendingEditors.size || !editors.has(textarea)) return;
            const validation = composerValidation(composerText(textarea.value), state.media.length, continuationPlainTexts());
            if (validation) { showError(validation); return; }
            const body = { content: textarea.value.trim(), content_format: 'html', media_ids: state.media.map(media => media.id), continuations: continuationTexts().map(text => text.trim()) };
            if (state.mode === 'reply') body.parent_id = Number(state.target);
            if (state.mode === 'quote') body.repost_of_id = Number(state.target);
            state.busy = true; showError(''); refresh();
            try {
                const data = await request(state.mode === 'edit' ? state.updateUrl : modalElement.dataset.storeUrl, { method: state.mode === 'edit' ? 'PATCH' : 'POST', body });
                if (!data.success || typeof data.html !== 'string') throw new Error('Máy chủ chưa xác nhận bài viết. Nội dung vẫn được giữ lại.');
                if (state.mode === 'edit') {
                    document.querySelectorAll(`[data-thread-card="${state.target}"]`).forEach(card => card.outerHTML = data.html);
                } else if (state.mode === 'reply') {
                    const replies = document.querySelector(`[data-stream-replies="${state.target}"]`);
                    replies?.insertAdjacentHTML('beforeend', data.html);
                    document.querySelector('[data-no-replies]')?.remove();
                    document.querySelectorAll(`[data-post-id="${state.target}"] [data-reply-count]`).forEach(count => count.textContent = String(data.parent_replies_count ?? Number(count.textContent || 0) + 1));
                    const total = document.querySelector('[data-thread-replies-total]');
                    if (total) total.textContent = String(data.parent_replies_count ?? Number(total.textContent || 0) + 1);
                } else {
                    const feed = document.querySelector('[data-stream-primary="for-you"]');
                    feed?.insertAdjacentHTML('afterbegin', data.html);
                    if (feed) document.querySelector('.empty-state')?.remove();
                }
                if (data.repost_of_id && Number.isInteger(data.original_reposts_count)) {
                    document.querySelectorAll(`[data-post-id="${data.repost_of_id}"] [data-repost-count]`).forEach(count => count.textContent = data.original_reposts_count || '');
                }
                if (state.mode === 'new') { try { sessionStorage.removeItem(draftKey); } catch {} }
                const shouldNavigate = state.mode !== 'edit' && state.mode !== 'reply' && !document.querySelector('[data-stream-primary]');
                notify(state.mode === 'edit' ? 'Đã cập nhật bài viết.' : 'Đã đăng bài viết.');
                reset(); state.busy = false; modal.hide();
                if (shouldNavigate && data.url) window.location.assign(data.url);
            } catch (error) { showError(error.message); }
            finally { state.busy = false; refresh(); }
        });
        restoreDraft();
        renderMedia(); refresh();
    }

    document.addEventListener('click', event => {
        const trigger = event.target.closest('[data-compose]');
        if (!trigger || !openComposer) return;
        event.preventDefault(); openComposer(trigger);
    });
    document.addEventListener('submit', async event => {
        const form = event.target.closest('form[data-stream-action]');
        if (!form || event.defaultPrevented) return;
        event.preventDefault();
        const button = form.querySelector('button[type="submit"]');
        if (button?.disabled) return;
        const card = form.closest('[data-thread-card]');
        const action = form.dataset.streamAction;
        if (button) button.disabled = true;
        try {
            const data = await request(form.action, { method: action === 'delete' ? 'DELETE' : 'POST' });
            if (!data.success) throw new Error('Chưa hoàn tất thao tác. Vui lòng thử lại.');
            const cards = card ? document.querySelectorAll(`[data-post-id="${card.dataset.postId}"]`) : [];
            if (action === 'like') cards.forEach(item => { item.querySelectorAll('[data-stream-action="like"] button').forEach(button => button.setAttribute('aria-pressed', String(data.liked))); item.querySelectorAll('[data-like-count]').forEach(count => count.textContent = data.likes_count || ''); });
            if (action === 'bookmark') { form.querySelector('[data-bookmark-label]').textContent = data.bookmarked ? 'Bỏ lưu bài viết' : 'Lưu bài viết'; button.setAttribute('aria-pressed', String(data.bookmarked)); notify(data.bookmarked ? 'Đã lưu bài viết.' : 'Đã bỏ lưu.'); }
            if (action === 'repost') cards.forEach(item => { item.querySelectorAll('[data-repost-count]').forEach(count => { count.textContent = data.reposts_count || ''; count.closest('button').setAttribute('aria-pressed', String(data.reposted)); }); item.querySelectorAll('[data-repost-label]').forEach(label => label.textContent = data.reposted ? 'Hủy đăng lại' : 'Đăng lại'); });
            if (action === 'follow') { button.textContent = data.following ? 'Đang theo dõi' : 'Theo dõi'; button.setAttribute('aria-pressed', String(data.following)); const context = form.closest('[data-follow-context]') || document; context.querySelectorAll('[data-followers-count]').forEach(count => count.textContent = data.followers_count); }
            if (action === 'delete') { const isRoot = document.querySelector('[data-stream-replies]')?.dataset.streamReplies === card?.dataset.threadCard; card?.remove(); notify('Đã xóa bài viết.'); if (isRoot) window.location.assign(data.redirect); }
        } catch (error) { notify(error.message); }
        finally { if (button) button.disabled = false; }
    });
    document.querySelectorAll('[data-stream-more]').forEach(link => link.addEventListener('click', async event => {
        event.preventDefault();
        if (link.getAttribute('aria-disabled') === 'true') return;
        link.setAttribute('aria-disabled', 'true'); link.textContent = 'Đang tải…';
        try {
            const data = await request(link.href);
            if (typeof data.html !== 'string') throw new Error('Chưa tải được bài viết.');
            document.querySelector('[data-stream-feed]')?.insertAdjacentHTML('beforeend', data.html);
            if (data.nextPageUrl) link.href = data.nextPageUrl;
            else link.parentElement.remove();
        } catch (error) { notify(error.message); }
        finally { link.setAttribute('aria-disabled', 'false'); link.textContent = 'Tải thêm bài viết'; }
    }));
}

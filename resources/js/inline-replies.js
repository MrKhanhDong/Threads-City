import { threadRequest } from './thread-api.js';
import { uploadThreadImage } from './image-upload.js';

export function replyValidation(content, attachments, uploading) {
    if (uploading) return 'Chờ ảnh tải xong trước khi gửi.';
    if (Array.from(content.trim()).length > 2000) return 'Câu trả lời tối đa 2.000 ký tự.';
    return content.trim() || attachments ? '' : 'Nhập nội dung hoặc đính kèm một ảnh để trả lời.';
}

export function setupInlineReplies(doc = document, win = window) {
    const template = doc.getElementById('inline-reply-template');
    if (!template) return;
    const drafts = new WeakMap();
    let formSequence = 0;
    const csrf = doc.querySelector('meta[name="csrf-token"]').content;
    const home = doc.querySelector('[data-reply-home]');
    const detailSlot = home?.querySelector?.('[data-thread-detail-composer]');
    const status = (form, text, error = false) => {
        const output = form.querySelector('[data-reply-status]');
        output.textContent = text; output.classList.toggle('is-error', error);
    };
    const autoSize = input => { input.style.height = 'auto'; input.style.height = `${Math.min(180, input.scrollHeight)}px`; };
    const renderMedia = form => {
        const media = form.querySelector('[data-reply-media]'); media.replaceChildren();
        drafts.get(form).media.forEach((item, index) => {
            const tile = doc.createElement('span');
            const image = doc.createElement('img'); image.src = item.url; image.alt = 'Ảnh đính kèm';
            const remove = doc.createElement('button'); remove.type = 'button'; remove.dataset.replyRemove = String(index); remove.textContent = '✕'; remove.setAttribute('aria-label', 'Gỡ ảnh đính kèm');
            tile.append(image, remove); media.append(tile);
        });
    };
    const close = form => {
        const draft = drafts.get(form);
        form.hidden = true;
        draft.trigger?.setAttribute('aria-expanded', 'false');
        const prompt = form.dataset?.detailReply === '1' ? home?.querySelector('[data-thread-detail-prompt]') : null;
        if (prompt) prompt.hidden = false;
        (prompt || draft.trigger)?.focus({ preventScroll: true });
    };
    const open = (trigger, { focus = true } = {}) => {
        const card = trigger.closest('[data-thread-id]');
        const isDetailReply = home?.dataset.replyHome === trigger.dataset.inlineReply && detailSlot;
        const slot = isDetailReply ? detailSlot : card?.querySelector('[data-inline-reply-slot]');
        if (!slot) return;
        let form = slot.querySelector('[data-inline-reply-form]');
        if (!form) {
            form = template.content.firstElementChild.cloneNode(true);
            form.id = `inline-reply-${++formSequence}`;
            form.elements.parent_id.value = trigger.dataset.inlineReply;
            form.querySelector('[data-reply-recipient]').textContent = trigger.dataset.replyAuthor;
            form.querySelector('[data-reply-context]').textContent = card.querySelector('.thread-content-text')?.textContent.trim().replace(/\s+/g, ' ').slice(0, 140) || 'Bài viết có nội dung đính kèm';
            if (isDetailReply) { form.dataset.detailReply = '1'; form.elements.content.placeholder = `Trả lời ${trigger.dataset.replyAuthor}…`; }
            slot.append(form); drafts.set(form, { media: [], uploading: 0, sending: false });
        }
        form.hidden = false;
        trigger.setAttribute('aria-controls', form.id); trigger.setAttribute('aria-expanded', 'true');
        drafts.get(form).trigger = trigger;
        drafts.get(form).card = card;
        if (isDetailReply) { const prompt = home.querySelector('[data-thread-detail-prompt]'); if (prompt) prompt.hidden = true; }
        if (focus) {
            form.querySelector('[data-reply-input]').focus({ preventScroll: true });
            form.scrollIntoView({ block: 'nearest', behavior: win.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth' });
        }
        return form;
    };
    doc.addEventListener('click', event => {
        const trigger = event.target.closest('[data-inline-reply]');
        if (trigger && !event.ctrlKey && !event.metaKey && !event.shiftKey && !event.altKey) {
            const card = trigger.closest('[data-thread-id]');
            if (!doc.querySelector('[data-reply-home]') && !card?.dataset.threadParent) return;
            event.preventDefault(); open(trigger); return;
        }
        if (event.target.closest('[data-thread-detail-prompt]')) {
            event.preventDefault();
            const primary = doc.querySelector(`[data-live-region="thread"] [data-inline-reply="${home?.dataset.replyHome}"]`);
            if (primary) open(primary);
            return;
        }
        const form = event.target.closest('[data-inline-reply-form]');
        if (!form) return;
        const draft = drafts.get(form);
        if (event.target.closest('[data-reply-close]')) { if (!draft.sending && !draft.uploading) close(form); }
        if (event.target.closest('[data-reply-attach]') && !draft.sending) form.querySelector('[data-reply-files]').click();
        const remove = event.target.closest('[data-reply-remove]');
        if (remove && !draft.sending) { draft.media.splice(Number(remove.dataset.replyRemove), 1); renderMedia(form); }
    });
    doc.addEventListener('input', event => { if (event.target.matches('[data-reply-input]')) autoSize(event.target); });
    doc.addEventListener('keydown', event => {
        if (!event.defaultPrevented && event.target.matches('[data-reply-input]') && (event.ctrlKey || event.metaKey) && event.key === 'Enter' && !event.isComposing) { event.preventDefault(); event.target.form.requestSubmit(); }
    });
    doc.addEventListener('change', async event => {
        if (!event.target.matches('[data-reply-files]')) return;
        const form = event.target.form; const draft = drafts.get(form);
        const files = [...event.target.files]; event.target.value = '';
        if (files.length + draft.media.length + draft.uploading > Number(form.dataset.mediaLimit)) { status(form, `Tối đa ${form.dataset.mediaLimit} ảnh cho mỗi câu trả lời.`, true); return; }
        draft.uploading += files.length;
        const failures = [];
        for (const file of files) {
            status(form, 'Đang tải ảnh…');
            try { draft.media.push(await uploadThreadImage(file, form.dataset.uploadUrl, csrf)); renderMedia(form); }
            catch (error) { failures.push(`${file.name}: ${error.message}`); }
            finally { draft.uploading--; }
        }
        status(form, failures.length ? failures.join(' ') : 'Ảnh đã sẵn sàng.', failures.length > 0);
    });
    doc.addEventListener('submit', async event => {
        const form = event.target.closest('[data-inline-reply-form]');
        if (!form) return;
        event.preventDefault();
        const draft = drafts.get(form); if (draft.sending) return;
        const input = form.elements.content;
        const validation = replyValidation(input.value, draft.media.length, draft.uploading);
        if (validation) { status(form, validation, true); return; }
        draft.sending = true;
        form.querySelectorAll('button, textarea').forEach(control => { control.disabled = true; });
        try {
            const body = new win.FormData(form); body.set('content', input.value);
            draft.media.forEach(media => body.append('media_ids[]', media.id));
            const data = await threadRequest(form.action, { method: 'POST', body, csrf });
            if (!data.success || !data.html) throw new Error('Chưa gửi được câu trả lời. Nội dung của bạn vẫn được giữ lại.');
            const isPageReply = home?.dataset.replyHome === form.elements.parent_id.value;
            const card = isPageReply ? (doc.querySelector('[data-live-region="thread"] [data-thread-url]') || draft.card) : draft.card;
            const results = isPageReply ? doc.querySelector('[data-live-region="replies"]') : card.querySelector('[data-inline-reply-results]');
            results?.querySelector('[data-no-replies]')?.remove();
            results?.insertAdjacentHTML('beforeend', data.html);
            const count = card.querySelector('[data-reply-count]');
            if (count) { const total = Number(count.dataset.replyCount) + 1; count.dataset.replyCount = String(total); count.textContent = total < 1000 ? String(total) : `${(total / 1000).toFixed(1).replace('.', ',')}K`; }
            if (isPageReply) { const total = doc.querySelector('[data-thread-replies-total]'); if (total) total.textContent = String(Number(total.textContent) + 1); }
            input.value = ''; draft.media = []; renderMedia(form); autoSize(input);
            form.querySelectorAll('input[name="mention_ids[]"]').forEach(item => item.remove());
            status(form, 'Đã gửi câu trả lời.');
            close(form);
            doc.dispatchEvent(new Event('threads:updated')); win.dispatchEvent(new Event('threads:refresh'));
        } catch (error) { status(form, error.message, true); }
        finally { draft.sending = false; form.querySelectorAll('button, textarea').forEach(control => { control.disabled = false; }); if (!form.hidden) input.focus({ preventScroll: true }); }
    });
    if (home) {
        const trigger = doc.querySelector(`[data-live-region="thread"] [data-inline-reply="${home.dataset.replyHome}"]`);
        if (trigger) open(trigger, { focus: win.location.hash === '#replyBox' });
    }
}

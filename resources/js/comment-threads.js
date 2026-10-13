import { requestErrorMessage } from './validation-messages.js';

export function replyBatchUrl(base, after = 0, focus = null) {
    const url = new URL(base);
    url.searchParams.set('after', String(after));
    if (focus !== null) url.searchParams.set('focus', String(focus));
    return url.toString();
}

export async function fetchReplyBatch(url, fetcher = fetch) {
    const response = await fetcher(url, { headers: { Accept: 'application/json' }, credentials: 'same-origin', cache: 'no-store' });
    const data = await response.json();
    if (!response.ok) throw new Error(data.message || 'Không tải được trả lời. Hãy thử lại.');
    if (typeof data.html !== 'string' || !Number.isSafeInteger(data.next) || typeof data.more !== 'boolean' || typeof data.earlier !== 'boolean') {
        throw new Error('Phản hồi trả lời không hợp lệ. Hãy tải lại trang.');
    }
    return data;
}

export function setupCommentThreads(doc = document) {
    doc.querySelectorAll('[data-comment-thread]').forEach(thread => {
        const panel = thread.querySelector('[data-replies-panel]');
        if (!panel) return;
        const toggle = thread.querySelector('[data-replies-toggle]');
        const more = thread.querySelector('[data-replies-more]');
        const earlier = thread.querySelector('[data-replies-earlier]');
        const list = thread.querySelector('[data-replies-list]');
        const status = thread.querySelector('[data-replies-status]');
        let busy = false;
        async function load({ reset = false, focus = null } = {}) {
            if (busy) return;
            busy = true;
            [toggle, more, earlier].forEach(button => { button.disabled = true; });
            status.textContent = 'Đang tải trả lời…';
            try {
                const data = await fetchReplyBatch(replyBatchUrl(thread.dataset.repliesUrl, reset ? 0 : Number(thread.dataset.next), focus));
                if (reset) list.replaceChildren();
                const template = doc.createElement('template');
                template.innerHTML = data.html;
                template.content.querySelectorAll('.comment[id]').forEach(comment => {
                    if (!list.querySelector('#' + comment.id)) list.append(comment);
                });
                thread.dataset.next = String(data.next);
                thread.dataset.loaded = '1';
                thread.dataset.more = data.more ? '1' : '0';
                more.hidden = !data.more;
                if (reset || focus !== null) earlier.hidden = !data.earlier;
                status.textContent = list.children.length ? (data.more ? '' : 'Đã hiển thị hết trả lời.') : 'Chưa có trả lời hiển thị.';
                if (focus !== null) doc.getElementById('comment-' + focus)?.scrollIntoView({ block: 'center' });
            } catch (error) {
                status.textContent = requestErrorMessage(error, 'Không tải được trả lời. Hãy kiểm tra kết nối rồi thử lại.');
            } finally {
                busy = false;
                [toggle, more, earlier].forEach(button => { button.disabled = false; });
            }
        }
        toggle.addEventListener('click', () => {
            panel.hidden = !panel.hidden;
            toggle.setAttribute('aria-expanded', String(!panel.hidden));
            toggle.textContent = panel.hidden ? 'Xem trả lời' : 'Ẩn bớt trả lời';
            if (!panel.hidden && thread.dataset.loaded !== '1') void load();
        });
        more.addEventListener('click', () => void load());
        earlier.addEventListener('click', () => void load({ reset: true }));
        thread.querySelector('[data-replies-collapse]')?.addEventListener('click', () => {
            panel.hidden = true;
            toggle.setAttribute('aria-expanded', 'false');
            toggle.textContent = 'Xem trả lời';
            toggle.focus?.();
        });
        thread.addEventListener('click', event => {
            const context = event.target.closest('.reply-context a');
            if (!context) return;
            const targetId = Number(context.hash.replace('#comment-', ''));
            if (Number.isSafeInteger(targetId) && !doc.getElementById('comment-' + targetId)) {
                event.preventDefault();
                void load({ reset: true, focus: targetId });
            }
        });
    });
}

export function setupReplyNotifications(doc = document) {
    const bell = doc.querySelector('[data-notification-bell]');
    if (!bell) return;
    const button = bell.querySelector('.notification-button');
    const badge = bell.querySelector('[data-notification-count]');
    const items = bell.querySelector('[data-notification-items]');
    const status = bell.querySelector('[data-notifications-status]');
    let busy = false;
    let refreshAgain = false;
    async function refresh(summary = false) {
        if (busy) { if (!summary) refreshAgain = true; return; }
        busy = true;
        if (!summary) status.textContent = 'Đang tải thông báo…';
        try {
            const url = new URL(bell.dataset.url);
            if (summary) url.searchParams.set('summary', '1');
            const response = await fetch(url, { headers: { Accept: 'application/json' }, credentials: 'same-origin', cache: 'no-store' });
            const data = await response.json();
            if (!response.ok) throw new Error(data.message || 'Không tải được thông báo.');
            if (!Number.isSafeInteger(data.unread) || (!summary && typeof data.html !== 'string')) throw new Error('Phản hồi thông báo không hợp lệ.');
            badge.hidden = data.unread === 0;
            badge.textContent = data.unread > 99 ? '99+' : String(data.unread);
            button.setAttribute('aria-label', data.unread ? 'Thông báo: ' + data.unread + ' chưa đọc' : 'Thông báo');
            if (!summary) { items.innerHTML = data.html; status.textContent = ''; }
        } catch (error) {
            if (!summary) status.textContent = requestErrorMessage(error, 'Không tải được thông báo. Nhấn chuông để thử lại.');
        } finally {
            busy = false;
            if (refreshAgain) { refreshAgain = false; void refresh(); }
        }
    }
    bell.addEventListener('show.bs.dropdown', () => void refresh());
    bell.querySelector('[data-notifications-read-all]').addEventListener('submit', async event => {
        event.preventDefault();
        const form = event.target;
        const submit = form.querySelector('button');
        submit.disabled = true;
        try {
            const response = await fetch(form.action, { method: 'POST', body: new FormData(form), headers: { Accept: 'application/json' }, credentials: 'same-origin' });
            const data = await response.json();
            if (!response.ok) throw new Error(data.message || 'Chưa đánh dấu được thông báo.');
            await refresh();
        } catch (error) {
            status.textContent = requestErrorMessage(error, 'Chưa đánh dấu được thông báo. Hãy thử lại.');
        } finally { submit.disabled = false; }
    });
    const refreshVisible = () => { if (doc.visibilityState === 'visible') void refresh(!bell.querySelector('.dropdown-menu.show')); };
    window.addEventListener('focus', refreshVisible);
    doc.addEventListener('visibilitychange', refreshVisible);
    setInterval(refreshVisible, 45000);
}

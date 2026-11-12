import { threadRequest } from './thread-api.js';

export function refreshFeedUrl(value) {
    const url = new URL(value);
    url.searchParams.delete('page');
    return url.toString();
}

export function pullDistance(start, current, horizontal) {
    const distance = current - start;
    return distance > 0 && distance > Math.abs(horizontal) * 1.5 ? Math.min(110, distance * 0.5) : 0;
}

export function setupLightbox(doc = document) {
    const dialog = doc.querySelector('[data-lightbox]');
    if (!dialog || typeof dialog.showModal !== 'function') return;
    const image = dialog.querySelector('img');
    const status = dialog.querySelector('[data-lightbox-status]');
    const previous = dialog.querySelector('[data-lightbox-previous]');
    const next = dialog.querySelector('[data-lightbox-next]');
    let links = [], index = 0, opener, start;
    const render = () => {
        const source = links[index].querySelector('img');
        image.src = links[index].href;
        image.alt = source?.alt || 'Ảnh đính kèm';
        status.textContent = `${index + 1} / ${links.length}`;
        previous.disabled = index === 0;
        next.disabled = index === links.length - 1;
    };
    const move = direction => { index = Math.max(0, Math.min(links.length - 1, index + direction)); render(); };
    doc.addEventListener('click', event => {
        const link = event.target.closest('[data-lightbox-image]');
        if (!link || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;
        event.preventDefault();
        opener = link;
        links = [...link.closest('[data-lightbox-gallery]').querySelectorAll('[data-lightbox-image]')];
        index = links.indexOf(link);
        render(); dialog.showModal();
    });
    previous.addEventListener('click', () => move(-1));
    next.addEventListener('click', () => move(1));
    dialog.querySelector('[data-lightbox-close]').addEventListener('click', () => dialog.close());
    dialog.addEventListener('click', event => { if (event.target === dialog) dialog.close(); });
    dialog.addEventListener('keydown', event => {
        if (event.key === 'ArrowLeft' || event.key === 'ArrowRight') { event.preventDefault(); move(event.key === 'ArrowLeft' ? -1 : 1); }
    });
    dialog.addEventListener('touchstart', event => { start = event.touches.length === 1 ? { x: event.touches[0].clientX, y: event.touches[0].clientY } : null; }, { passive: true });
    dialog.addEventListener('touchend', event => {
        if (!start || !event.changedTouches.length) return;
        const dx = event.changedTouches[0].clientX - start.x;
        const dy = event.changedTouches[0].clientY - start.y;
        if (Math.abs(dx) > 60 && Math.abs(dx) > Math.abs(dy) * 1.5 && (doc.defaultView.visualViewport?.scale ?? 1) <= 1) move(dx < 0 ? 1 : -1);
        start = null;
    }, { passive: true });
    image.addEventListener('error', () => { status.textContent = 'Không tải được ảnh. Hãy đóng rồi mở lại để thử.'; });
    dialog.addEventListener('close', () => { image.removeAttribute('src'); opener?.focus(); });
}

export function setupConnections(doc = document) {
    const dialog = doc.querySelector('[data-connections-dialog]');
    if (!dialog || typeof dialog.showModal !== 'function') return;
    const list = dialog.querySelector('[data-connections-list]');
    const status = dialog.querySelector('[data-connections-status]');
    const more = dialog.querySelector('[data-connections-more]');
    let generation = 0, busy = false, nextUrl = null, opener;
    const load = async (url, reset = false) => {
        const version = generation;
        busy = true; more.disabled = true; status.textContent = 'Đang tải…';
        try {
            const data = await threadRequest(url);
            if (version !== generation || !dialog.open) return;
            if (typeof data.html !== 'string') throw new Error('Chưa tải được danh sách.');
            if (reset) list.replaceChildren();
            list.insertAdjacentHTML('beforeend', data.html);
            nextUrl = data.nextPageUrl;
            more.hidden = !nextUrl;
            status.textContent = list.children.length ? '' : 'Chưa có tài khoản nào.';
        } catch (error) {
            if (version !== generation) return;
            status.textContent = error.message;
            nextUrl = url; more.hidden = false;
        } finally {
            if (version === generation) { busy = false; more.disabled = false; }
        }
    };
    doc.addEventListener('click', event => {
        const link = event.target.closest('[data-connections-url]');
        if (!link || event.ctrlKey || event.metaKey || event.shiftKey) return;
        event.preventDefault();
        opener = link; generation += 1; list.replaceChildren(); more.hidden = true;
        dialog.querySelector('h2').textContent = link.dataset.connectionsTitle;
        dialog.showModal(); void load(link.href, true);
    });
    more.addEventListener('click', () => { if (!busy && nextUrl) void load(nextUrl); });
    dialog.querySelector('[data-connections-close]').addEventListener('click', () => dialog.close());
    dialog.addEventListener('close', () => { generation += 1; busy = false; opener?.focus(); });
}

export function setupPullToRefresh(doc = document, win = window) {
    const feed = doc.querySelector('[data-stream-primary]');
    const indicator = doc.querySelector('[data-pull-refresh]');
    if (!feed || !indicator || !win.matchMedia('(pointer: coarse)').matches || win.top !== win) return;
    let start = null, distance = 0, refreshing = false;
    const reset = () => { start = null; distance = 0; if (!refreshing) indicator.hidden = true; };
    const refresh = async () => {
        if (refreshing || feed.dataset.loading === 'true') return reset();
        refreshing = true; feed.dataset.loading = 'true'; indicator.hidden = false;
        win.dispatchEvent?.(new Event('threads:refresh-start'));
        indicator.textContent = 'Đang làm mới…';
        try {
            const data = await threadRequest(refreshFeedUrl(win.location.href));
            if (typeof data.html !== 'string') throw new Error('Chưa tải được bài viết mới.');
            feed.innerHTML = data.html;
            doc.querySelector('[data-feed-empty]')?.remove();
            doc.querySelector('[data-stream-more]')?.closest('.stream-load-more')?.remove();
            doc.getElementById?.('loadMoreSection')?.remove();
            if (!feed.children.length) {
                const empty = doc.createElement('p'); empty.dataset.feedEmpty = ''; empty.className = 'empty-state';
                empty.textContent = 'Chưa có bài viết hiển thị trong bảng tin này.'; feed.after(empty);
            }
            if (data.nextPageUrl) {
                const container = doc.createElement('div'); container.className = 'stream-load-more';
                const link = doc.createElement('a'); link.href = data.nextPageUrl; link.className = 'btn-threads-outline';
                link.dataset.streamMore = ''; link.textContent = 'Tải thêm bài viết'; container.append(link); feed.after(container);
            }
            indicator.textContent = 'Đã cập nhật bảng tin.';
        } catch (error) { indicator.textContent = error.message; }
        finally { refreshing = false; feed.dataset.loading = 'false'; win.dispatchEvent?.(new Event('threads:refresh')); win.setTimeout(() => { if (!start && !refreshing) indicator.hidden = true; }, 3500); }
    };
    doc.addEventListener('touchstart', event => {
        reset();
        if (refreshing || feed.dataset.loading === 'true' || win.scrollY > 0 || event.touches.length !== 1 || doc.querySelector('dialog[open], .modal.show, .offcanvas.show') || event.target.closest('input, textarea, button, a, [contenteditable], .stream-media')) return;
        start = { x: event.touches[0].clientX, y: event.touches[0].clientY };
    }, { passive: true });
    doc.addEventListener('touchmove', event => {
        if (!start || event.touches.length !== 1 || win.scrollY > 0) return reset();
        distance = pullDistance(start.y, event.touches[0].clientY, event.touches[0].clientX - start.x);
        if (distance > 8) {
            if (event.cancelable) event.preventDefault();
            indicator.hidden = false; indicator.textContent = distance >= 70 ? 'Thả để làm mới' : 'Kéo xuống để làm mới';
        }
    }, { passive: false });
    doc.addEventListener('touchend', () => { const ready = start && distance >= 70; reset(); if (ready) void refresh(); }, { passive: true });
    doc.addEventListener('touchcancel', reset, { passive: true });
}

export function setupFeedPagination(doc = document, win = window) {
    doc.addEventListener('click', async event => {
        const control = event.target.closest('#btnLoadMoreThreads, [data-stream-more]');
        if (!control) return;
        event.preventDefault();
        const feed = doc.querySelector('[data-stream-feed]');
        if (!feed || feed.dataset.loading === 'true') return;
        feed.dataset.loading = 'true'; control.setAttribute('aria-disabled', 'true');
        win.dispatchEvent(new Event('threads:refresh-start'));
        try {
            const data = await threadRequest(control.dataset.nextUrl || control.href);
            if (typeof data.html !== 'string') throw new Error('Chưa tải được bài viết.');
            const template = doc.createElement('template'); template.innerHTML = data.html;
            template.content.querySelectorAll('[data-live-key]').forEach(card => { if (doc.getElementById(card.id)) card.remove(); });
            feed.append(template.content);
            if (data.nextPageUrl) { control.dataset.nextUrl = data.nextPageUrl; if (control.tagName === 'A') control.href = data.nextPageUrl; }
            else (control.closest('#loadMoreSection, .stream-load-more') || control).remove();
        } catch (error) { win.showToast?.(error.message); }
        finally { feed.dataset.loading = 'false'; control.setAttribute('aria-disabled', 'false'); win.dispatchEvent(new Event('threads:refresh')); }
    });
}

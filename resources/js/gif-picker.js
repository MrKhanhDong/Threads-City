export function giphyUrl(key, { query = '', offset = 0, ids = null } = {}) {
    const url = new URL(`https://api.giphy.com/v1/gifs${ids ? '' : query ? '/search' : '/trending'}`);
    url.searchParams.set('api_key', key);
    url.searchParams.set('rating', 'pg-13');
    if (ids) url.searchParams.set('ids', ids.join(','));
    else {
        url.searchParams.set('limit', '18');
        url.searchParams.set('offset', String(offset));
        if (query) { url.searchParams.set('q', query); url.searchParams.set('lang', 'vi'); }
    }
    return url;
}

function mediaUrl(value) {
    try {
        const url = new URL(value);
        return url.protocol === 'https:' && /(^|\.)giphy\.com$/.test(url.hostname) && !url.username && !url.password ? value : null;
    } catch { return null; }
}

export function giphyMedia(gif) {
    if (!/^[a-zA-Z0-9]{1,100}$/.test(gif?.id || '')) return null;
    const images = gif.images || {};
    const url = mediaUrl(images.downsized?.url) || mediaUrl(images.original?.url);
    const preview = mediaUrl(images.fixed_width_small?.webp) || mediaUrl(images.fixed_width_small?.url) || url;
    return url && preview ? { id: gif.id, kind: 'giphy', url, preview, title: gif.title || 'GIF trên GIPHY' } : null;
}

export async function requestGiphy(pageWindow, key, options, signal) {
    const response = await pageWindow.fetch(giphyUrl(key, options), { signal, credentials: 'omit', cache: 'no-store' });
    if (!response.ok) {
        if (response.status === 429) throw new Error('GIPHY đã hết lượt truy cập. Vui lòng thử lại sau.');
        if ([401, 403].includes(response.status)) throw new Error('Chưa kết nối được GIPHY. Vui lòng kiểm tra cấu hình.');
        throw new Error('Không tải được GIF. Vui lòng thử lại.');
    }
    const result = await response.json();
    if (!Array.isArray(result.data)) throw new Error('GIPHY trả về dữ liệu không hợp lệ.');
    return result;
}

function attribution(doc) {
    const link = doc.createElement('a');
    link.className = 'giphy-attribution';
    link.href = 'https://giphy.com/';
    link.target = '_blank';
    link.rel = 'noopener noreferrer';
    const logo = doc.createElement('img');
    logo.src = '/giphy-attribution.png'; logo.alt = 'Powered by GIPHY'; logo.width = 130;
    link.append(logo);
    return link;
}

export function setupGiphy(doc = document, pageWindow = window) {
    const key = doc.querySelector('meta[name="giphy-api-key"]')?.content;
    const dialog = doc.querySelector('[data-gif-picker]');
    if (dialog) setupPicker(dialog, key, doc, pageWindow);
    setupPostGifs(key, doc, pageWindow);
}

function setupPicker(dialog, key, doc, pageWindow) {
    const search = dialog.querySelector('[data-gif-search]');
    const input = search.querySelector('input');
    const results = dialog.querySelector('[data-gif-results]');
    const status = dialog.querySelector('[data-gif-status]');
    const more = dialog.querySelector('[data-gif-more]');
    const trigger = doc.getElementById('modalAddGifBtn');
    let controller, debounce, generation = 0, offset = 0, busy = false;
    const close = () => { clearTimeout(debounce); controller?.abort(); generation++; dialog.close(); };
    const load = async (append = false) => {
        if (!key) { status.textContent = 'Chưa kết nối được kho GIF. Bạn vẫn có thể chọn GIF từ máy.'; return; }
        controller?.abort();
        controller = new AbortController();
        const requestController = controller;
        const version = ++generation;
        const timeout = pageWindow.setTimeout(() => requestController.abort(), 12000);
        if (!append) { offset = 0; results.replaceChildren(); }
        busy = true; more.hidden = true;
        results.setAttribute('aria-busy', 'true');
        status.textContent = 'Đang tìm GIF…';
        try {
            const data = await requestGiphy(pageWindow, key, { query: input.value.trim(), offset }, requestController.signal);
            if (version !== generation || !dialog.open) return;
            for (const gif of data.data) {
                const media = giphyMedia(gif);
                if (!media) continue;
                const button = doc.createElement('button');
                button.type = 'button'; button.title = media.title; button.setAttribute('aria-label', `Chọn ${media.title}`);
                const image = doc.createElement('img');
                image.src = media.preview; image.alt = media.title; image.loading = 'lazy';
                button.append(image);
                button.addEventListener('click', () => {
                    const composer = pageWindow.threadMediaComposer;
                    if (!composer?.available()) { status.textContent = 'Không thể thêm GIF lúc này. Hãy kiểm tra giới hạn tệp hoặc chế độ bình chọn.'; return; }
                    try { composer.add(media); close(); }
                    catch (error) { status.textContent = error.message; }
                });
                results.append(button);
            }
            const count = data.pagination?.count ?? data.data.length;
            offset += count;
            more.hidden = !count || offset >= (data.pagination?.total_count ?? offset);
            status.textContent = results.children.length ? (input.value.trim() ? 'Chọn GIF để thêm vào bài viết' : 'GIF thịnh hành') : 'Không tìm thấy GIF. Thử từ khóa khác nhé.';
        } catch (error) {
            if (version === generation && dialog.open) status.textContent = error.name === 'AbortError' ? 'Tải GIF quá lâu. Bấm Tìm để thử lại.' : error.message;
        } finally {
            pageWindow.clearTimeout(timeout);
            if (version === generation) { busy = false; results.setAttribute('aria-busy', 'false'); }
        }
    };
    trigger?.addEventListener('click', () => {
        if (dialog.open) return;
        dialog.showModal(); input.focus(); load();
    });
    dialog.querySelector('[data-gif-close]').addEventListener('click', close);
    dialog.addEventListener('cancel', event => { event.preventDefault(); close(); });
    dialog.addEventListener('keydown', event => { if (event.key === 'Escape') event.stopPropagation(); });
    dialog.addEventListener('click', event => { if (event.target === dialog) close(); });
    doc.getElementById('newThreadModal')?.addEventListener('hide.bs.modal', close);
    search.addEventListener('submit', event => { event.preventDefault(); clearTimeout(debounce); load(); });
    input.addEventListener('input', () => {
        clearTimeout(debounce); controller?.abort(); generation++;
        debounce = pageWindow.setTimeout(() => load(), 450);
    });
    more.addEventListener('click', () => { if (!busy) load(true); });
    dialog.querySelector('[data-gif-upload]').addEventListener('click', () => {
        close();
        const file = doc.getElementById('modalComposerFileInput');
        if (file) { file.accept = 'image/gif'; file.click(); }
    });
}

function setupPostGifs(key, doc, pageWindow) {
    const watched = new WeakSet();
    const pending = new Set();
    let timer;
    const queue = node => {
        if (!node.isConnected || node.dataset.giphyLoaded) return;
        pending.add(node);
        pageWindow.clearTimeout(timer);
        timer = pageWindow.setTimeout(load, 80);
    };
    const load = async () => {
        const nodes = [...pending].filter(node => node.isConnected).slice(0, 100);
        nodes.forEach(node => pending.delete(node));
        if (!nodes.length) return;
        const controller = new AbortController();
        const timeout = pageWindow.setTimeout(() => controller.abort(), 12000);
        try {
            if (!key) throw new Error('Chưa kết nối được kho GIF.');
            const data = await requestGiphy(pageWindow, key, { ids: [...new Set(nodes.map(node => node.dataset.giphyId))] }, controller.signal);
            const gifs = new Map(data.data.map(gif => [gif.id, giphyMedia(gif)]));
            for (const node of nodes) {
                if (!node.isConnected) continue;
                const media = gifs.get(node.dataset.giphyId);
                if (!media) { node.textContent = 'GIF không còn khả dụng.'; node.dataset.giphyLoaded = '1'; continue; }
                const link = doc.createElement('a');
                link.href = media.url; link.dataset.lightbox = ''; link.setAttribute('aria-label', 'Phóng to GIF');
                const image = doc.createElement('img'); image.src = media.url; image.alt = media.title; image.loading = 'lazy';
                link.append(image);
                const caption = doc.createElement('figcaption'); caption.append(attribution(doc));
                node.replaceChildren(link, caption); node.dataset.giphyLoaded = '1';
            }
        } catch (error) {
            for (const node of nodes) {
                if (!node.isConnected) continue;
                const retry = doc.createElement('button'); retry.type = 'button'; retry.textContent = 'Thử tải lại GIF';
                retry.dataset.giphyRetry = '';
                node.replaceChildren(doc.createTextNode(error.name === 'AbortError' ? 'Tải GIF quá lâu. ' : `${error.message} `), retry);
                node.dataset.giphyLoaded = 'error';
            }
        } finally {
            pageWindow.clearTimeout(timeout);
            if (pending.size) timer = pageWindow.setTimeout(load, 80);
        }
    };
    const observer = pageWindow.IntersectionObserver ? new pageWindow.IntersectionObserver(entries => {
        entries.filter(entry => entry.isIntersecting).forEach(entry => { observer.unobserve(entry.target); queue(entry.target); });
    }, { rootMargin: '200px' }) : null;
    const scan = () => doc.querySelectorAll('[data-giphy-id]').forEach(node => {
        if (watched.has(node) || node.dataset.giphyLoaded) return;
        watched.add(node);
        if (observer) observer.observe(node); else queue(node);
    });
    scan();
    doc.addEventListener('click', event => {
        const retry = event.target.closest('[data-giphy-retry]');
        if (!retry) return;
        event.preventDefault(); event.stopPropagation();
        retry.disabled = true;
        const node = retry.closest('[data-giphy-id]');
        if (node) { delete node.dataset.giphyLoaded; queue(node); }
    });
    new pageWindow.MutationObserver(records => {
        if (records.some(record => [...record.addedNodes].some(node => node.nodeType === 1 && (node.matches('[data-giphy-id]') || node.querySelector('[data-giphy-id]'))))) scan();
    }).observe(doc.querySelector('.journal-workspace') || doc.body, { childList: true, subtree: true });
}

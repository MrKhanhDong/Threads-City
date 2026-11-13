export function collectionOrder(current, incoming, removed = [], keepTail = false) {
    const deleted = new Set(removed.map(id => `thread-${id}`));
    const keys = keepTail ? [...incoming, ...current] : incoming;
    return [...new Set(keys)].filter(key => !deleted.has(key));
}

function stripScripts(element) {
    element.querySelectorAll('script').forEach(script => script.remove());
    return element;
}

function listItems(element) {
    return [...element.children].filter(child => child.hasAttribute('data-live-key'));
}

function syncRegion(current, incoming, removed) {
    if (!current.hasAttribute('data-live-list')) {
        if (current.outerHTML !== incoming.outerHTML) current.replaceWith(incoming);
        return;
    }

    const existing = new Map(listItems(current).map(node => [node.dataset.liveKey, node]));
    const fresh = new Map(listItems(incoming).map(node => [node.dataset.liveKey, node]));
    const keepTail = current.dataset.liveRegion === 'feed';
    const order = collectionOrder([...existing.keys()], [...fresh.keys()], removed, keepTail);

    if (!order.length) {
        if (current.innerHTML !== incoming.innerHTML) current.replaceChildren(...incoming.childNodes);
        return;
    }

    // Keep the card styling available when the first post arrives in an empty feed.
    if (!document.getElementById('live-thread-card-style')) {
        const style = incoming.querySelector('style');
        if (style) {
            const copy = style.cloneNode(true);
            copy.id = 'live-thread-card-style';
            document.head.append(copy);
        }
    }

    const keep = new Set(order);
    for (const node of [...current.children]) {
        if (!node.dataset.liveKey || !keep.has(node.dataset.liveKey)) node.remove();
    }

    let previous = null;
    for (const key of order) {
        let node = existing.get(key);
        const replacement = fresh.get(key);
        if (!node) node = replacement;
        else if (replacement && node.outerHTML !== replacement.outerHTML) {
            const selection = window.getSelection();
            if (!node.contains(document.activeElement) && !(selection?.toString() && node.contains(selection.anchorNode))) {
                node.replaceWith(replacement);
                node = replacement;
            }
        }
        const position = previous ? previous.nextElementSibling : current.firstElementChild;
        if (position !== node) current.insertBefore(node, position);
        previous = node;
    }

    const heading = incoming.querySelector(':scope > h6');
    if (heading) current.prepend(heading);
}

export function setupLiveUpdates() {
    if (document.body.dataset.liveEnabled !== '1' || !document.querySelector('[data-live-region]')) return;

    let timer;
    let activeRequest;
    let busy = false;
    let mutations = 0;
    let revision = 0;
    let failures = 0;
    let stopped = false;
    const originalFetch = window.fetch.bind(window);
    const schedule = (delay = 8000) => {
        clearTimeout(timer);
        if (!stopped && !document.hidden) timer = setTimeout(refresh, delay);
    };

    // Synchronize after existing AJAX actions, without duplicating their handlers.
    window.fetch = async (input, options) => {
        const method = (options?.method || input?.method || 'GET').toUpperCase();
        const url = new URL(typeof input === 'string' || input instanceof URL ? input : input.url, location.href);
        const mutation = url.origin === location.origin && !['GET', 'HEAD', 'OPTIONS'].includes(method);
        if (mutation) { mutations++; revision++; }
        try {
            return await originalFetch(input, options);
        } finally {
            if (mutation) { mutations--; revision++; schedule(200); }
        }
    };

    async function refresh() {
        if (stopped || document.hidden) return;
        if (busy || mutations || document.querySelector('.modal.show, .dropdown-menu.show') || document.querySelector('[data-stream-primary]')?.dataset.loading === 'true') { schedule(2000); return; }
        busy = true;
        const version = revision;
        activeRequest = new AbortController();
        const timeout = setTimeout(() => activeRequest?.abort(), 15000);
        try {
            const url = new URL(location.href);
            url.hash = '';
            const known = [...new Set([...document.querySelectorAll('[data-live-key^="thread-"]')].map(node => node.dataset.liveKey.slice(7)))].slice(0, 100);
            const response = await originalFetch(url, {
                headers: { 'Accept': 'text/html', 'X-Threads-Live': '1', 'X-Threads-Known': known.join(',') },
                credentials: 'same-origin', cache: 'no-store', signal: activeRequest.signal,
            });
            if (response.redirected || [401, 403, 419].includes(response.status)) { stopped = true; return; }
            if (response.status === 404 && version === revision && document.querySelector('[data-live-region="thread"]')) {
                const message = document.createElement('p');
                message.className = 'text-secondary py-4';
                message.textContent = 'Bài viết không còn tồn tại hoặc đã bị xóa.';
                document.querySelector('[data-live-region="thread"]').replaceChildren(message);
                document.querySelector('[data-live-region="replies"]')?.replaceChildren();
                const submit = document.querySelector('#replyForm [type="submit"]');
                if (submit) submit.disabled = true;
                stopped = true;
                return;
            }
            if (!response.ok || !response.headers.get('content-type')?.includes('application/json')) throw new Error('Live update unavailable');
            const snapshot = await response.json();
            if (version !== revision || mutations || document.hidden) return;

            const anchor = [...document.querySelectorAll('.threads-center-feed [data-live-key]')].find(node => node.getBoundingClientRect().bottom > 0);
            const anchorKey = anchor?.dataset.liveKey;
            const top = anchor?.getBoundingClientRect().top;
            const keepPosition = window.scrollY > 100;

            for (const region of snapshot.regions || []) {
                const current = [...document.querySelectorAll('[data-live-region]')].find(node => node.dataset.liveRegion === region.name);
                if (!current) continue;
                const template = document.createElement('template');
                template.innerHTML = region.html;
                const incoming = stripScripts(template.content.firstElementChild);
                syncRegion(current, incoming, snapshot.removed || []);
            }

            if (keepPosition && anchorKey) {
                const updated = [...document.querySelectorAll('[data-live-key]')].find(node => node.dataset.liveKey === anchorKey);
                if (updated) window.scrollBy(0, updated.getBoundingClientRect().top - top);
            }
            failures = 0;
            document.dispatchEvent(new Event('threads:updated'));
        } catch (error) {
            if (error.name !== 'AbortError') failures++;
        } finally {
            clearTimeout(timeout);
            busy = false;
            activeRequest = null;
            schedule(Math.min(60000, 8000 * (2 ** Math.min(failures, 3))));
        }
    }

    window.addEventListener('threads:refresh', () => { revision++; schedule(150); });
    window.addEventListener('threads:refresh-start', () => { revision++; activeRequest?.abort(); });
    document.addEventListener('visibilitychange', () => {
        if (document.hidden) { clearTimeout(timer); activeRequest?.abort(); }
        else schedule(100);
    });
    window.addEventListener('pagehide', () => { stopped = true; clearTimeout(timer); activeRequest?.abort(); });

    document.addEventListener('submit', async event => {
        const replyForm = event.target.closest('#replyForm, form[data-live-submit]');
        if (!replyForm || event.defaultPrevented) return;
        event.preventDefault();
        const button = replyForm.querySelector('[type="submit"]');
        if (!button || button.disabled) return;
        button.disabled = true;
        try {
            const response = await fetch(replyForm.action, {
                method: 'POST', body: new FormData(replyForm),
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
            });
            const data = await response.json();
            if (!response.ok || (replyForm.id === 'replyForm' && !data.success)) throw new Error(data.message || 'Không thực hiện được thao tác.');
            replyForm.reset();
            replyForm.dispatchEvent(new Event('threads:sent'));
            window.dispatchEvent(new Event('threads:refresh'));
        } catch (error) {
            const status = document.getElementById('replyStatusText');
            if (status) status.textContent = error.message;
            else {
                const toast = document.getElementById('threadsGlobalToast');
                if (toast) { toast.textContent = error.message; toast.classList.add('show'); setTimeout(() => toast.classList.remove('show'), 4000); }
            }
        } finally {
            button.disabled = false;
        }
    });

    schedule();
}

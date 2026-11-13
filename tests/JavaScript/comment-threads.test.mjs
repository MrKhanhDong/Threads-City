import test from 'node:test';
import assert from 'node:assert/strict';
import { replyBatchUrl, fetchReplyBatch, setupCommentThreads, setupReplyNotifications } from '../../resources/js/comment-threads.js';

test('reply URL retains route and adds a stable cursor or focused comment', () => {
    const url = new URL(replyBatchUrl('http://localhost:8000/binh-luan/4/tra-loi', 17, 25));
    assert.equal(url.pathname, '/binh-luan/4/tra-loi');
    assert.equal(url.searchParams.get('after'), '17');
    assert.equal(url.searchParams.get('focus'), '25');
    assert.equal(new URL(replyBatchUrl('http://localhost:8000/replies')).searchParams.has('focus'), false);
});

test('reply fetch uses same-session credentials and uncached JSON', async () => {
    const batch = { html: '<p>Trả lời</p>', next: 5, more: true, earlier: false };
    const data = await fetchReplyBatch('http://localhost:8000/replies', async (url, options) => {
        assert.equal(options.credentials, 'same-origin');
        assert.equal(options.cache, 'no-store');
        assert.equal(options.headers.Accept, 'application/json');
        return { ok: true, json: async () => batch };
    });
    assert.deepEqual(data, batch);
});

test('server errors are shown instead of appending a broken response', async () => {
    await assert.rejects(fetchReplyBatch('http://localhost:8000/replies', async () => ({
        ok: false, json: async () => ({ message: 'Bình luận không còn hiển thị.' }),
    })), /Bình luận không còn hiển thị/);
});

test('malformed cursor, markup, or pagination state is rejected', async () => {
    for (const batch of [
        { html: '<p>x</p>', next: '5', more: false, earlier: false },
        { html: null, next: 5, more: false, earlier: false },
        { html: '<p>x</p>', next: 5, more: 'yes', earlier: false },
        { html: '<p>x</p>', next: 5, more: false },
    ]) {
        await assert.rejects(fetchReplyBatch('http://localhost:8000/replies', async () => ({ ok: true, json: async () => batch })), /Phản hồi trả lời không hợp lệ/);
    }
});

function fixture() {
    const element = (hidden = false) => ({
        hidden, disabled: false, textContent: '', listeners: {}, attributes: {},
        addEventListener(name, listener) { this.listeners[name] = listener; },
        setAttribute(name, value) { this.attributes[name] = value; },
    });
    const panel = element(true), toggle = element(), more = element(), earlier = element(true), collapse = element(), status = element();
    const list = { children: [], replaceChildren() { this.children = []; }, querySelector() { return null; }, append(node) { this.children.push(node); } };
    const selectors = {
        '[data-replies-panel]': panel, '[data-replies-toggle]': toggle,
        '[data-replies-more]': more, '[data-replies-earlier]': earlier,
        '[data-replies-list]': list, '[data-replies-status]': status,
        '[data-replies-collapse]': collapse,
    };
    const thread = { ...element(), dataset: { repliesUrl: 'http://localhost:8000/replies', next: '0', loaded: '0', more: '1' }, querySelector(selector) { return selectors[selector]; } };
    const doc = {
        querySelectorAll() { return [thread]; },
        createElement() { return { content: { querySelectorAll() { return []; } } }; },
    };
    return { doc, thread, panel, toggle, more, earlier, collapse, status, list };
}
const settle = () => new Promise(resolve => setImmediate(resolve));

test('opening loads lazily; collapse and reopening preserve loaded replies and cursor', async () => {
    const originalFetch = globalThis.fetch;
    const urls = [];
    globalThis.fetch = async url => {
        urls.push(url);
        return { ok: true, json: async () => ({ html: '', next: urls.length * 5, more: urls.length === 1, earlier: false }) };
    };
    try {
        const f = fixture();
        setupCommentThreads(f.doc);
        assert.equal(urls.length, 0);
        f.toggle.listeners.click();
        await settle();
        assert.equal(f.panel.hidden, false);
        assert.equal(f.toggle.textContent, 'Ẩn bớt trả lời');
        assert.equal(urls.length, 1);
        f.list.children.push({ draft: 'Nội dung đang viết' });
        f.toggle.listeners.click();
        assert.equal(f.panel.hidden, true);
        f.toggle.listeners.click();
        await settle();
        assert.equal(urls.length, 1);
        assert.equal(f.list.children[0].draft, 'Nội dung đang viết');
        f.more.listeners.click();
        await settle();
        assert.equal(new URL(urls[1]).searchParams.get('after'), '5');
        assert.equal(f.more.hidden, true);
        assert.equal(f.toggle.disabled, false);
        f.collapse.listeners.click();
        assert.equal(f.panel.hidden, true);
        assert.equal(f.toggle.attributes['aria-expanded'], 'false');
        assert.equal(f.toggle.textContent, 'Xem trả lời');
        assert.equal(f.list.children[0].draft, 'Nội dung đang viết');
    } finally { globalThis.fetch = originalFetch; }
});

test('failed load shows a Vietnamese error, re-enables controls, and permits retry', async () => {
    const originalFetch = globalThis.fetch;
    globalThis.fetch = async () => { throw new TypeError('Failed to fetch'); };
    try {
        const f = fixture();
        setupCommentThreads(f.doc);
        f.toggle.listeners.click();
        await settle();
        assert.equal(f.thread.dataset.loaded, '0');
        assert.equal(f.toggle.disabled, false);
        assert.match(f.status.textContent, /kết nối/i);
        assert.doesNotMatch(f.status.textContent, /Failed to fetch/);
        globalThis.fetch = async () => ({ ok: true, json: async () => ({ html: '', next: 0, more: false, earlier: false }) });
        f.more.listeners.click();
        await settle();
        assert.equal(f.thread.dataset.loaded, '1');
        assert.equal(f.more.hidden, true);
    } finally { globalThis.fetch = originalFetch; }
});

function notificationFixture() {
    const element = () => ({
        hidden: false, disabled: false, textContent: '', innerHTML: '', listeners: {}, attributes: {},
        addEventListener(name, listener) { this.listeners[name] = listener; },
        setAttribute(name, value) { this.attributes[name] = value; },
    });
    const button = element(), badge = element(), items = element(), status = element(), retry = element(), submit = element();
    const form = { ...element(), action: 'http://localhost:8000/read-all', querySelector: () => submit };
    const selectors = {
        '.notification-button': button, '[data-notification-count]': badge, '[data-notification-items]': items,
        '[data-notifications-status]': status, '[data-notifications-retry]': retry,
        '[data-notifications-read-all]': form, '[data-notifications-read-all] button': submit,
    };
    const bell = { ...element(), dataset: { url: 'http://localhost:8000/thong-bao?preview=1' }, open: false,
        querySelector(selector) { return selector === '.dropdown-menu.show' ? this.open : selectors[selector]; },
    };
    const doc = { ...element(), visibilityState: 'visible', querySelector: () => bell };
    const requests = [], responses = [], timers = new Map();
    let nextTimer = 0;
    const pageWindow = {
        ...element(), FormData: class { constructor(source) { this.source = source; } },
        async fetch(url, options) {
            requests.push({ url: new URL(url), options });
            const result = responses.shift();
            if (result instanceof Error) throw result;
            return typeof result === 'function' ? result() : { ok: true, json: async () => result };
        },
        setInterval(handler, delay) { assert.equal(delay, 30000); timers.set(++nextTimer, handler); return nextTimer; },
        clearInterval(id) { timers.delete(id); },
    };
    setupReplyNotifications(doc, pageWindow);
    const open = () => { bell.open = true; bell.listeners['show.bs.dropdown'](); };
    const poll = () => [...timers.values()][0]();
    const read = () => form.listeners.submit({ preventDefault() {}, target: form });
    return { doc, pageWindow, bell, button, badge, items, status, retry, submit, requests, responses, timers, open, poll, read };
}

test('notification polling updates a red dot and accessible count without loading or marking the preview read', async () => {
    const ui = notificationFixture();
    ui.items.innerHTML = 'Cached notifications';
    ui.responses.push({ unread: 3 });
    ui.poll(); await settle();
    assert.equal(ui.badge.hidden, false);
    assert.equal(ui.badge.textContent, '');
    assert.equal(ui.button.attributes['aria-label'], 'Thông báo: 3 chưa đọc');
    assert.equal(ui.items.innerHTML, 'Cached notifications');
    assert.equal(ui.requests[0].url.searchParams.get('summary'), '1');
    assert.equal(ui.requests[0].options.cache, 'no-store');
    assert.equal(ui.requests[0].options.credentials, 'same-origin');
    ui.doc.visibilityState = 'hidden'; ui.poll();
    assert.equal(ui.requests.length, 1);
});

test('opening loads recent notifications and marking all read refreshes the popup without navigation', async () => {
    const ui = notificationFixture();
    ui.responses.push({ unread: 2, html: 'Two unread notifications' });
    ui.open(); await settle();
    assert.equal(ui.items.innerHTML, 'Two unread notifications');
    assert.equal(ui.requests.length, 1);
    assert.equal(ui.requests[0].options.method, undefined);
    ui.responses.push({ message: 'Đã đọc' }, { unread: 0, html: 'Two read notifications' });
    await ui.read(); await settle();
    assert.equal(ui.requests[1].options.method, 'POST');
    assert.equal(ui.badge.hidden, true);
    assert.equal(ui.submit.disabled, true);
    assert.equal(ui.button.attributes['aria-label'], 'Thông báo');
    assert.equal(ui.items.innerHTML, 'Two read notifications');
    ui.responses.push({ unread: 1, html: 'New activity notification' });
    ui.pageWindow.listeners['threads:refresh'](); await settle();
    assert.equal(ui.badge.hidden, false);
    assert.equal(ui.items.innerHTML, 'New activity notification');
});

test('an older preview response cannot restore unread state after marking all read', async () => {
    const ui = notificationFixture();
    let resolvePreview;
    ui.responses.push(() => new Promise(resolve => { resolvePreview = resolve; }));
    ui.open();
    ui.responses.push({ message: 'Đã đọc' });
    await ui.read();
    assert.equal(ui.badge.hidden, true);
    ui.responses.push({ unread: 0, html: 'Fresh read state' });
    resolvePreview({ ok: true, json: async () => ({ unread: 2, html: 'Outdated unread state' }) });
    await settle();
    assert.equal(ui.badge.hidden, true);
    assert.equal(ui.items.innerHTML, 'Fresh read state');
});

test('popup load failures retain existing notifications and expose a working retry', async () => {
    const ui = notificationFixture();
    ui.items.innerHTML = 'Previous notifications';
    ui.responses.push(new TypeError('Failed to fetch'));
    ui.open(); await settle();
    assert.equal(ui.items.innerHTML, 'Previous notifications');
    assert.equal(ui.retry.hidden, false);
    assert.match(ui.status.textContent, /thử lại/i);
    ui.responses.push({ unread: 0, html: 'Updated notifications' });
    ui.retry.listeners.click(); await settle();
    assert.equal(ui.items.innerHTML, 'Updated notifications');
    assert.equal(ui.retry.hidden, true);
});

test('opening during a summary request queues a full preview and page lifecycle keeps one poll timer', async () => {
    const ui = notificationFixture();
    let resolveSummary;
    ui.responses.push(() => new Promise(resolve => { resolveSummary = resolve; }));
    ui.poll(); ui.open();
    ui.responses.push({ unread: 1, html: 'Loaded on opening' });
    resolveSummary({ ok: true, json: async () => ({ unread: 1 }) });
    await settle();
    assert.equal(ui.requests.length, 2);
    assert.equal(ui.requests[1].url.searchParams.has('summary'), false);
    assert.equal(ui.items.innerHTML, 'Loaded on opening');
    ui.pageWindow.listeners.pagehide();
    assert.equal(ui.timers.size, 0);
    ui.responses.push({ unread: 0, html: 'After returning' });
    ui.pageWindow.listeners.pageshow({ persisted: true }); await settle();
    assert.equal(ui.timers.size, 1);
    ui.pageWindow.listeners.pageshow({ persisted: true });
    assert.equal(ui.timers.size, 1);
});

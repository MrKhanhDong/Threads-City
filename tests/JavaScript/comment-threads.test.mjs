import test from 'node:test';
import assert from 'node:assert/strict';
import { replyBatchUrl, fetchReplyBatch, setupCommentThreads } from '../../resources/js/comment-threads.js';

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

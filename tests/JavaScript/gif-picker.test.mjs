import test from 'node:test';
import assert from 'node:assert/strict';
import { giphyUrl, giphyMedia, requestGiphy, setupGiphy } from '../../resources/js/gif-picker.js';

const gif = (id = 'hello') => ({ id, title: `GIF ${id}`, images: {
    downsized: { url: `https://media.giphy.com/media/${id}/giphy.gif?cid=keep` },
    fixed_width_small: { webp: `https://media.giphy.com/media/${id}/100.webp?cid=keep` },
} });

test('GIPHY requests distinguish trending, exact search and batch lookup with content rating', () => {
    const trending = giphyUrl('test');
    assert.equal(trending.pathname, '/v1/gifs/trending');
    assert.equal(trending.searchParams.get('rating'), 'pg-13');
    const search = giphyUrl('test', { query: 'mèo & vui', offset: 18 });
    assert.equal(search.pathname, '/v1/gifs/search');
    assert.equal(search.searchParams.get('q'), 'mèo & vui');
    assert.equal(search.searchParams.get('offset'), '18');
    const ids = giphyUrl('test', { ids: ['hello', 'world'] });
    assert.equal(ids.pathname, '/v1/gifs');
    assert.equal(ids.searchParams.get('ids'), 'hello,world');
});

test('GIF renditions preserve provider query parameters and reject unsafe URLs', () => {
    assert.equal(giphyMedia(gif()).url, 'https://media.giphy.com/media/hello/giphy.gif?cid=keep');
    for (const url of ['javascript:alert(1)', 'https://giphy.com.evil.test/a.gif', 'http://media.giphy.com/a.gif', 'https://user:pass@media.giphy.com/a.gif']) {
        assert.equal(giphyMedia({ id: 'hello', images: { original: { url } } }), null);
    }
    assert.equal(giphyMedia({ ...gif(), id: '../bad' }), null);
});

test('provider failures explain quota and auth problems without exposing the key', async () => {
    for (const [status, message] of [[429, /hết lượt/], [403, /cấu hình/], [500, /thử lại/]]) {
        await assert.rejects(requestGiphy({ fetch: async () => ({ ok: false, status }) }, 'test', {}), message);
    }
    await assert.rejects(requestGiphy({ fetch: async () => ({ ok: true, json: async () => ({ data: {} }) }) }, 'test', {}), /không hợp lệ/);
});

function element() {
    const listeners = new Map();
    return {
        children: [], dataset: {}, attributes: {}, value: '', textContent: '', open: false, isConnected: true,
        addEventListener(name, callback) { listeners.set(name, callback); },
        emit(name, details = {}) { return listeners.get(name)?.({ preventDefault() {}, stopPropagation() {}, target: this, ...details }); },
        setAttribute(name, value) { this.attributes[name] = value; },
        append(...nodes) { this.children.push(...nodes); },
        replaceChildren(...nodes) { this.children = nodes; },
        focus() { this.focused = true; }, showModal() { this.open = true; }, close() { this.open = false; }, click() { this.clicked = true; },
    };
}

function fixture({ key = 'test', request, postIds = [] } = {}) {
    const dialog = element(), search = element(), input = element(), results = element(), status = element(), more = element(), close = element(), upload = element();
    const trigger = element(), modal = element(), file = element();
    const timers = new Map(), calls = [], selected = [];
    let serial = 0;
    const posts = postIds.map(id => Object.assign(element(), { dataset: { giphyId: id } }));
    const controls = { '[data-gif-search]': search, '[data-gif-results]': results, '[data-gif-status]': status, '[data-gif-more]': more, '[data-gif-close]': close, '[data-gif-upload]': upload };
    dialog.querySelector = selector => controls[selector]; search.querySelector = () => input;
    const doc = {
        ...element(),
        body: element(), querySelector: selector => selector === 'meta[name="giphy-api-key"]' ? { content: key } : selector === '[data-gif-picker]' ? dialog : null,
        querySelectorAll: () => posts,
        getElementById: id => ({ modalAddGifBtn: trigger, newThreadModal: modal, modalComposerFileInput: file })[id],
        createElement: () => element(), createTextNode: text => ({ textContent: text }),
    };
    const win = {
        fetch: async (url, options) => { calls.push({ url, options }); return request ? request(url, options) : { ok: true, json: async () => ({ data: [gif()], pagination: { count: 1, total_count: 2 } }) }; },
        setTimeout(callback, delay) { const id = ++serial; timers.set(id, { callback, delay }); return id; }, clearTimeout: id => timers.delete(id),
        MutationObserver: class { observe() {} },
        threadMediaComposer: { available: () => true, add: media => selected.push(media) },
    };
    setupGiphy(doc, win);
    return { dialog, input, results, status, more, close, upload, trigger, file, modal, calls, selected, posts, win,
        fireTimer(delay) { for (const [id, timer] of timers) if (timer.delay === delay) { timers.delete(id); timer.callback(); break; } },
    };
}
const settle = () => new Promise(resolve => setImmediate(resolve));

test('picker opens trending GIFs, paginates and selects into the existing composer without upload', async () => {
    const ui = fixture();
    ui.trigger.emit('click'); await settle();
    assert.equal(ui.dialog.open, true);
    assert.equal(ui.input.focused, true);
    assert.equal(ui.results.children.length, 1);
    assert.equal(ui.calls[0].options.credentials, 'omit');
    assert.equal(ui.calls[0].options.cache, 'no-store');
    ui.more.emit('click'); await settle();
    assert.equal(ui.calls[1].url.searchParams.get('offset'), '1');
    assert.equal(ui.results.children.length, 2);
    ui.results.children[0].emit('click');
    assert.equal(ui.selected[0].kind, 'giphy');
    assert.equal(ui.selected[0].id, 'hello');
    assert.equal(ui.dialog.open, false);
});

test('debounced search aborts the previous request and ignores stale results after close', async () => {
    let finish;
    const ui = fixture({ request: () => new Promise(resolve => { finish = resolve; }) });
    ui.trigger.emit('click');
    ui.input.value = 'mèo'; ui.input.emit('input');
    assert.equal(ui.calls[0].options.signal.aborted, true);
    ui.fireTimer(450);
    assert.equal(ui.calls[1].url.searchParams.get('q'), 'mèo');
    ui.close.emit('click');
    finish({ ok: true, json: async () => ({ data: [gif()] }) });
    await settle();
    assert.equal(ui.results.children.length, 0);
    assert.equal(ui.dialog.open, false);
});

test('missing key offers local GIF upload, and polls or attachment limits block selection', async () => {
    const missing = fixture({ key: '' });
    missing.trigger.emit('click');
    assert.equal(missing.calls.length, 0);
    assert.match(missing.status.textContent, /từ máy/);
    missing.upload.emit('click');
    assert.equal(missing.file.accept, 'image/gif');
    assert.equal(missing.file.clicked, true);
    const ui = fixture(); ui.trigger.emit('click'); await settle();
    ui.win.threadMediaComposer.available = () => false;
    ui.results.children[0].emit('click');
    assert.equal(ui.selected.length, 0);
    assert.equal(ui.dialog.open, true);
    assert.match(ui.status.textContent, /giới hạn/);
});

test('post GIFs batch IDs and render direct media with attribution and a lightbox link', async () => {
    const ui = fixture({ postIds: ['hello', 'hello'] });
    ui.fireTimer(80); await settle();
    assert.equal(ui.calls.length, 1);
    assert.equal(ui.calls[0].url.searchParams.get('ids'), 'hello');
    assert.equal(ui.posts[0].dataset.giphyLoaded, '1');
    assert.equal(ui.posts[0].children[0].href, giphyMedia(gif()).url);
    assert.equal(ui.posts[0].children[0].dataset.lightbox, '');
    assert.equal(ui.posts[0].children[1].children[0].children[0].alt, 'Powered by GIPHY');
});

test('failed post GIFs retain an explicit retry state instead of retrying on every live refresh', async () => {
    const ui = fixture({ postIds: ['hello'], request: async () => ({ ok: false, status: 429 }) });
    ui.fireTimer(80); await settle();
    assert.equal(ui.posts[0].dataset.giphyLoaded, 'error');
    assert.match(ui.posts[0].children[0].textContent, /hết lượt/);
    assert.equal(ui.posts[0].children[1].dataset.giphyRetry, '');
});

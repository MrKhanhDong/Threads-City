import test from 'node:test';
import assert from 'node:assert/strict';
import { musicStreamUrl, searchMusic, setupMusicPicker } from '../../resources/js/music-picker.js';

const track = { id: 'song123', title: 'Bài hát', artist: 'Nghệ sĩ', artwork: null, provider: 'audius' };
const settle = () => new Promise(resolve => setImmediate(resolve));

test('music streams only from fixed Audius endpoints and rejects arbitrary URLs and path traversal', () => {
    assert.equal(musicStreamUrl('song123'), 'https://api.audius.co/v1/tracks/song123/stream?app_name=ThreadsCity');
    for (const id of ['../other', 'https://evil.test', '', 'a'.repeat(65)]) assert.equal(musicStreamUrl(id), null);
});

test('search encodes Vietnamese queries and preserves authentication and cancellation', async () => {
    const signal = new AbortController().signal;
    const win = { location: new URL('http://localhost/'), fetch: async (url, options) => {
        assert.equal(url.searchParams.get('q'), 'nhạc & vui'); assert.equal(url.searchParams.get('offset'), '20');
        assert.equal(options.credentials, 'same-origin'); assert.equal(options.signal, signal);
        return { ok: true, json: async () => ({ items: [] }) };
    } };
    assert.deepEqual(await searchMusic('/music/search', 'nhạc & vui', 20, signal, win), { items: [] });
    win.fetch = async () => ({ ok: false, status: 401, json: async () => ({}) });
    await assert.rejects(searchMusic('/music/search', '', 0, signal, win), /Đăng nhập/);
});

function element() {
    const listeners = new Map();
    return {
        children: [], dataset: {}, attributes: {}, value: '', textContent: '', open: false, paused: true,
        addEventListener(name, callback) { listeners.set(name, callback); },
        emit(name, details = {}) { return listeners.get(name)?.({ preventDefault() {}, stopPropagation() {}, target: this, ...details }); },
        setAttribute(name, value) { this.attributes[name] = value; }, removeAttribute(name) { delete this[name]; },
        append(...nodes) { this.children.push(...nodes); }, replaceChildren(...nodes) { this.children = nodes; },
        focus() { this.focused = true; }, showModal() { this.open = true; }, close() { this.open = false; },
        pause() { this.paused = true; }, async play() { this.paused = false; }, load() {}, querySelectorAll: () => [],
    };
}

function fixture(request) {
    const dialog = element(), trigger = element(), modal = element(), selected = element(), musicId = element();
    const form = element(), input = element(), results = element(), status = element(), more = element(), audition = element(), close = element();
    const timers = new Map(), calls = []; let serial = 0, updates = 0;
    dialog.dataset.searchUrl = '/music/search';
    form.querySelector = () => input;
    dialog.querySelector = selector => ({ '[data-music-search]': form, '[data-music-results]': results, '[data-music-status]': status, '[data-music-more]': more, '[data-music-audition]': audition, '[data-music-close]': close })[selector];
    const doc = {
        ...element(),
        querySelector: selector => ({ '[data-music-picker]': dialog, '[data-selected-music]': selected })[selector],
        getElementById: id => ({ modalAddMusicBtn: trigger, newThreadModal: modal, modalMusicId: musicId })[id],
        createElement: () => element(),
    };
    const win = {
        location: new URL('http://localhost/'),
        fetch: async (url, options) => { calls.push({ url, options }); return request ? request(url, options) : { ok: true, json: async () => ({ items: [track], has_more: true, next_offset: 20 }) }; },
        setTimeout(callback, delay) { const id = ++serial; timers.set(id, { callback, delay }); return id; }, clearTimeout: id => timers.delete(id),
        updateComposerSubmitState() { updates++; },
    };
    setupMusicPicker(doc, win);
    return { dialog, trigger, modal, selected, musicId, input, results, status, more, audition, close, calls,
        updates: () => updates,
        fireTimer(delay) { for (const [id, timer] of timers) if (timer.delay === delay) { timers.delete(id); timer.callback(); break; } },
    };
}

test('picker loads trending music, previews audio and selects exactly one song into the composer', async () => {
    const ui = fixture(); ui.trigger.emit('click'); await settle();
    assert.equal(ui.dialog.open, true); assert.equal(ui.input.focused, true);
    assert.equal(ui.calls[0].url.searchParams.get('q'), '');
    const actions = ui.results.children[0].children[1];
    await actions.children[0].emit('click');
    assert.equal(ui.audition.paused, false);
    assert.equal(ui.audition.src, musicStreamUrl('song123'));
    actions.children[1].emit('click');
    assert.equal(ui.musicId.value, 'song123');
    assert.equal(ui.selected.hidden, false); assert.equal(ui.selected.children.length, 1);
    assert.equal(ui.dialog.open, false); assert.equal(ui.audition.paused, true);
    assert.ok(ui.updates() > 0);
});

test('changing songs replaces the selection and removal or reset clears music-only submission state', async () => {
    const ui = fixture(); ui.trigger.emit('click'); await settle();
    const pick = ui.results.children[0].children[1].children[1];
    pick.emit('click'); pick.emit('click');
    assert.equal(ui.selected.children.length, 1);
    ui.selected.children[0].children[0].children[1].emit('click');
    assert.equal(ui.musicId.value, ''); assert.equal(ui.selected.hidden, true);
    pick.emit('click'); ui.modal.emit('composer:reset');
    assert.equal(ui.musicId.value, ''); assert.equal(ui.selected.children.length, 0);
});

test('debounced searches cancel older requests and late results do not reopen a closed picker', async () => {
    let finish;
    const ui = fixture(() => new Promise(resolve => { finish = resolve; }));
    ui.trigger.emit('click'); ui.input.value = 'Sơn Tùng'; ui.input.emit('input');
    assert.equal(ui.calls[0].options.signal.aborted, true);
    ui.fireTimer(500); assert.equal(ui.calls[1].url.searchParams.get('q'), 'Sơn Tùng');
    ui.close.emit('click');
    finish({ ok: true, json: async () => ({ items: [track] }) }); await settle();
    assert.equal(ui.results.children.length, 0); assert.equal(ui.dialog.open, false);
});

test('pagination keeps prior results and provider failures remain recoverable', async () => {
    const ui = fixture(); ui.trigger.emit('click'); await settle();
    ui.more.emit('click'); await settle();
    assert.equal(ui.calls[1].url.searchParams.get('offset'), '20');
    assert.equal(ui.results.children.length, 2);
    const unavailable = fixture(async () => ({ ok: false, status: 503, json: async () => ({ message: 'Kho nhạc đang bận.' }) }));
    unavailable.trigger.emit('click'); await settle();
    assert.equal(unavailable.status.textContent, 'Kho nhạc đang bận.');
    assert.equal(unavailable.dialog.open, true);
});

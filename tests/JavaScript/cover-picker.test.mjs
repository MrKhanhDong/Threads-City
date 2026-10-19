import test from 'node:test';
import assert from 'node:assert/strict';
import { setImmediate } from 'node:timers/promises';
import { setupCoverPicker, uploadCover } from '../../resources/js/cover-picker.js';

test('upload uses the existing library endpoint with CSRF and keeps the returned media identity', async t => {
    t.mock.method(globalThis, 'fetch', async (url, options) => {
        assert.equal(url, '/admin/media');
        assert.equal(options.headers['X-CSRF-TOKEN'], 'token');
        assert.equal(options.headers.Accept, 'application/json');
        assert.equal(options.body.get('file').name, 'cover.png');
        assert.equal(options.body.get('alt'), 'Ảnh minh họa');
        return { ok: true, json: async () => ({ id: 12, location: '/media/12', alt: 'Ảnh minh họa' }) };
    });
    const result = await uploadCover(new File(['image'], 'cover.png', { type: 'image/png' }), '/admin/media', 'token', 'Ảnh minh họa');
    assert.deepEqual(result, { id: '12', url: '/media/12', name: 'cover.png', alt: 'Ảnh minh họa' });
});

test('unsupported and oversized files never send a request', async t => {
    const fetch = t.mock.method(globalThis, 'fetch', async () => { throw new Error('Unexpected request'); });
    await assert.rejects(uploadCover(new File(['svg'], 'cover.svg', { type: 'image/svg+xml' }), '/upload', 'token'), /JPEG/);
    await assert.rejects(uploadCover({ type: 'image/png', size: 5 * 1024 * 1024 + 1 }, '/upload', 'token'), /5 MB/);
    assert.equal(fetch.mock.callCount(), 0);
});

test('validation, expired sessions, and malformed responses surface as upload failures', async t => {
    const file = new File(['image'], 'cover.png', { type: 'image/png' });
    const fetch = t.mock.method(globalThis, 'fetch', async () => ({ ok: false, json: async () => ({ errors: { file: ['Ảnh không hợp lệ.'] } }) }));
    await assert.rejects(uploadCover(file, '/upload', 'token'), /Ảnh không hợp lệ/);
    fetch.mock.mockImplementation(async () => ({ ok: false, json: async () => ({ message: 'Phiên làm việc đã hết hạn.' }) }));
    await assert.rejects(uploadCover(file, '/upload', 'token'), /hết hạn/);
    fetch.mock.mockImplementation(async () => ({ ok: true, json: async () => ({}) }));
    await assert.rejects(uploadCover(file, '/upload', 'token'), /thông tin ảnh/);
});

class Element extends EventTarget {
    dataset = {};
    children = [];
    attributes = {};
    value = '';
    textContent = '';
    hidden = false;
    disabled = false;
    classList = { toggle() {} };
    setAttribute(name, value) { this.attributes[name] = value; }
    removeAttribute(name) { delete this.attributes[name]; }
    append(...items) { this.children.push(...items); }
    prepend(item) { this.children.unshift(item); }
    querySelectorAll() { return this.children; }
    closest() { return this; }
    click() { this.dispatchEvent(new Event('click')); }
    add(option) { this.children.push(option); }
}

test('picker stages a library selection, uploads in place, confirms it and can remove the cover', async t => {
    const controls = new Map();
    const element = selector => {
        if (!controls.has(selector)) controls.set(selector, new Element());
        return controls.get(selector);
    };
    const modal = element('[data-cover-picker]');
    modal.querySelector = element;
    modal.dataset.uploadUrl = '/admin/media';
    const form = new Element();
    form.querySelector = element;
    form.elements = { namedItem: element };
    const cover = element('cover_media_id');
    cover.value = '1';
    cover.dataset.previewUrl = '/media/1';
    const existing = new Element();
    existing.dataset = { id: '1', url: '/media/1', name: 'Old cover.jpg', alt: 'Old' };
    element('[data-cover-library]').append(existing);
    globalThis.document = { querySelector: element, createElement: () => new Element() };
    globalThis.window = new Element();
    globalThis.Option = class { dataset = {}; constructor(name, id) { this.text = name; this.value = id; } };
    t.after(() => { delete globalThis.document; delete globalThis.window; delete globalThis.Option; });
    let resolveUpload;
    t.mock.method(globalThis, 'fetch', () => new Promise(resolve => { resolveUpload = resolve; }));
    setupCoverPicker(form, 'token');
    modal.dispatchEvent(new Event('show.bs.modal'));
    assert.equal(existing.attributes['aria-pressed'], 'true');
    element('[data-cover-search]').value = 'missing';
    element('[data-cover-search]').dispatchEvent(new Event('input'));
    assert.equal(existing.hidden, true);
    assert.equal(element('[data-cover-empty]').hidden, false);
    element('[data-cover-file]').files = [new File(['image'], '<new>.png', { type: 'image/png' })];
    element('[data-cover-upload]').click();
    assert.equal(element('[data-cover-confirm]').disabled, true);
    const hide = new Event('hide.bs.modal', { cancelable: true });
    modal.dispatchEvent(hide);
    assert.equal(hide.defaultPrevented, true);
    resolveUpload({ ok: true, json: async () => ({ id: 2, location: '/media/2', alt: 'New' }) });
    await setImmediate();
    assert.equal(cover.value, '1', 'upload does not replace cover until confirmed');
    assert.equal(element('[data-cover-library]').children.length, 2);
    assert.equal(element('[data-cover-library]').children[0].children[1].textContent, '<new>.png');
    assert.equal(element('og_media_id').children[0].value, '2');
    assert.equal(element('[data-cover-confirm]').disabled, false);
    element('[data-cover-confirm]').click();
    assert.equal(cover.value, '2');
    assert.equal(cover.dataset.previewUrl, '/media/2');
    assert.equal(element('[data-cover-preview]').src, '/media/2');
    element('[data-cover-remove]').click();
    assert.equal(cover.value, '');
    assert.equal(element('[data-cover-preview]').hidden, true);
    assert.equal(element('[data-cover-library]').children.length, 2, 'removing cover keeps library files');
});

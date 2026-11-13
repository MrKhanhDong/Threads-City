import test from 'node:test';
import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import vm from 'node:vm';

const nextTurn = () => new Promise(resolve => setImmediate(resolve));

async function fixture() {
    class Element {
        style = { display: 'none' };
        dataset = {};
        children = [];
        listeners = {};
        value = '';
        textContent = '';
        classList = { add() {}, remove() {}, toggle() {} };
        addEventListener(name, callback) { this.listeners[name] = callback; }
        dispatchEvent(event) { this.listeners[event.type]?.(event); }
        setAttribute() {}
        getAttribute(name) { return this.attributes?.[name]; }
        append(...children) { this.children.push(...children); }
        appendChild(child) { this.append(child); }
        querySelectorAll() { return []; }
        querySelector() { return null; }
        reset() {}
        set innerHTML(value) { this.children = []; }
    }
    const elements = new Map(), revoked = [], uploads = [];
    let ready, serial = 0;
    const element = id => {
        if (!elements.has(id)) elements.set(id, new Element());
        return elements.get(id);
    };
    const doc = {
        getElementById: element,
        createElement: () => new Element(),
        querySelector: selector => selector === 'meta[name="csrf-token"]' ? { getAttribute: () => 'csrf' } : null,
        querySelectorAll: () => [],
        addEventListener(name, callback) { if (name === 'DOMContentLoaded') ready = callback; },
    };
    const win = {
        uploadThreadImage(file, url, csrf, options) {
            return new Promise((resolve, reject) => {
                uploads.push({ file, options, resolve, reject });
                options.signal.addEventListener('abort', () => reject(new DOMException('Canceled', 'AbortError')));
            });
        },
    };
    const source = await readFile(new URL('../../resources/views/threads/partials/composer_modal.blade.php', import.meta.url), 'utf8');
    const script = source.match(/<script>([\s\S]*?)<\/script>/)[1];
    vm.runInNewContext(script, {
        document: doc, window: win, AbortController, Event, console,
        URL: { createObjectURL: () => `blob:preview-${++serial}`, revokeObjectURL: url => revoked.push(url) },
        setTimeout() {}, clearTimeout() {},
    });
    ready();
    return {
        element, uploads, revoked, win,
        choose(count = 1) {
            const input = element('modalComposerFileInput');
            input.files = Array.from({ length: count }, (_, index) => ({ name: `photo-${index}.jpg`, type: 'image/jpeg', size: 1024 }));
            input.listeners.change.call(input);
        },
        close() { element('newThreadModal').listeners['hidden.bs.modal'](); },
    };
}

test('composer previews chosen files immediately and keeps other pending thumbnails when one finishes', async () => {
    const ui = await fixture();
    ui.choose(2);
    const preview = ui.element('modalMediaPreview');
    assert.equal(preview.children.length, 2);
    assert.equal(preview.children[0].children[0].src, 'blob:preview-1');
    assert.equal(ui.element('modalSubmitBtn').disabled, true);
    ui.uploads[0].resolve({ id: 10, url: '/media/10' });
    await nextTurn();
    assert.equal(preview.children.length, 2);
    assert.equal(ui.element('modalMediaIdsContainer').children.length, 1);
    assert.equal(ui.element('modalSubmitBtn').disabled, true);
    ui.uploads[1].resolve({ id: 11, url: '/media/11' });
    await nextTurn();
    assert.equal(preview.children.length, 2);
    assert.equal(ui.element('modalMediaIdsContainer').children.length, 2);
    assert.equal(ui.element('modalSubmitBtn').disabled, false);
    assert.equal(preview.children[0].children[0].src, 'blob:preview-1');
    ui.close();
    assert.deepEqual(ui.revoked.sort(), ['blob:preview-1', 'blob:preview-2']);
});

test('closing the composer aborts pending uploads and late responses cannot reattach old images', async () => {
    const ui = await fixture();
    ui.choose();
    ui.close();
    assert.equal(ui.uploads[0].options.signal.aborted, true);
    await nextTurn();
    assert.equal(ui.element('modalMediaIdsContainer').children.length, 0);
    assert.equal(ui.element('modalMediaPreview').style.display, 'none');
    assert.deepEqual(ui.revoked, ['blob:preview-1']);
});

test('a failed upload removes only the failed thumbnail and successful images remain usable', async () => {
    const ui = await fixture();
    ui.choose(2);
    ui.uploads[0].resolve({ id: 10, url: '/media/10' });
    ui.uploads[1].reject(new Error('Tải ảnh quá lâu.'));
    await nextTurn();
    assert.equal(ui.element('modalMediaPreview').children.length, 1);
    assert.equal(ui.element('modalMediaIdsContainer').children.length, 1);
    assert.equal(ui.element('modalSubmitBtn').disabled, false);
    assert.deepEqual(ui.revoked, ['blob:preview-2']);
    assert.match(ui.element('threadsGlobalToast').textContent, /Tải ảnh quá lâu/);
});

test('a selected song enables music-only posts and composer reset clears the selection', async () => {
    const ui = await fixture();
    ui.element('modalMusicId').value = 'song123';
    ui.win.updateComposerSubmitState();
    assert.equal(ui.element('modalSubmitBtn').disabled, false);
    ui.element('newThreadModal').addEventListener('composer:reset', () => { ui.element('modalMusicId').value = ''; });
    ui.close();
    assert.equal(ui.element('modalMusicId').value, '');
    assert.equal(ui.element('modalSubmitBtn').disabled, true);
});

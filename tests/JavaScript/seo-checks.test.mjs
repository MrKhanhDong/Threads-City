import test from 'node:test';
import assert from 'node:assert/strict';
import { setImmediate } from 'node:timers/promises';
import { renderSeoChecks, setupSeoChecks } from '../../resources/js/seo-checks.js';

class Element extends EventTarget {
    children = [];
    dataset = {};
    attributes = {};
    textContent = '';
    classes = new Set();
    classList = { toggle: (name, enabled) => enabled ? this.classes.add(name) : this.classes.delete(name) };
    replaceChildren(...children) { this.children = children; }
    append(...children) { this.children.push(...children); }
    setAttribute(name, value) { this.attributes[name] = value; }
    removeAttribute(name) { delete this.attributes[name]; }
}

function mockDocument(t) {
    globalThis.document = { createElement: () => new Element(), createTextNode: text => ({ textContent: text }) };
    globalThis.window = new Element();
    t.after(() => { delete globalThis.document; delete globalThis.window; });
}

test('checklist distinguishes passed, missing and not applicable without rendering HTML', t => {
    mockDocument(t);
    const list = new Element();
    renderSeoChecks(list, { 'Có ảnh bìa': true, 'Có heading': false, 'Ảnh nội dung có ALT': null, '<img src=x>': false });
    assert.equal(list.children[0].classes.has('pass'), true);
    assert.equal(list.children[1].children[0].textContent, '○');
    assert.equal(list.children[2].classes.has('pass'), false);
    assert.equal(list.children[2].children[0].textContent, '–');
    assert.match(list.children[2].children[1].textContent, /chưa có ảnh/);
    assert.equal(list.children[3].children[1].textContent, '<img src=x>');
});

test('live checks debounce edits, use unsaved editor content and ignore late responses', async t => {
    mockDocument(t);
    t.mock.timers.enable({ apis: ['setTimeout'] });
    const panel = new Element(), list = new Element(), status = new Element(), form = new Element();
    panel.dataset.url = '/admin/posts/seo-checks';
    panel.querySelector = selector => selector === '[data-seo-check-list]' ? list : status;
    form.querySelector = () => panel;
    form.elements = { namedItem: name => ({ value: name === 'title' ? 'Chưa lưu' : '' }) };
    const requests = [];
    let content = '<p>Nội dung cũ</p>';
    t.mock.method(globalThis, 'fetch', (url, options) => new Promise(resolve => requests.push({ url, options, resolve })));
    const update = setupSeoChecks(form, 'csrf', () => content);
    t.mock.timers.tick(799);
    assert.equal(requests.length, 0);
    t.mock.timers.tick(1);
    assert.equal(requests.length, 1);
    content = '<h2>Nội dung mới chưa lưu</h2>';
    update();
    update();
    t.mock.timers.tick(800);
    assert.equal(requests.length, 2);
    assert.equal(requests[0].options.signal.aborted, true);
    assert.equal(requests[1].options.headers['X-CSRF-TOKEN'], 'csrf');
    assert.equal(JSON.parse(requests[1].options.body).content, content);
    requests[1].resolve({ ok: true, json: async () => ({ checks: { 'Có heading': true } }) });
    await setImmediate();
    assert.equal(list.children[0].classes.has('pass'), true);
    requests[0].resolve({ ok: true, json: async () => ({ checks: { 'Có heading': false } }) });
    await setImmediate();
    assert.equal(list.children[0].classes.has('pass'), true);
    assert.equal(status.textContent, '');
    assert.equal(status.hidden, true);
    assert.equal(list.hidden, false);
    form.dispatchEvent(new Event('change'));
    t.mock.timers.tick(800);
    requests[2].resolve({ ok: false, json: async () => ({ message: 'Phiên đăng nhập hết hạn.' }) });
    await setImmediate();
    assert.match(status.textContent, /Chưa cập nhật.*hết hạn/);
    assert.equal(status.hidden, false);
    assert.equal(list.hidden, true, 'stale checkmarks are not displayed as current results after failure');
    assert.equal(panel.attributes['aria-busy'], undefined);
    window.dispatchEvent(new Event('focus'));
    t.mock.timers.tick(800);
    requests[3].resolve({ ok: true, json: async () => ({ checks: { 'Có heading': false } }) });
    await setImmediate();
    assert.equal(status.hidden, true);
    assert.equal(list.hidden, false);
    assert.equal(list.children[0].classes.has('pass'), false);
});

test('malformed results cannot turn truthy strings into passing checks', t => {
    mockDocument(t);
    const list = new Element();
    for (const checks of [null, [], {}, { 'Có heading': 'false' }, { 'Có ảnh bìa': 1 }]) {
        assert.throws(() => renderSeoChecks(list, checks), /không hợp lệ/);
    }
    assert.equal(list.children.length, 0);
});

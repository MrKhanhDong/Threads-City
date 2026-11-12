import test from 'node:test';
import assert from 'node:assert/strict';
import { setImmediate } from 'node:timers/promises';
import { refreshFeedUrl, pullDistance, setupPullToRefresh, setupLightbox, setupFeedPagination } from '../../resources/js/mobile-experience.js';

class Element {
    handlers = {}; dataset = {}; children = []; hidden = true; textContent = ''; disabled = false;
    addEventListener(name, callback) { this.handlers[name] = callback; }
    emit(name, event = {}) { return this.handlers[name]?.(event); }
    append(item) { this.children.push(item); }
    after(item) { this.afterElement = item; }
    set innerHTML(value) { this.html = value; this.children = value ? [{}] : []; }
    get innerHTML() { return this.html; }
    removeAttribute(name) { delete this[name]; }
}

function pullFixture() {
    const doc = new Element(), feed = new Element(), indicator = new Element();
    let modal = false;
    feed.innerHTML = '<article>Existing</article>';
    doc.querySelector = selector => selector === '[data-stream-primary]' ? feed : selector === '[data-pull-refresh]' ? indicator : selector.startsWith('dialog') && modal ? {} : null;
    doc.createElement = () => new Element();
    const win = { matchMedia: () => ({ matches: true }), scrollY: 0, location: { href: 'https://threads.test/?tab=following&q=hello&page=3' }, setTimeout() {} };
    win.top = win;
    setupPullToRefresh(doc, win);
    const gesture = () => {
        doc.emit('touchstart', { touches: [{ clientX: 10, clientY: 10 }], target: { closest: () => null } });
        doc.emit('touchmove', { touches: [{ clientX: 10, clientY: 180 }], cancelable: true, preventDefault() {} });
        doc.emit('touchend');
    };
    return { doc, feed, indicator, gesture, setModal(value) { modal = value; } };
}

test('refresh keeps tab/search, restarts pagination and ignores horizontal or upward gestures', () => {
    assert.equal(refreshFeedUrl('https://threads.test/?tab=following&q=hello&page=3'), 'https://threads.test/?tab=following&q=hello');
    assert.equal(pullDistance(100, 50, 0), 0);
    assert.equal(pullDistance(10, 80, 100), 0);
    assert.equal(pullDistance(10, 180, 0), 85);
});

test('pull refresh prevents overlapping loads, preserves active filters and resets the next-page link', async t => {
    let resolve;
    const fetcher = t.mock.method(globalThis, 'fetch', (url) => {
        assert.equal(url, 'https://threads.test/?tab=following&q=hello');
        return new Promise(done => { resolve = done; });
    });
    const fixture = pullFixture();
    fixture.gesture(); fixture.gesture();
    assert.equal(fetcher.mock.callCount(), 1); assert.equal(fixture.feed.dataset.loading, 'true');
    resolve({ ok: true, json: async () => ({ html: '<article>Fresh</article>', nextPageUrl: 'https://threads.test/?tab=following&page=2' }) });
    await setImmediate();
    assert.equal(fixture.feed.innerHTML, '<article>Fresh</article>');
    assert.equal(fixture.feed.afterElement.children[0].href, 'https://threads.test/?tab=following&page=2');
    assert.equal(fixture.feed.dataset.loading, 'false');
});

test('network failure retains the existing feed and an open composer prevents refresh', async t => {
    const fetcher = t.mock.method(globalThis, 'fetch', async () => { throw new Error('offline'); });
    const fixture = pullFixture();
    fixture.setModal(true); fixture.gesture(); assert.equal(fetcher.mock.callCount(), 0);
    fixture.setModal(false); fixture.gesture(); await setImmediate();
    assert.equal(fixture.feed.innerHTML, '<article>Existing</article>');
    assert.match(fixture.indicator.textContent, /Không kết nối/);
    assert.equal(fixture.feed.dataset.loading, 'false');
});

test('lightbox stays within the selected gallery, supports keyboard navigation and restores focus on close', () => {
    const doc = new Element(), dialog = new Element(), image = new Element(), status = new Element(), previous = new Element(), next = new Element(), close = new Element();
    const controls = { img: image, '[data-lightbox-status]': status, '[data-lightbox-previous]': previous, '[data-lightbox-next]': next, '[data-lightbox-close]': close };
    dialog.querySelector = selector => controls[selector]; dialog.showModal = () => { dialog.open = true; }; dialog.close = () => { dialog.open = false; dialog.emit('close'); };
    const links = [1, 2].map(number => ({ href: `/media/${number}`, querySelector: () => ({ alt: `Photo ${number}` }), closest: () => ({ querySelectorAll: () => links }), focus() { this.focused = true; } }));
    doc.querySelector = () => dialog; doc.defaultView = {};
    setupLightbox(doc);
    doc.emit('click', { target: { closest: () => links[0] }, preventDefault() {} });
    assert.equal(dialog.open, true); assert.equal(image.src, '/media/1'); assert.equal(previous.disabled, true);
    dialog.emit('keydown', { key: 'ArrowRight', preventDefault() {} });
    assert.equal(image.src, '/media/2'); assert.equal(next.disabled, true); assert.equal(status.textContent, '2 / 2');
    close.emit('click'); assert.equal(dialog.open, false); assert.equal(links[0].focused, true); assert.equal(image.src, undefined);
});

test('dev pagination keeps its cursor after a failed load and coordinates with live refresh', async t => {
    const doc = new Element(), feed = new Element(), control = new Element();
    const events = [], messages = [];
    control.dataset.nextUrl = 'https://threads.test/?tab=following&page=2';
    control.tagName = 'BUTTON'; control.setAttribute = (name, value) => { control[name] = value; };
    const wrapper = { remove() { this.removed = true; } }; control.closest = () => wrapper;
    const duplicate = { id: 'thread-1', remove() { this.removed = true; } };
    doc.querySelector = () => feed; doc.getElementById = id => id === 'thread-1' ? {} : null;
    doc.createElement = () => ({ content: { querySelectorAll: () => [duplicate] } });
    const win = { dispatchEvent: event => events.push(event.type), showToast: message => messages.push(message) };
    const fetcher = t.mock.method(globalThis, 'fetch', async () => { throw new Error('offline'); });
    setupFeedPagination(doc, win);
    const click = () => doc.emit('click', { target: { closest: () => control }, preventDefault() {} });
    await click();
    assert.equal(control.dataset.nextUrl, 'https://threads.test/?tab=following&page=2');
    assert.equal(feed.dataset.loading, 'false'); assert.equal(messages.length, 1);
    fetcher.mock.mockImplementation(async () => ({ ok: true, json: async () => ({ html: '<article>Next page</article>', nextPageUrl: 'https://threads.test/?tab=following&page=3' }) }));
    await click(); assert.equal(duplicate.removed, true); assert.equal(control.dataset.nextUrl, 'https://threads.test/?tab=following&page=3');
    assert.deepEqual(events, ['threads:refresh-start', 'threads:refresh', 'threads:refresh-start', 'threads:refresh']);
});

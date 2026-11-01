import test from 'node:test';
import assert from 'node:assert/strict';
import { collectionOrder, setupLiveUpdates, syncRegion } from '../../resources/js/live-updates.js';

test('new posts prepend without losing older manually loaded pages', () => {
    assert.deepEqual(collectionOrder(['thread-3', 'thread-2', 'thread-1'], ['thread-4', 'thread-3'], [], true), ['thread-4', 'thread-3', 'thread-2', 'thread-1']);
});

test('deleted posts disappear even if they belong to an older loaded page', () => {
    assert.deepEqual(collectionOrder(['thread-3', 'thread-2', 'thread-1'], ['thread-3', 'thread-2'], ['1'], true), ['thread-3', 'thread-2']);
});

test('replies synchronize in server order without duplicates', () => {
    assert.deepEqual(collectionOrder(['thread-2'], ['thread-1', 'thread-2', 'thread-2', 'thread-3']), ['thread-1', 'thread-2', 'thread-3']);
});

test('unbookmarked posts and removed notifications leave their filtered lists', () => {
    assert.deepEqual(collectionOrder(['thread-1', 'thread-2'], ['thread-2']), ['thread-2']);
    assert.deepEqual(collectionOrder(['notification-a'], []), []);
});

test('empty feed accepts its first post', () => {
    assert.deepEqual(collectionOrder([], ['thread-1'], [], true), ['thread-1']);
});

test('detail refresh preserves loading, seeking and playing media then updates once paused', () => {
    let replaced = false, loading = true;
    const media = { paused: true, ended: false, seeking: false };
    const current = {
        outerHTML: 'Old detail', hasAttribute: () => false,
        querySelector: () => loading ? {} : null, querySelectorAll: selector => selector === 'video, audio' ? [media] : [],
        replaceWith() { replaced = true; },
    };
    const incoming = { outerHTML: 'Updated detail', querySelectorAll: () => [] };
    syncRegion(current, incoming, []);
    assert.equal(replaced, false);
    loading = false; media.paused = false;
    syncRegion(current, incoming, []);
    assert.equal(replaced, false);
    media.paused = true; media.seeking = true;
    syncRegion(current, incoming, []);
    assert.equal(replaced, false);
    media.seeking = false;
    syncRegion(current, incoming, []);
    assert.equal(replaced, true);
});

test('pausing in fullscreen does not let a live refresh remove the video', () => {
    let replaced = false;
    const player = {};
    const current = {
        ownerDocument: { fullscreenElement: player }, contains: node => node === player,
        outerHTML: 'Old detail', hasAttribute: () => false,
        querySelector: () => null, querySelectorAll: () => [], replaceWith() { replaced = true; },
    };
    syncRegion(current, { outerHTML: 'New detail' }, []);
    assert.equal(replaced, false);
});

test('a refresh waits until a photo drag and its momentum have finished', () => {
    let interacting = true, replaced = false;
    const current = {
        outerHTML: 'Photo card', hasAttribute: () => false,
        querySelector: selector => interacting && selector.includes('[data-media-interacting]') ? {} : null,
        querySelectorAll: () => [], replaceWith() { replaced = true; },
    };
    const incoming = { outerHTML: 'Updated photo card', querySelectorAll: () => [] };
    syncRegion(current, incoming, []);
    assert.equal(replaced, false);
    interacting = false;
    syncRegion(current, incoming, []);
    assert.equal(replaced, true);
});

test('photo position survives updated post statistics and resets only for different attachments', () => {
    for (const changedPhotos of [false, true]) {
        const gallery = (href, left) => ({ scrollLeft: left, querySelectorAll: () => [{ getAttribute: name => name === 'href' ? href : null }] });
        const previous = gallery('/media/1', 137), next = gallery(changedPhotos ? '/media/2' : '/media/1', 0);
        let replaced = false;
        const current = {
            outerHTML: 'Post with 3 likes', hasAttribute: () => false, querySelector: () => null,
            querySelectorAll: selector => selector === '[data-media-gallery]' ? [previous] : [],
            replaceWith() { replaced = true; },
        };
        const incoming = { outerHTML: 'Post with 4 likes', querySelectorAll: () => [next] };
        syncRegion(current, incoming, []);
        assert.equal(replaced, true);
        assert.equal(next.scrollLeft, changedPhotos ? 0 : 137);
    }
});

test('polling pauses in hidden tabs and resumes on visibility changes', async () => {
    const saved = Object.fromEntries(['document', 'window', 'location', 'setTimeout', 'clearTimeout'].map(key => [key, globalThis[key]]));
    const timers = new Map();
    const listeners = new Map();
    let serial = 0;
    let requests = 0;
    globalThis.document = {
        body: { dataset: { liveEnabled: '1' } }, hidden: true,
        querySelector: selector => selector === '[data-live-region]' ? {} : null,
        querySelectorAll: () => [],
        addEventListener: (name, callback) => listeners.set(name, callback),
        dispatchEvent: () => {},
    };
    globalThis.window = {
        fetch: async () => { requests++; return { ok: true, status: 200, headers: new Headers({ 'content-type': 'application/json' }), json: async () => ({ regions: [], removed: [] }) }; },
        addEventListener: (name, callback) => listeners.set(name, callback),
    };
    globalThis.location = new URL('https://example.com/');
    globalThis.setTimeout = (callback, delay) => { const id = ++serial; timers.set(id, { callback, delay }); return id; };
    globalThis.clearTimeout = id => timers.delete(id);
    try {
        setupLiveUpdates();
        assert.equal(timers.size, 0);
        document.hidden = false;
        listeners.get('visibilitychange')();
        const task = [...timers.values()][0];
        assert.equal(task.delay, 100);
        timers.clear();
        await task.callback();
        assert.equal(requests, 1);
        assert.equal([...timers.values()][0].delay, 8000);
        document.hidden = true;
        listeners.get('visibilitychange')();
        assert.equal(timers.size, 0);
    } finally {
        for (const [key, value] of Object.entries(saved)) {
            if (value === undefined) delete globalThis[key];
            else globalThis[key] = value;
        }
    }
});

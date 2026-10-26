import test from 'node:test';
import assert from 'node:assert/strict';
import { collectionOrder, setupLiveUpdates } from '../../resources/js/live-updates.js';

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

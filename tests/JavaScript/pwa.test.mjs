import test from 'node:test';
import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import vm from 'node:vm';
import { applicationServerKey } from '../../resources/js/pwa.js';

async function workerFixture() {
    const handlers = {}, notifications = [], cached = [], navigations = [];
    let allowed = false, online = true;
    const self = {
        location: { href: 'https://threads.test/sw.js' },
        addEventListener(name, callback) { handlers[name] = callback; }, skipWaiting: async () => {},
        registration: { showNotification: async (...args) => notifications.push(args) },
        clients: { claim: async () => {}, matchAll: async () => [], openWindow: async url => navigations.push(url) },
    };
    const context = { self, URL, caches: { open: async () => ({ addAll: async urls => cached.push(...urls) }), keys: async () => [], match: async () => 'offline-document' }, fetch: async (request, options) => {
        if (new URL(typeof request === 'string' || request instanceof URL ? request : request.url).pathname === '/push/context') {
            assert.equal(options.cache, 'no-store'); assert.equal(options.credentials, 'include'); assert.equal(options.headers['X-Push-Token'], 'device-token');
            return { ok: true, json: async () => ({ allowed }) };
        }
        if (!online) throw new Error('offline');
        return 'network-document';
    } };
    vm.runInNewContext(await readFile(new URL('../../public/sw.js', import.meta.url), 'utf8'), context);
    async function emit(name, event = {}) {
        let pending;
        handlers[name]({ ...event, waitUntil(value) { pending = value; }, respondWith(value) { pending = value; } });
        return pending;
    }
    return { emit, notifications, cached, navigations, setAllowed(value) { allowed = value; }, setOnline(value) { online = value; } };
}

test('VAPID public keys decode URL-safe bytes', () => { assert.deepEqual([...applicationServerKey('BAAB_w')], [4, 0, 1, 255]); });

test('worker only precaches the neutral offline page and icons; authenticated pages use the network', async () => {
    const worker = await workerFixture(); await worker.emit('install');
    assert.equal(worker.cached.length, 3); assert.ok(worker.cached.every(url => /offline\.html|pwa-icon-\d+\.png/.test(url)));
    const request = { method: 'GET', mode: 'navigate', url: 'https://threads.test/thong-bao' };
    assert.equal(await worker.emit('fetch', { request }), 'network-document');
    worker.setOnline(false); assert.equal(await worker.emit('fetch', { request }), 'offline-document');
    assert.equal(await worker.emit('fetch', { request: { ...request, mode: 'cors', url: 'https://threads.test/media/1' } }), undefined);
});

test('push verifies the current account before display and never exposes the sender or body from the payload', async () => {
    const worker = await workerFixture();
    const event = { data: { json: () => ({ token: 'device-token', notification: 'notification-id', title: 'Private sender', body: 'Private content' }) } };
    await worker.emit('push', event); assert.equal(worker.notifications.length, 0);
    worker.setAllowed(true); await worker.emit('push', event); assert.equal(worker.notifications.length, 1);
    assert.equal(worker.notifications[0][0], 'Threads City'); assert.ok(!JSON.stringify(worker.notifications).includes('Private'));
    await worker.emit('notificationclick', { notification: { close() {}, data: { url: 'https://evil.test/' } } });
    assert.equal(worker.navigations[0], 'https://threads.test/thong-bao');
});

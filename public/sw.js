const BASE = new URL('./', self.location.href);
const CACHE = 'threads-static-v1';
const OFFLINE = new URL('offline.html', BASE).href;
self.addEventListener('install', event => {
    event.waitUntil(caches.open(CACHE).then(cache => cache.addAll([OFFLINE, new URL('pwa-icon-192.png', BASE).href, new URL('pwa-icon-512.png', BASE).href])).then(() => self.skipWaiting()));
});
self.addEventListener('activate', event => {
    event.waitUntil(caches.keys().then(keys => Promise.all(keys.filter(key => key.startsWith('threads-static-') && key !== CACHE).map(key => caches.delete(key)))).then(() => self.clients.claim()));
});
self.addEventListener('fetch', event => {
    if (event.request.mode !== 'navigate' || event.request.method !== 'GET') return;
    event.respondWith(fetch(event.request, { cache: 'no-store' }).catch(() => caches.match(OFFLINE)));
});
self.addEventListener('push', event => {
    event.waitUntil((async () => {
        let payload;
        try { payload = event.data?.json(); } catch { return; }
        if (!payload || typeof payload.token !== 'string' || typeof payload.notification !== 'string') return;
        const url = new URL('push/context', BASE); url.searchParams.set('notification', payload.notification);
        try {
            const response = await fetch(url, { credentials: 'include', cache: 'no-store', headers: { Accept: 'application/json', 'X-Push-Token': payload.token } });
            if (!response.ok) return;
            const context = await response.json();
            if (context.allowed !== true) return;
            await self.registration.showNotification('Threads City', {
                body: 'Bạn có thông báo mới. Mở Threads để xem.',
                icon: new URL('pwa-icon-192.png', BASE).href,
                badge: new URL('pwa-icon-192.png', BASE).href,
                tag: 'threads-activity', data: { url: new URL('thong-bao', BASE).href },
            });
        } catch {}
    })());
});
self.addEventListener('notificationclick', event => {
    event.notification.close();
    const url = new URL('thong-bao', BASE).href;
    event.waitUntil((async () => {
        const clients = await self.clients.matchAll({ type: 'window', includeUncontrolled: true });
        const existing = clients.find(client => new URL(client.url).origin === BASE.origin && client.url.startsWith(BASE.href));
        if (existing) { await existing.navigate(url); return existing.focus(); }
        return self.clients.openWindow(url);
    })());
});

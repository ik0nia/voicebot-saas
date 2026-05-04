// Sambla Operator Console — Service Worker
// Strategy:
//   - Static (build/, fonts, images): cache-first
//   - /dashboard/operator nav: network-first cu fallback la cache
//   - Push notifications: handle push event + click → focus tab existent

const VERSION = 'sambla-operator-v7';
const STATIC_CACHE = `${VERSION}-static`;
const RUNTIME_CACHE = `${VERSION}-runtime`;

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(STATIC_CACHE)
            .then((cache) => cache.addAll([
                '/manifest-operator.json',
                '/images/logo-icon.png',
            ]))
            .then(() => self.skipWaiting())
    );
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((keys) =>
            Promise.all(
                keys
                    .filter((k) => !k.startsWith(VERSION))
                    .map((k) => caches.delete(k))
            )
        ).then(() => self.clients.claim())
    );
});

self.addEventListener('fetch', (event) => {
    const req = event.request;
    if (req.method !== 'GET') return;

    const url = new URL(req.url);
    if (url.origin !== self.location.origin) return;

    // Static cache-first
    if (url.pathname.startsWith('/build/') ||
        url.pathname.startsWith('/images/') ||
        url.pathname.startsWith('/fonts/')) {
        event.respondWith(
            caches.match(req).then((cached) => cached || fetch(req).then((resp) => {
                if (resp.ok) {
                    const clone = resp.clone();
                    caches.open(STATIC_CACHE).then((c) => c.put(req, clone));
                }
                return resp;
            }).catch(() => cached))
        );
        return;
    }

    // Operator nav network-first
    if (url.pathname.startsWith('/dashboard/operator')) {
        event.respondWith(
            fetch(req).catch(() => caches.match(req).then((cached) =>
                cached || new Response('Offline — încearcă să te reconectezi.', {
                    status: 503,
                    headers: { 'Content-Type': 'text/plain; charset=utf-8' },
                })
            ))
        );
        return;
    }
});

// Push notification handler
self.addEventListener('push', (event) => {
    if (!event.data) return;
    let payload = {};
    try { payload = event.data.json(); } catch (e) {}

    const title = payload.title || 'Sambla';
    const options = {
        body: payload.body || '',
        icon: payload.icon || '/images/logo-icon.png',
        badge: '/images/logo-icon.png',
        tag: payload.tag || 'sambla',
        data: { url: payload.url || '/dashboard/operator' },
        renotify: true,
        requireInteraction: payload.requireInteraction || false,
    };

    event.waitUntil(self.registration.showNotification(title, options));
});

// Click → focus tab existent sau deschide nou
self.addEventListener('notificationclick', (event) => {
    event.notification.close();
    const targetUrl = event.notification.data?.url || '/dashboard/operator';

    event.waitUntil(
        self.clients.matchAll({ type: 'window', includeUncontrolled: true })
            .then((clients) => {
                for (const client of clients) {
                    if (client.url.includes('/dashboard/operator') && 'focus' in client) {
                        client.postMessage({ type: 'navigate', url: targetUrl });
                        return client.focus();
                    }
                }
                if (self.clients.openWindow) {
                    return self.clients.openWindow(targetUrl);
                }
            })
    );
});

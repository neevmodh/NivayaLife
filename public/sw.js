// Minimal service worker — its only job is satisfying the browser's PWA
// installability requirement (a registered SW + valid manifest), not
// offline data access. Novix is an authenticated, data-heavy medical-
// records app: caching HTML/API responses risks showing stale or wrong
// medical data with no network indicator, so this deliberately stays
// network-only for everything except the versioned, hashed Vite build
// output (safe to cache — the filename itself changes on every deploy,
// so there's no staleness risk).
const BUILD_CACHE = 'novix-build-v1';

self.addEventListener('install', (event) => {
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(self.clients.claim());
});

self.addEventListener('fetch', (event) => {
    const url = new URL(event.request.url);

    if (url.origin !== self.location.origin || !url.pathname.startsWith('/build/')) {
        return; // let the browser handle it normally — network only
    }

    event.respondWith(
        caches.open(BUILD_CACHE).then(async (cache) => {
            const cached = await cache.match(event.request);
            if (cached) return cached;

            const response = await fetch(event.request);
            if (response.ok) cache.put(event.request, response.clone());
            return response;
        })
    );
});

// Medication/vaccination reminders. The payload is plain JSON (no
// encryption-at-rest concerns beyond what the Push API itself already
// guarantees) — { title, body, url }.
self.addEventListener('push', (event) => {
    let data = { title: 'Novix', body: 'You have a new reminder.', url: '/dashboard' };
    try {
        if (event.data) data = { ...data, ...event.data.json() };
    } catch (e) {
        // Malformed payload — fall back to the generic notification above
        // rather than dropping it silently.
    }

    event.waitUntil(
        self.registration.showNotification(data.title, {
            body: data.body,
            icon: '/icons/icon-192.png',
            badge: '/icons/icon-192.png',
            data: { url: data.url },
        })
    );
});

self.addEventListener('notificationclick', (event) => {
    event.notification.close();
    const targetUrl = event.notification.data?.url || '/dashboard';

    event.waitUntil(
        self.clients.matchAll({ type: 'window', includeUncontrolled: true }).then((clients) => {
            for (const client of clients) {
                if (client.url.includes(targetUrl) && 'focus' in client) return client.focus();
            }
            if (self.clients.openWindow) return self.clients.openWindow(targetUrl);
        })
    );
});

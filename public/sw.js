const CACHE_NAME = 'umco-house-cache-v1';
const STATIC_ASSETS = [
    '/',
    '/favicon.ico',
    '/manifest.json',
    '/sounds/new-order.mp3',
    '/sounds/ticket.mp3',
    '/sounds/payment-success.mp3',
    '/sounds/ready.mp3',
    '/sounds/alert.mp3',
];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => {
            return cache.addAll(STATIC_ASSETS).catch((err) => {
                console.warn('[ServiceWorker] Some assets failed to cache:', err);
            });
        })
    );
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((keys) => {
            return Promise.all(
                keys.filter((key) => key !== CACHE_NAME).map((key) => caches.delete(key))
            );
        })
    );
    self.clients.claim();
});

self.addEventListener('fetch', (event) => {
    const request = event.request;
    const url = new URL(request.url);

    // Only handle GET requests
    if (request.method !== 'GET') {
        return;
    }

    // Skip WebSockets or livewire updates or API polling
    if (url.pathname.startsWith('/livewire/') || 
        url.pathname.startsWith('/reverb/') || 
        url.pathname.startsWith('/api/')) {
        return;
    }

    // For static assets (build, sounds, fonts): Cache-first with network fallback
    if (url.pathname.startsWith('/build/') || 
        url.pathname.startsWith('/sounds/') ||
        url.hostname.includes('fonts.googleapis.com') ||
        url.hostname.includes('fonts.gstatic.com') ||
        url.hostname.includes('unpkg.com')) {
        event.respondWith(
            caches.match(request).then((cachedResponse) => {
                if (cachedResponse) return cachedResponse;
                return fetch(request).then((networkResponse) => {
                    if (networkResponse && networkResponse.status === 200) {
                        const responseToCache = networkResponse.clone();
                        caches.open(CACHE_NAME).then((cache) => {
                            cache.put(request, responseToCache);
                        });
                    }
                    return networkResponse;
                }).catch(() => cachedResponse);
            })
        );
        return;
    }

    // For HTML navigation: Network first, fallback to cached page if offline
    if (request.headers.get('accept')?.includes('text/html')) {
        event.respondWith(
            fetch(request).catch(() => {
                return caches.match(request).then((response) => {
                    return response || caches.match('/');
                });
            })
        );
    }
});

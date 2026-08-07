const CACHE_NAME = 'familytree-v1';

const APP_SHELL = [
  '/',
  '/assets/css/app.css',
  '/index.php?route=admin/dashboard'
];

// Install: cache the app shell
self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => {
      return cache.addAll(APP_SHELL);
    }).then(() => {
      // Activate immediately without waiting for old tabs to close
      return self.skipWaiting();
    })
  );
});

// Activate: clean up stale caches from previous versions
self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((keys) => {
      return Promise.all(
        keys
          .filter((key) => key !== CACHE_NAME)
          .map((key) => caches.delete(key))
      );
    }).then(() => self.clients.claim())
  );
});

// Fetch: network-first, fall back to cache for GET requests
self.addEventListener('fetch', (event) => {
  // Only handle GET requests; let everything else pass through
  if (event.request.method !== 'GET') return;

  event.respondWith(
    fetch(event.request)
      .then((networkResponse) => {
        // Store a fresh copy in the cache before returning
        const responseClone = networkResponse.clone();
        caches.open(CACHE_NAME).then((cache) => {
          cache.put(event.request, responseClone);
        });
        return networkResponse;
      })
      .catch(() => {
        // Network unavailable — serve from cache if possible
        return caches.match(event.request).then((cached) => {
          if (cached) return cached;
          // Nothing in cache either — return a minimal offline response
          return new Response(
            '<h1>You are offline</h1><p>Please reconnect to use FamilyTree.</p>',
            {
              status: 503,
              headers: { 'Content-Type': 'text/html; charset=utf-8' }
            }
          );
        });
      })
  );
});

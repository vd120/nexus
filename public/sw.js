const CACHE_NAME = 'nexus-cache-v2';

const STATIC_ASSETS = [
    '/css/app-layout.css',
    '/css/comments.css',
    '/css/partial-posts.css',
    '/css/mobile-header.css',
    '/css/modals.css',
    '/vendor/fontawesome/css/all.min.css',
    '/fonts/cairo/cairo-arabic.woff2',
    '/fonts/cairo/cairo-latin-ext.woff2',
    '/fonts/cairo/cairo-latin.woff2'
];

self.addEventListener('install', (event) => {
  // Pre-cache core layout assets and local fonts on install
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => {
      return cache.addAll(STATIC_ASSETS).catch(err => {
        console.warn('Failed to pre-cache some assets:', err);
      });
    })
  );
  self.skipWaiting();
});

self.addEventListener('activate', (event) => {
  // Clean up older cache versions
  event.waitUntil(
    caches.keys().then((cacheNames) => {
      return Promise.all(
        cacheNames.map((cache) => {
          if (cache !== CACHE_NAME) {
            return caches.delete(cache);
          }
        })
      );
    })
  );
  self.clients.claim();
});

self.addEventListener('fetch', (event) => {
  const url = new URL(event.request.url);
  
  // Skip service worker for sensitive routes (Auth, API, dynamic views, socket.io)
  if (url.pathname.startsWith('/login') || 
      url.pathname.startsWith('/register') || 
      url.pathname.startsWith('/logout') || 
      url.pathname.startsWith('/auth/') || 
      url.pathname.startsWith('/api/') ||
      url.pathname.startsWith('/chat') ||
      url.pathname.startsWith('/socket.io/') ||
      url.pathname.match(/\.(mp4|webm|ogg|wav|mp3)$/i)) {
    return;
  }

  // Cache-First strategy for local static files
  const isStaticAsset = url.origin === self.location.origin && 
    (url.pathname.match(/\.(woff2|woff|ttf|css|js|png|jpg|jpeg|gif|svg|ico)$/i) || 
     STATIC_ASSETS.includes(url.pathname));

  if (isStaticAsset) {
    event.respondWith(
      caches.match(event.request).then((cachedResponse) => {
        if (cachedResponse) {
          return cachedResponse;
        }
        return fetch(event.request).then((networkResponse) => {
          if (networkResponse && networkResponse.status === 200) {
            const responseToCache = networkResponse.clone();
            caches.open(CACHE_NAME).then((cache) => {
              cache.put(event.request, responseToCache);
            });
          }
          return networkResponse;
        }).catch((err) => {
           // Fallback for failed network requests
           return new Response('Network error', { status: 500 });
        });
      })
    );
  } else {
    // Network-First strategy
    event.respondWith(
      fetch(event.request).catch((err) => {
        return caches.match(event.request);
      })
    );
  }
});

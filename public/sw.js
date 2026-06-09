const CACHE_NAME = 'nexus-cache-v4';

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

// ── Push notification received ──────────────────────────────────────────────
self.addEventListener('push', (event) => {
  if (!event.data) return;

  let payload;
  try { payload = event.data.json(); } catch (e) { return; }

  const title   = payload.title || 'Nexus';
  const options = {
    body:               payload.body || '',
    icon:               payload.icon || '/favicon.ico',
    badge:              payload.badge || '/favicon.ico',
    tag:                payload.tag || 'nexus-notification',
    requireInteraction: payload.requireInteraction || false,
    silent:             payload.silent || false,
    data:               payload.data || {},
    actions: payload.data && payload.data.type === 'call' ? [
      { action: 'accept',  title: '✅ Accept'  },
      { action: 'decline', title: '❌ Decline' },
    ] : [],
  };

  event.waitUntil(self.registration.showNotification(title, options));
});

// ── Notification click ──────────────────────────────────────────────────────
self.addEventListener('notificationclick', (event) => {
  event.notification.close();

  const data   = event.notification.data || {};
  const url    = data.url || '/';
  const action = event.action;

  if (data.type === 'call' && data.callId) {
    if (action === 'decline') {
      event.waitUntil(
        fetch('/call/' + data.callId + '/reject', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
          credentials: 'same-origin',
        }).catch(() => {})
      );
      return;
    }
  }

  event.waitUntil(
    clients.matchAll({ type: 'window', includeUncontrolled: true }).then((windowClients) => {
      for (const client of windowClients) {
        if (client.url.includes(url) && 'focus' in client) {
          return client.focus();
        }
      }
      if (clients.openWindow) {
        return clients.openWindow(url);
      }
    })
  );
});

// ── Fetch (cache strategy) ──────────────────────────────────────────────────
self.addEventListener('fetch', (event) => {
  const url = new URL(event.request.url);

  // Skip SW for sensitive/dynamic routes
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

  // Cache-First for static assets
  const isStaticAsset = url.origin === self.location.origin &&
    (url.pathname.match(/\.(woff2|woff|ttf|css|js|png|jpg|jpeg|gif|svg|ico)$/i) ||
     STATIC_ASSETS.includes(url.pathname));

  if (isStaticAsset) {
    event.respondWith(
      caches.match(event.request).then((cachedResponse) => {
        if (cachedResponse) return cachedResponse;
        return fetch(event.request).then((networkResponse) => {
          if (networkResponse && networkResponse.status === 200) {
            const responseToCache = networkResponse.clone();
            caches.open(CACHE_NAME).then((cache) => {
              cache.put(event.request, responseToCache);
            });
          }
          return networkResponse;
        }).catch(() => {
          return new Response('', { status: 404 });
        });
      })
    );
  } else {
    // Network-First for everything else
    event.respondWith(
      fetch(event.request).catch(() => {
        return caches.match(event.request);
      })
    );
  }
});

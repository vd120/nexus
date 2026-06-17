const CACHE_NAME = 'nexus-cache-' + (typeof __BUILD_HASH__ !== 'undefined' ? __BUILD_HASH__ : 'dev');
const OFFLINE_URL = '/offline.html';

const STATIC_ASSETS = [
    OFFLINE_URL,
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
          if (cache.startsWith('nexus-cache-') && cache !== CACHE_NAME) {
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
    icon:               payload.icon || '/images/icons/nexus-icon-192.png',
    badge:              payload.badge || '/images/icons/nexus-notification-badge.png',
    tag:                payload.tag || 'nexus-notification',
    requireInteraction: payload.requireInteraction || false,
    silent:             payload.silent || false,
    data:               payload.data || {},
    actions: payload.data && payload.data.type === 'call' ? [
      { action: 'accept',  title: '✅ Accept'  },
      { action: 'decline', title: '❌ Decline' },
    ] : [],
  };

  // For non-call pushes: check if the app is in the foreground (focused window).
  // If so, relay to the main thread as an in-app toast instead of a system notification.
  if (!payload.data || payload.data.type !== 'call') {
    event.waitUntil(
      self.clients.matchAll({ type: 'window', includeUncontrolled: false }).then((clients) => {
        const focusedClient = clients.find((c) => c.focused);
        if (focusedClient) {
          focusedClient.postMessage({ type: 'PUSH_FOREGROUND', payload });
          return;
        }
        const showPromise = self.registration.showNotification(title, options);
        if (payload.data && payload.data.message_id) {
          return showPromise.then(() =>
            fetch('/chat/confirm-delivery', {
              method: 'POST',
              credentials: 'include',
              redirect: 'error', // an auth redirect (302 → login) must fail, not masquerade as success
              headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
              body: JSON.stringify({ message_id: payload.data.message_id }),
            }).catch(() => {}) // delivery is also confirmed server-side by SendPushNotificationJob
          );
        }
        return showPromise;
      })
    );
  } else {
    event.waitUntil(self.registration.showNotification(title, options));
  }
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
      // Ask any controlled page to re-sync the app badge from the server count.
      windowClients.forEach((c) => c.postMessage({ type: 'REFRESH_BADGE' }));
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

// ── SW update: SKIP_WAITING on demand ──────────────────────────────────────
self.addEventListener('message', (event) => {
  if (event.data && event.data.type === 'SKIP_WAITING') {
    self.skipWaiting();
  }
});

// ── Fetch (cache strategy) ──────────────────────────────────────────────────
self.addEventListener('fetch', (event) => {
  let url;
  try { url = new URL(event.request.url); } catch (e) { return; }

  // Skip SW for sensitive/dynamic routes
  if (url.pathname.startsWith('/login') ||
      url.pathname.startsWith('/register') ||
      url.pathname.startsWith('/logout') ||
      url.pathname.startsWith('/auth/') ||
      url.pathname.startsWith('/api/') ||
      url.pathname.startsWith('/chat') ||
      url.pathname.startsWith('/admin') ||
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
  } else if (event.request.mode === 'navigate' && url.pathname === '/' &&
             (url.searchParams.get('source') === 'pwa' || url.searchParams.size === 0)) {
    // Cache the app shell (start_url) on first successful visit — satisfies Lighthouse
    // "start_url is cached by a service worker" check without pre-caching stale HTML.
    event.respondWith(
      fetch(event.request).then((response) => {
        if (response.status === 200) {
          const clone = response.clone();
          caches.open(CACHE_NAME).then((cache) => cache.put(event.request, clone));
        }
        return response;
      }).catch(() => {
        return caches.match(event.request).then((cached) => {
          return cached || caches.match(OFFLINE_URL);
        });
      })
    );
  } else {
    // Network-First for everything else; show offline page on total failure
    event.respondWith(
      fetch(event.request).catch(() => {
        return caches.match(event.request).then((cached) => {
          return cached || caches.match(OFFLINE_URL);
        });
      })
    );
  }
});

// ── Background Sync (Android only — not available on iOS) ───────────────────
self.addEventListener('sync', (event) => {
  if (event.tag === 'nexus-offline-sync') {
    event.waitUntil(
      self.clients.matchAll({ type: 'window' }).then((clients) => {
        clients.forEach((client) => client.postMessage({ type: 'REPLAY_OFFLINE_QUEUE' }));
      })
    );
  }
});

const CACHE_NAME = 'nexus-cache-' + (typeof __BUILD_HASH__ !== 'undefined' ? __BUILD_HASH__ : 'dev');

function urlBase64ToUint8Array(base64String) {
    const padding = '='.repeat((4 - (base64String.length % 4)) % 4);
    const base64  = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
    const raw     = atob(base64);
    return Uint8Array.from([...raw].map(c => c.charCodeAt(0)));
}

// ── E2E helper functions inside Service Worker ─────────────────────────────
function openE2EDatabase() {
  return new Promise((resolve) => {
    const request = self.indexedDB.open("nexus-e2e", 1);
    request.onsuccess = (event) => resolve(event.target.result);
    request.onerror = () => resolve(null);
  });
}

function getStoreItem(db, storeName, key) {
  return new Promise((resolve) => {
    try {
      const tx = db.transaction(storeName, "readonly");
      const store = tx.objectStore(storeName);
      const request = store.get(key);
      request.onsuccess = (event) => resolve(event.target.result || null);
      request.onerror = () => resolve(null);
    } catch (e) {
      resolve(null);
    }
  });
}

function putStoreItem(db, storeName, value) {
  return new Promise((resolve) => {
    try {
      const tx = db.transaction(storeName, "readwrite");
      const store = tx.objectStore(storeName);
      const request = store.put(value);
      request.onsuccess = () => resolve(true);
      request.onerror = () => resolve(false);
    } catch (e) {
      resolve(false);
    }
  });
}

async function importPublicKey(jwk, algorithm, usage) {
  return self.crypto.subtle.importKey(
    "jwk",
    jwk,
    algorithm,
    true,
    usage
  );
}

async function deriveSharedSecret(privateKey, publicKey) {
  const bits = await self.crypto.subtle.deriveBits(
    { name: "ECDH", public: publicKey },
    privateKey,
    256
  );
  const hash = await self.crypto.subtle.digest("SHA-256", bits);
  return self.crypto.subtle.importKey(
    "raw",
    hash,
    { name: "AES-GCM", length: 256 },
    false,
    ["encrypt", "decrypt"]
  );
}

async function importSymmetricKey(keyB64) {
  const raw = Uint8Array.from(atob(keyB64), (c) => c.charCodeAt(0));
  return self.crypto.subtle.importKey(
    "raw",
    raw,
    { name: "AES-GCM", length: 256 },
    false,
    ["encrypt", "decrypt"]
  );
}

async function decryptMessage(key, ciphertextB64, ivB64) {
  const ciphertext = Uint8Array.from(atob(ciphertextB64), (c) => c.charCodeAt(0));
  const iv = Uint8Array.from(atob(ivB64), (c) => c.charCodeAt(0));
  const plaintext = await self.crypto.subtle.decrypt(
    { name: "AES-GCM", iv },
    key,
    ciphertext
  );
  return JSON.parse(new TextDecoder().decode(plaintext));
}

async function fetchPeerKeys(db, userId) {
  try {
    const response = await fetch(`/api/e2e/keys/${userId}?t=${Date.now()}`, {
      headers: { 'Accept': 'application/json' }
    });
    if (!response.ok) return null;
    
    const data = await response.json();
    
    const [ecdhKey, ecdsaKey] = await Promise.all([
      importPublicKey(
        data.ecdh_public_key,
        { name: "ECDH", namedCurve: "P-256" },
        []
      ),
      importPublicKey(
        data.ecdsa_public_key,
        { name: "ECDSA", namedCurve: "P-256" },
        ["verify"]
      )
    ]);
    
    const keys = { ecdh_public_key: ecdhKey, ecdsa_public_key: ecdsaKey };
    
    await putStoreItem(db, 'peer-keys', {
      user_id: String(userId),
      ecdh_public_key: ecdhKey,
      ecdsa_public_key: ecdsaKey,
      fetched_at: Date.now()
    });
    
    return keys;
  } catch (err) {
    console.error('[SW E2E] Failed to fetch peer keys:', err);
    return null;
  }
}

async function fetchAndCacheGroupKeys(db, conversationId, senderId, myPrekeyPrivateKey) {
  try {
    const response = await fetch(`/api/e2e/group-keys/${conversationId}`, {
      headers: { 'Accept': 'application/json' }
    });
    if (!response.ok) return [];
    
    const data = await response.json();
    const encryptedKeys = data.encrypted_keys || [];
    
    const decryptedKeys = [];
    for (const ek of encryptedKeys) {
      if (!ek.sender_id) continue;
      
      const sId = String(ek.sender_id);
      let peerKeys = await getStoreItem(db, 'peer-keys', sId);
      let peerEcdhKey;
      if (peerKeys && peerKeys.ecdh_public_key instanceof CryptoKey) {
        peerEcdhKey = peerKeys.ecdh_public_key;
      } else {
        const freshKeys = await fetchPeerKeys(db, sId);
        if (freshKeys) peerEcdhKey = freshKeys.ecdh_public_key;
      }
      
      if (!peerEcdhKey) continue;
      
      const sharedSecret = await deriveSharedSecret(myPrekeyPrivateKey, peerEcdhKey);
      const decryptedPayload = await decryptMessage(sharedSecret, ek.encrypted_key, ek.iv);
      if (decryptedPayload && decryptedPayload.key) {
        decryptedKeys.push({
          key_id: ek.key_id,
          raw_key: decryptedPayload.key,
          active: true,
          rotated_at: ek.created_at
        });
      }
    }
    
    if (decryptedKeys.length > 0) {
      await putStoreItem(db, 'group-keys', {
        conversation_id: String(conversationId),
        keys: decryptedKeys
      });
    }
    return decryptedKeys;
  } catch (err) {
    console.error('[SW E2E] Failed to fetch and cache group keys:', err);
    return [];
  }
}

async function decryptPushPayload(data) {
  let envelope;
  try {
    envelope = JSON.parse(data.encrypted_content);
  } catch (e) {
    console.error('[SW E2E] Failed to parse encrypted content envelope:', e);
    return null;
  }
  
  if (!envelope || !envelope.ciphertext || !envelope.iv) {
    return null;
  }
  
  const db = await openE2EDatabase();
  if (!db) {
    console.warn('[SW E2E] E2E IndexedDB not available');
    return null;
  }
  
  const prekey = await getStoreItem(db, 'user-keys', 'prekey');
  if (!prekey || !prekey.private_key) {
    console.warn('[SW E2E] No local prekey private key found in IndexedDB');
    return null;
  }
  
  let decryptedText = null;
  
  if (data.is_group) {
    const conversationId = String(data.conversation_id || '');
    let groupKeysRecord = await getStoreItem(db, 'group-keys', conversationId);
    let keys = groupKeysRecord ? (groupKeysRecord.keys || []) : [];
    
    let keyRecord = keys.find(k => k.key_id === envelope.key_id);
    if (!keyRecord) {
      console.log('[SW E2E] Group key not found in IndexedDB cache. Fetching group keys...');
      const fetchedKeys = await fetchAndCacheGroupKeys(db, conversationId, data.sender_id, prekey.private_key);
      keyRecord = fetchedKeys.find(k => k.key_id === envelope.key_id);
    }
    
    if (keyRecord && keyRecord.raw_key) {
      const importedKey = await importSymmetricKey(keyRecord.raw_key);
      const decrypted = await decryptMessage(importedKey, envelope.ciphertext, envelope.iv);
      decryptedText = decrypted.text;
    } else {
      console.warn('[SW E2E] Group key not found even after refetching');
    }
  } else {
    const senderId = String(data.sender_id);
    let peerKeys = await getStoreItem(db, 'peer-keys', senderId);
    let peerEcdhKey;
    
    if (envelope.sender_ecdh_key && envelope.recipient_ecdh_key) {
      try {
        peerEcdhKey = await importPublicKey(
          envelope.sender_ecdh_key,
          { name: "ECDH", namedCurve: "P-256" },
          []
        );
      } catch (err) {
        console.warn('[SW E2E] Failed to import payload public key:', err);
      }
    }

    if (!peerEcdhKey) {
      let keyIdMatches = false;
      if (peerKeys && peerKeys.ecdsa_public_key instanceof CryptoKey && envelope.key_id) {
        try {
          const exported = await self.crypto.subtle.exportKey("jwk", peerKeys.ecdsa_public_key);
          const msgPub = JSON.parse(atob(envelope.key_id));
          if (msgPub && msgPub.x === exported.x && msgPub.y === exported.y) {
            keyIdMatches = true;
          }
        } catch (err) {
          console.warn('[SW E2E] Failed to compare public keys:', err);
        }
      }

      if (peerKeys && peerKeys.ecdh_public_key instanceof CryptoKey && (!envelope.key_id || keyIdMatches)) {
        peerEcdhKey = peerKeys.ecdh_public_key;
      } else {
        console.log('[SW E2E] Peer key mismatch or not found in IndexedDB. Fetching peer keys...');
        const freshKeys = await fetchPeerKeys(db, senderId);
        if (freshKeys) {
          peerEcdhKey = freshKeys.ecdh_public_key;
        }
      }
    }
    
    if (peerEcdhKey) {
      const sharedSecret = await deriveSharedSecret(prekey.private_key, peerEcdhKey);
      const decrypted = await decryptMessage(sharedSecret, envelope.ciphertext, envelope.iv);
      decryptedText = decrypted.text;
    } else {
      console.warn('[SW E2E] Peer ECDH key not found even after refetching');
    }
  }
  
  return decryptedText;
}

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
      { action: 'decline', title: '❌ Decline' },
      { action: 'accept',  title: '✅ Accept'  },
    ] : [],
  };

  // For non-call pushes: check if the app is in the foreground (focused window).
  // If so, relay to the main thread as an in-app toast instead of a system notification.
  if (!payload.data || payload.data.type !== 'call') {
    event.waitUntil(
      self.clients.matchAll({ type: 'window', includeUncontrolled: false }).then(async (clients) => {
        const focusedClient = clients.find((c) => c.focused);
        if (focusedClient) {
          if (payload.data && payload.data.is_e2e_encrypted) {
            try {
              const decryptedText = await decryptPushPayload(payload.data);
              if (decryptedText) {
                payload.body = decryptedText;
                payload.data.decrypted_body = decryptedText;
              }
            } catch (err) {
              console.error('[SW E2E] Foreground decryption failed:', err);
            }
          }
          focusedClient.postMessage({ type: 'PUSH_FOREGROUND', payload });
          return;
        }

        // Decrypt E2E encrypted message if not in foreground
        if (payload.data && payload.data.is_e2e_encrypted) {
          try {
            const decryptedText = await decryptPushPayload(payload.data);
            if (decryptedText) {
              options.body = decryptedText;
            } else {
              options.body = '🔒 Encrypted message';
            }
          } catch (err) {
            console.error('[SW E2E] Background decryption failed:', err);
            options.body = '🔒 Encrypted message';
          }
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
          credentials: 'include',
        }).catch(() => {})
      );
      return;
    }

    if (action === 'accept') {
      // Accept immediately in SW — prevents the 30s CallTimeoutJob from firing
      // before the page finishes loading. CSRF exempt on /call/*/accept.
      event.waitUntil((async () => {
        try {
          await fetch('/call/' + data.callId + '/accept', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'include',
          });
        } catch (e) {}
        // Open the conversation page so WebRTC negotiation can complete
        const windowClients = await self.clients.matchAll({ type: 'window', includeUncontrolled: true });
        for (const client of windowClients) {
          if (client.url.includes(data.url) && 'focus' in client) {
            return client.focus();
          }
        }
        if (self.clients.openWindow) {
          return self.clients.openWindow(data.url || '/');
        }
      })());
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

  // Skip SW for sensitive/dynamic routes and build assets
  if (url.pathname.startsWith('/login') ||
      url.pathname.startsWith('/register') ||
      url.pathname.startsWith('/logout') ||
      url.pathname.startsWith('/auth/') ||
      url.pathname.startsWith('/api/') ||
      url.pathname.startsWith('/chat') ||
      url.pathname.startsWith('/admin') ||
      url.pathname.startsWith('/socket.io/') ||
      url.pathname.startsWith('/build/') ||
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

// ── Push subscription rotated by browser ───────────────────────────────────
// Fires when the browser expires or rotates the push endpoint (e.g. after a
// browser update). Re-subscribe DIRECTLY here — no open page required.
// /api/push/subscribe is CSRF-exempt; session cookie sent via credentials:include.
self.addEventListener('pushsubscriptionchange', (event) => {
  event.waitUntil((async () => {
    try {
      // Fetch public VAPID key (unauthenticated endpoint)
      const res     = await fetch('/api/push/vapid-key');
      const data    = await res.json();
      const vapidKey = data.public_key;
      if (!vapidKey) return;

      // Get a fresh subscription from the browser push service
      const sub = await self.registration.pushManager.subscribe({
        userVisibleOnly:      true,
        applicationServerKey: urlBase64ToUint8Array(vapidKey),
      });

      // Register the new endpoint with the server
      const p256dh = sub.getKey('p256dh');
      const auth   = sub.getKey('auth');
      await fetch('/api/push/subscribe', {
        method:      'POST',
        credentials: 'include',
        headers:     { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          endpoint:         sub.endpoint,
          p256dh:           p256dh ? btoa(String.fromCharCode(...new Uint8Array(p256dh))) : null,
          auth:             auth   ? btoa(String.fromCharCode(...new Uint8Array(auth)))   : null,
          content_encoding: (PushManager.supportedContentEncodings || ['aesgcm'])[0],
        }),
      });
    } catch (e) {
      // If direct re-subscribe fails, fall back to messaging any open page
      const clients = await self.clients.matchAll({ type: 'window', includeUncontrolled: true });
      clients.forEach((client) => client.postMessage({ type: 'PUSH_RESUBSCRIBE' }));
    }
  })());
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

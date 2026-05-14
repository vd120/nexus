const CACHE_NAME = 'nexus-cache-v1';

self.addEventListener('install', (event) => {
  // Minimal installation to satisfy PWA requirements
  self.skipWaiting();
});

self.addEventListener('fetch', (event) => {
  const url = new URL(event.request.url);
  
  // Skip service worker for sensitive routes (Auth, API, etc.)
  // These routes rely on consistent cookie/session handling which SW interception can break
  if (url.pathname.startsWith('/login') || 
      url.pathname.startsWith('/register') || 
      url.pathname.startsWith('/logout') || 
      url.pathname.startsWith('/auth/') || 
      url.pathname.startsWith('/api/') ||
      url.pathname.match(/\.(mp4|webm|ogg|wav|mp3)$/i)) {
    return;
  }

  // Network-first strategy to ensure the app is always up to date
  event.respondWith(
    fetch(event.request).catch(() => {
      return caches.match(event.request);
    })
  );
});

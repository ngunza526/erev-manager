const CACHE_NAME = 'ereve-church-v4';
const MEDIA_CACHE_NAME = 'ereve-church-media-v1';
const ASSETS = ['/manifest.webmanifest'];

self.addEventListener('install', (event) => {
  self.skipWaiting();
  event.waitUntil(caches.open(CACHE_NAME).then((cache) => cache.addAll(ASSETS)));
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys()
      .then((keys) => Promise.all(keys.filter((key) => key !== CACHE_NAME && key !== MEDIA_CACHE_NAME).map((key) => caches.delete(key))))
      .then(() => self.clients.claim()),
  );
});

self.addEventListener('fetch', (event) => {
  if (event.request.method !== 'GET') return;

  if (event.request.mode === 'navigate') {
    event.respondWith(fetch(event.request, { cache: 'no-store' }).catch(() => new Response('Application indisponible hors connexion. Verifiez que le serveur local Laravel est lance sur http://127.0.0.1:8088.', {
      status: 503,
      headers: { 'Content-Type': 'text/plain; charset=utf-8', 'Cache-Control': 'no-store' },
    })));
    return;
  }

  event.respondWith(caches.match(event.request).then((cached) => cached || fetch(event.request)));
});

self.addEventListener('message', (event) => {
  if (event.data?.type !== 'EREVE_CACHE_MEDIA') return;

  const urls = Array.isArray(event.data.urls) ? event.data.urls.filter((url) => typeof url === 'string') : [];
  event.waitUntil(
    caches.open(MEDIA_CACHE_NAME).then((cache) => Promise.all(
      urls.map((url) => cache.add(url).catch(() => null)),
    )),
  );
});

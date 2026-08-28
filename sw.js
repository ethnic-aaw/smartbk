const CACHE_NAME = 'smartbk-v1';
const BASE = self.registration.scope.replace(/\/$/, '');
const ASSETS = [
    `${BASE}/assets/css/style.css`,
    `${BASE}/assets/js/main.js`,
    `${BASE}/manifest.webmanifest`
];

self.addEventListener('install', (event) => {
    event.waitUntil(caches.open(CACHE_NAME).then((cache) => cache.addAll(ASSETS)));
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(caches.keys().then((keys) => Promise.all(keys.filter((key) => key !== CACHE_NAME).map((key) => caches.delete(key)))));
    self.clients.claim();
});

self.addEventListener('fetch', (event) => {
    if (event.request.method !== 'GET') {
        return;
    }
    const url = new URL(event.request.url);
    const isStatic = /\.(css|js|png|jpg|jpeg|gif|svg|ico|woff2?|ttf|eot|webmanifest)$/.test(url.pathname);
    if (!isStatic) {
        return;
    }
    event.respondWith(caches.match(event.request).then((cached) => cached || fetch(event.request).then((response) => {
        const clone = response.clone();
        caches.open(CACHE_NAME).then((cache) => cache.put(event.request, clone));
        return response;
    })));
});

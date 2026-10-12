// Service worker sederhana: aset statis di-cache, halaman selalu diambil dari jaringan
// (data absen harus selalu terbaru), dengan halaman offline sebagai cadangan.
const CACHE = 'absenqr-v23';
const ASET = ['/css/app.css?v=23', '/js/ui.js?v=23', '/vendor/html5-qrcode.min.js', '/vendor/qrcode.js', '/icons/icon-192.png', '/offline.html', '/fonts/dm-sans.woff2', '/fonts/fredoka.woff2', '/fonts/space-mono.woff2'];

self.addEventListener('install', e => {
    e.waitUntil(caches.open(CACHE).then(c => c.addAll(ASET)).then(() => self.skipWaiting()));
});

self.addEventListener('activate', e => {
    e.waitUntil(caches.keys().then(keys => Promise.all(keys.filter(k => k !== CACHE).map(k => caches.delete(k))))
        .then(() => self.clients.claim()));
});

self.addEventListener('fetch', e => {
    const req = e.request;
    if (req.method !== 'GET' || new URL(req.url).origin !== location.origin) return;

    if (req.mode === 'navigate') {
        e.respondWith(fetch(req).catch(() => caches.match('/offline.html')));
        return;
    }
    if (ASET.some(a => req.url.endsWith(a))) {
        e.respondWith(caches.match(req).then(r => r || fetch(req)));
    }
});

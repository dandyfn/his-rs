/*
 * Service Worker "Ini Rumah Sakit"
 * Tugasnya hanya satu: kalau pengguna membuka/pindah halaman saat TIDAK ADA INTERNET,
 * tampilkan halaman /offline.html (bukan halaman dinosaurus bawaan browser).
 * Halaman berisi data pasien TIDAK disimpan di cache (privasi): hanya offline.html,
 * logo, dan favicon.
 */
const CACHE = 'irs-offline-v2';
const BERKAS = ['/offline.html', '/images/logo-irs.png', '/images/favicon.png'];

self.addEventListener('install', (event) => {
    event.waitUntil(caches.open(CACHE).then((c) => c.addAll(BERKAS)).then(() => self.skipWaiting()));
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys()
            .then((nama) => Promise.all(nama.filter((n) => n !== CACHE).map((n) => caches.delete(n))))
            .then(() => self.clients.claim())
    );
});

self.addEventListener('fetch', (event) => {
    const req = event.request;
    if (req.method !== 'GET') return;

    // Pindah halaman: coba internet dulu, kalau gagal tampilkan offline.html
    if (req.mode === 'navigate') {
        event.respondWith(fetch(req).catch(() => caches.match('/offline.html')));
        return;
    }

    // Gambar milik halaman offline: dari internet, kalau gagal dari cache
    const url = new URL(req.url);
    if (url.origin === self.location.origin && BERKAS.includes(url.pathname)) {
        event.respondWith(fetch(req).catch(() => caches.match(url.pathname)));
    }
});

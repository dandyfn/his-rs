{{-- Lapisan "koneksi terputus" + notifikasi "tersambung kembali" + pendaftaran Service Worker --}}
<div id="koneksi-putus" class="koneksi-putus" role="status" aria-live="polite" hidden>
    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" class="koneksi-ikon"><path d="M2 8.5a15 15 0 0 1 20 0M5.5 12a10 10 0 0 1 13 0M9 15.5a5 5 0 0 1 6 0"/><circle cx="12" cy="19" r="1"/><path d="M3 3l18 18"/></svg>
    <strong>Koneksi internet terputus.</strong>
    <span>Data yang Anda isi belum bisa disimpan. Sistem akan tersambung otomatis</span>
    <span class="koneksi-titik" aria-hidden="true"><i></i><i></i><i></i></span>
</div>
<div id="koneksi-pulih" class="koneksi-pulih" role="status" aria-live="polite" hidden>
    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 8.5a15 15 0 0 1 20 0M5.5 12a10 10 0 0 1 13 0M9 15.5a5 5 0 0 1 6 0"/><circle cx="12" cy="19" r="1"/></svg>
    <span><strong>Tersambung kembali.</strong> Anda dapat melanjutkan pekerjaan.</span>
</div>
<script>
(() => {
    const putus = document.getElementById('koneksi-putus');
    const pulih = document.getElementById('koneksi-pulih');
    let timer;

    function offline() {
        pulih.hidden = true;
        putus.hidden = false;
    }
    function online() {
        if (putus.hidden) return;
        putus.hidden = true;
        pulih.hidden = false;
        clearTimeout(timer);
        timer = setTimeout(() => { pulih.hidden = true; }, 3000);
    }

    window.addEventListener('offline', offline);
    window.addEventListener('online', online);
    if (!navigator.onLine) offline();

    // Saat offline, formulir tidak dikirim supaya isian tidak hilang
    document.addEventListener('submit', (e) => {
        if (!navigator.onLine) {
            e.preventDefault();
            offline();
            putus.classList.remove('goyang');
            void putus.offsetWidth;
            putus.classList.add('goyang');
        }
    }, true);

    // Service Worker: menampilkan halaman offline saat pindah halaman tanpa internet
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => navigator.serviceWorker.register('{{ asset('sw.js') }}').catch(() => {}));
    }
})();
</script>

# MediStock RS — Subsistem Manajemen Stok Obat (Laravel 13)

Bagian dari Hospital Information System (HIS) kelompok. Dipindahkan dari versi PHP native
dengan tampilan dan fungsi yang sama.

## Fitur

| Kode | Fitur | Halaman |
|---|---|---|
| F1 | Data obat, transaksi stok masuk/keluar, supplier, pembelian dari supplier | `/obat`, `/supplier`, `/pembelian` |
| F2 | Penjualan obat ke pasien (resep / tanpa resep) + struk | `/penjualan` |
| F3 | Analisis penggunaan & rekomendasi pengadaan (Data-Driven Decision Making) | `/rekomendasi-obat`, ringkasan di `/` |

## Arsitektur (MVC + service layer)

```
Browser ──> routes/web.php ──> Controller ──> Service (logika bisnis) ──> Model (Eloquent) ──> PostgreSQL (Aiven)
                                   │
                                   └──> View (Blade) ──> HTML
```

| Lapisan | Lokasi |
|---|---|
| Route | `routes/web.php` |
| Controller | `app/Http/Controllers/` (`ObatController`, `SupplierController`, `PembelianController`, `PenjualanController`, `RekomendasiController`, `DashboardController`, `Auth/LoginController`) |
| Service | `app/Services/StokObatService.php` (perubahan stok dalam transaksi DB + kunci baris), `app/Services/RekomendasiPengadaanService.php` (rumus DDDM) |
| Model | `app/Models/` (`Obat`, `Supplier`, `Pembelian`, `Penjualan`, `PenjualanDetail`, `TransaksiStok`, `Pasien`, `User`) |
| View | `resources/views/` |
| Database | `database/migrations/`, `database/seeders/` |
| Pengujian | `tests/Feature/MediStockTest.php` |

## Rumus rekomendasi (F3)

- d = total obat keluar dalam periode ÷ jumlah hari
- SS (stok pengaman) = d × `stok_pengaman_hari`
- ROP (titik pesan ulang) = (d × `lead_time_hari`) + SS
- Q (saran jumlah pesan) = d × (`lead_time_hari` + `interval_pesan_hari`) + SS − stok

## Perintah penting

```bash
php artisan migrate        # membuat/memperbarui tabel
php artisan db:seed        # 8 akun demo (lihat tabel Akun Demo) + data demo
php artisan serve          # menjalankan di http://localhost:8000
php artisan test           # menjalankan pengujian otomatis
```

## Deploy

`Dockerfile` dan `docker/start.sh` dipakai oleh Render. Tidak perlu dijalankan di laptop.

## Akun Demo (satu akun per peran)

Password demo = username + `123` (admin: `admin123`). Ganti sebelum dipakai sungguhan.

| Username | Peran | Subsistem 1 Pendaftaran | Subsistem 2 Antrean | Subsistem 3 Obat |
|---|---|---|---|---|
| `admin` | Administrator Sistem | Semua | Semua + kelola poli | Semua + kelola pengguna |
| `manajemen` | Manajemen RS | Lihat | Lihat, rekomendasi, laporan | Lihat, analisis, rekomendasi, laporan |
| `pendaftaran` | Petugas Pendaftaran | Semua | Daftarkan antrean | - |
| `antrean` | Petugas Antrean / Poli | Lihat pasien | Panggil, layani, riwayat, laporan | - |
| `dokter` | Tenaga Medis | Lihat identitas | Lihat antrean | - |
| `apoteker` | Apoteker | Lihat pasien | - | Obat & harga, supplier, pesanan, retur, analisis, laporan |
| `apotek` | Petugas Apotek | Lihat pasien | - | Penjualan, struk, retur, lihat stok (tidak bisa ubah harga) |
| `gudang` | Staf Gudang | - | - | Terima barang (batch + ED), stok per batch, laporan |

Hak akses diatur di `config/hak_akses.php` dan didaftarkan sebagai Gate di `AppServiceProvider`.

## Pemetaan Fitur MediStock ke SRS

| SRS | Fitur | Menu |
|---|---|---|
| FR-01, US-01 | Tambah, edit, nonaktifkan obat; nama obat tidak boleh kembar | Obat → Stok Obat |
| FR-02 | Kelola supplier | Obat → Supplier |
| FR-03, FR-04, UC-04, UC-06, US-02 | Pesanan → penerimaan barang (batch + ED wajib), diterima sebagian | Obat → Pesanan & Penerimaan |
| FR-05, FR-07, US-04 | Stok per batch, peringatan kedaluwarsa ≤ 90 hari (`BATAS_KEDALUWARSA_HARI`) | Obat → Batch & Kedaluwarsa |
| FR-08, FR-09, FR-10, US-05–07 | Penjualan resep/non-resep, FEFO, struk dengan batch | Obat → Penjualan Obat |
| FR-11, UC-09, US-12 | Retur obat rusak/kedaluwarsa dengan alasan | Obat → Retur Obat |
| FR-12 | Riwayat pembelian obat per pasien | Obat → Penjualan Obat |
| FR-13, UC-10, US-08 | Grafik tren pemakaian per bulan, filter periode & obat | Obat → Analisis Pemakaian |
| FR-14, UC-11, US-09 (DDDM) | Rekomendasi jumlah & waktu pesan (ROP, Q), data < 1 bulan ditandai | Obat → Rekomendasi Pengadaan |
| FR-15, UC-12, US-10 | Laporan stok/penjualan/pengadaan/retur: cetak/PDF dan Excel (.xlsx) | Obat → Laporan Obat |
| FR-16, NFR-03, NFR-04, US-11 | Login, password hash, menu & akses sesuai peran | semua halaman |
| NFR-06 | Transaksi database + penguncian baris; gagal = stok tidak berubah | `StokObatService` |
| NFR-09 | Riwayat transaksi stok & perubahan harga (siapa, kapan, apa) | Obat → Stok Obat |

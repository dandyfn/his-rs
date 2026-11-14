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
Browser ──> routes/web.php ──> Controller ──> Service (logika bisnis) ──> Model (Eloquent) ──> MySQL (Aiven)
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
php artisan db:seed        # akun admin (admin / admin123) + data demo
php artisan serve          # menjalankan di http://localhost:8000
php artisan test           # menjalankan pengujian otomatis
```

## Deploy

`Dockerfile` dan `docker/start.sh` dipakai oleh Render. Tidak perlu dijalankan di laptop.

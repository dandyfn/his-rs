# Panduan Tim — Mengerjakan HIS "Ini Rumah Sakit"

Panduan ini untuk anggota kelompok yang melanjutkan pengerjaan web HIS (Laravel).

## 1. Yang perlu terpasang di laptop

| Alat | Versi | Cek dengan |
|---|---|---|
| PHP | 8.3 atau 8.4 (Laragon) | `php -v` |
| Ekstensi PHP | `pdo_sqlite`, `sqlite3`, `pdo_pgsql`, `zip`, `mbstring`, `openssl` | `php -m` |
| Composer | 2.x | `composer -V` |
| Git | 2.x | `git --version` |
| Editor | VS Code | |

Di Laragon, ekstensi PHP untuk terminal diatur di file `php.ini` yang ditunjukkan `php --ini`
(bukan lewat centang di menu Laragon).

## 2. Menjalankan proyek pertama kali

Jalankan di terminal, di folder tempat menyimpan proyek (misalnya `D:\Projek-HIS`):

```
git clone https://github.com/dandyfn/his-rs.git
cd his-rs
composer install
copy .env.example .env
php artisan key:generate
type nul > database\database.sqlite
php artisan migrate:fresh --seed
php artisan test
php artisan serve
```

Buka `http://127.0.0.1:8000`. Semua tes harus lulus (`passed`).

**Akun demo** (password = username + `123`, admin = `admin123`):
`admin`, `manajemen`, `pendaftaran`, `antrean`, `dokter`, `apoteker`, `apotek`, `gudang`.

> ⚠️ Saat mengembangkan, **pakai SQLite lokal** (bawaan `.env.example`).
> Database Aiven dipakai web demo di Render. Menjalankan `migrate:fresh` ke Aiven
> akan menghapus semua data demo dan akun.

## 3. Alur kerja Git (supaya tidak saling menimpa)

Web di Render **otomatis deploy setiap ada perubahan di branch `main`**.
Jadi jangan bekerja langsung di `main`.

1. Ambil versi terbaru dulu:
   ```
   git checkout main
   git pull
   ```
2. Buat branch sendiri sesuai fitur:
   ```
   git checkout -b fahmi/pendaftaran-dddm
   ```
   Contoh nama: `fahmi/...`, `puspa/...`, `dandy/...`.
3. Kerjakan, lalu sebelum commit pastikan `php artisan test` lulus.
4. Simpan dan kirim branch:
   ```
   git add .
   git commit -m "Pendaftaran: skor kemiripan duplikat"
   git push -u origin fahmi/pendaftaran-dddm
   ```
5. Di GitHub, buat **Pull Request** ke `main`, minta satu anggota lain mengecek, lalu **Merge**.
   Setelah merge, Render deploy otomatis.

## 4. Pembagian folder per subsistem

| Subsistem | Penanggung jawab | File utama |
|---|---|---|
| Pendaftaran & Identitas | Fahmi | `app/Http/Controllers/PasienController.php`, `IdentitasPasienController.php`, `app/Services/IdentitasPasienService.php`, `resources/views/pendaftaran/` |
| Antrean (SIRATA) | Puspa | `AntreanController.php`, `PoliController.php`, `RiwayatAntreanController.php`, `RekomendasiAntreanController.php`, `LaporanAntreanController.php`, `app/Services/AntreanService.php`, `RekomendasiAntreanService.php`, `resources/views/antrean/` |
| Obat (MediStock RS) | Dandy | `ObatController.php`, `BatchObatController.php`, `PembelianController.php`, `PenjualanController.php`, `ReturObatController.php`, `AnalisisObatController.php`, `RekomendasiController.php`, `LaporanObatController.php`, `app/Services/StokObatService.php` dkk., `resources/views/obat/`, `batch/`, `pembelian/`, `penjualan/`, `retur/`, `analisis/`, `rekomendasi/`, `laporan-obat/` |

**File bersama** — ubah dengan hati-hati dan kabari kelompok sebelum mengubahnya,
karena paling sering bentrok (*merge conflict*):

- `routes/web.php` — daftar alamat halaman dan hak aksesnya
- `config/hak_akses.php` — peran dan izin
- `resources/views/layouts/app.blade.php` — header dan menu
- `public/css/medistock.css`, `public/css/style.css` — tampilan
- `database/seeders/DatabaseSeeder.php` — akun demo
- `database/migrations/` — **jangan mengubah migrasi lama**; buat file migrasi baru
  (`php artisan make:migration nama_perubahan`)

## 5. Arsitektur singkat

```
Route (routes/web.php, + middleware can:izin)
  → Controller (validasi input, memanggil service)
    → Service (logika bisnis + DDDM, transaksi database)
      → Model (Eloquent) → database
  → View (Blade, resources/views)
```

- Menambah menu baru: tambahkan route + izinnya di `routes/web.php`, izin di
  `config/hak_akses.php`, dan item menu (dengan ikon) di `layouts/app.blade.php`.
- Ikon menu: komponen `<x-ikon nama="..."/>` di `resources/views/components/ikon.blade.php`.
- Tes otomatis ada di `tests/Feature/`. Tambahkan tes untuk fitur baru.

## 6. Yang tidak boleh

- Jangan meng-commit file `.env` (berisi password database). Sudah dikecualikan oleh `.gitignore`.
- Jangan push langsung ke `main` tanpa Pull Request.
- Jangan menjalankan `migrate:fresh` dengan `.env` yang mengarah ke Aiven.

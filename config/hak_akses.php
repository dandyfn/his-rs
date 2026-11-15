<?php

/*
|--------------------------------------------------------------------------
| Peran (aktor) dan hak akses HIS RS
|--------------------------------------------------------------------------
| Disusun dari tabel aktor gabungan SRS Pendaftaran (Fahmi), SIRATA (Puspa),
| dan MediStock RS (Dandy). Setiap izin didaftarkan sebagai Gate di
| AppServiceProvider, lalu dipakai di route (middleware can:...), menu, dan view.
*/

return [

    'peran' => [
        'admin' => 'Administrator Sistem',
        'manajemen' => 'Manajemen RS',
        'pendaftaran' => 'Petugas Pendaftaran',
        'antrean' => 'Petugas Antrean / Poli',
        'dokter' => 'Tenaga Medis',
        'apoteker' => 'Apoteker',
        'apotek' => 'Petugas Apotek',
        'gudang' => 'Staf Gudang',
    ],

    // izin => peran yang boleh. Admin selalu boleh semua izin.
    'izin' => [
        // Subsistem 1: Pendaftaran & Identitas
        'pasien.lihat' => ['manajemen', 'pendaftaran', 'antrean', 'dokter', 'apoteker', 'apotek'],
        'pasien.kelola' => ['pendaftaran'],
        'identitas.lihat' => ['manajemen', 'pendaftaran', 'dokter'],

        // Subsistem 2: Antrean (SIRATA)
        'antrean.lihat' => ['manajemen', 'pendaftaran', 'antrean', 'dokter'],
        'antrean.daftar' => ['pendaftaran', 'antrean'],
        'antrean.layani' => ['antrean'],
        'antrean.analisis' => ['manajemen', 'antrean'],
        'poli.lihat' => ['manajemen', 'pendaftaran', 'antrean', 'dokter'],

        // Subsistem 3: MediStock RS
        'obat.lihat' => ['manajemen', 'apoteker', 'apotek', 'gudang'],
        'obat.kelola' => ['apoteker'],                    // NFR-04: harga hanya apoteker/admin
        'supplier.lihat' => ['manajemen', 'apoteker', 'gudang'],
        'supplier.kelola' => ['apoteker'],
        'pengadaan.lihat' => ['manajemen', 'apoteker', 'gudang'],
        'pengadaan.pesan' => ['apoteker'],                // UC-04
        'pengadaan.terima' => ['apoteker', 'gudang'],     // UC-06
        'penjualan.lihat' => ['manajemen', 'apoteker', 'apotek'],
        'penjualan.buat' => ['apotek'],                   // UC-07
        'retur' => ['apoteker', 'apotek'],                // UC-09
        'analisis.obat' => ['manajemen', 'apoteker'],     // UC-10, UC-11
        'laporan.obat' => ['manajemen', 'apoteker', 'gudang'], // UC-12

        // Bersama
        'pengguna.kelola' => [],
    ],

    // FR-07: batas hari peringatan kedaluwarsa (dapat diatur lewat .env)
    'batas_kedaluwarsa_hari' => (int) env('BATAS_KEDALUWARSA_HARI', 90),
];

<?php

use App\Http\Controllers\AnalisisObatController;
use App\Http\Controllers\AntreanController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\BatchObatController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\IdentitasPasienController;
use App\Http\Controllers\LaporanAntreanController;
use App\Http\Controllers\LaporanObatController;
use App\Http\Controllers\ObatController;
use App\Http\Controllers\PasienController;
use App\Http\Controllers\PembelianController;
use App\Http\Controllers\PenggunaController;
use App\Http\Controllers\PenjualanController;
use App\Http\Controllers\PoliController;
use App\Http\Controllers\RekomendasiAntreanController;
use App\Http\Controllers\RekomendasiController;
use App\Http\Controllers\ReturObatController;
use App\Http\Controllers\RiwayatAntreanController;
use App\Http\Controllers\SupplierController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:30,1');
});

Route::middleware('auth')->group(function () {
    Route::get('/', DashboardController::class)->name('dashboard');
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

    // ===== Subsistem 1: Pendaftaran & Identitas Pasien =====
    Route::get('/pasien', [PasienController::class, 'index'])->name('pasien.index')->middleware('can:pasien.lihat');
    Route::middleware('can:pasien.kelola')->group(function () {
        Route::post('/pasien', [PasienController::class, 'store'])->name('pasien.store');
        Route::put('/pasien/{pasien}', [PasienController::class, 'update'])->name('pasien.update');
        Route::post('/identitas-pasien', [IdentitasPasienController::class, 'store'])->name('identitas.store');
        Route::put('/identitas-pasien/{identitas}', [IdentitasPasienController::class, 'update'])->name('identitas.update');
        Route::post('/identitas-pasien/{identitas}/tindakan', [IdentitasPasienController::class, 'tindakan'])->name('identitas.tindakan');
        Route::post('/identitas-pasien/{identitas}/jadikan-pasien', [IdentitasPasienController::class, 'jadikanPasien'])->name('identitas.jadikan-pasien');
    });
    Route::get('/identitas-pasien', [IdentitasPasienController::class, 'index'])->name('identitas.index')->middleware('can:identitas.lihat');

    // ===== Subsistem 2: Antrean Rawat Jalan (SIRATA) =====
    Route::get('/antrean', [AntreanController::class, 'index'])->name('antrean.index')->middleware('can:antrean.lihat');
    Route::post('/antrean', [AntreanController::class, 'store'])->name('antrean.store')->middleware('can:antrean.daftar');
    Route::post('/antrean/{antrean}/status', [AntreanController::class, 'ubahStatus'])->name('antrean.status')->middleware('can:antrean.layani');

    Route::get('/poli', [PoliController::class, 'index'])->name('poli.index')->middleware('can:poli.lihat');
    Route::middleware('can:admin')->group(function () {
        Route::post('/poli', [PoliController::class, 'store'])->name('poli.store');
        Route::put('/poli/{poli}', [PoliController::class, 'update'])->name('poli.update');
        Route::patch('/poli/{poli}/status', [PoliController::class, 'toggle'])->name('poli.toggle');
    });

    Route::middleware('can:antrean.analisis')->group(function () {
        Route::get('/riwayat-antrean', [RiwayatAntreanController::class, 'index'])->name('riwayat-antrean.index');
        Route::get('/rekomendasi-antrean', [RekomendasiAntreanController::class, 'index'])->name('rekomendasi-antrean.index');
        Route::get('/laporan-antrean', [LaporanAntreanController::class, 'index'])->name('laporan-antrean.index');
        Route::get('/laporan-antrean/ekspor', [LaporanAntreanController::class, 'ekspor'])->name('laporan-antrean.ekspor');
    });

    // ===== Subsistem 3: Manajemen Stok Obat (MediStock RS) =====
    Route::get('/obat', [ObatController::class, 'index'])->name('obat.index')->middleware('can:obat.lihat');
    Route::get('/stok-batch', [BatchObatController::class, 'index'])->name('batch.index')->middleware('can:obat.lihat');
    Route::middleware('can:obat.kelola')->group(function () {
        Route::post('/obat', [ObatController::class, 'store'])->name('obat.store');
        Route::put('/obat/{obat}', [ObatController::class, 'update'])->name('obat.update');
        Route::patch('/obat/{obat}/status', [ObatController::class, 'toggle'])->name('obat.toggle');
        Route::post('/obat/transaksi', [ObatController::class, 'transaksi'])->name('obat.transaksi');
    });

    Route::get('/supplier', [SupplierController::class, 'index'])->name('supplier.index')->middleware('can:supplier.lihat');
    Route::middleware('can:supplier.kelola')->group(function () {
        Route::post('/supplier', [SupplierController::class, 'store'])->name('supplier.store');
        Route::patch('/supplier/{supplier}/status', [SupplierController::class, 'toggle'])->name('supplier.toggle');
    });

    Route::get('/pembelian', [PembelianController::class, 'index'])->name('pembelian.index')->middleware('can:pengadaan.lihat');
    Route::post('/pembelian', [PembelianController::class, 'store'])->name('pembelian.store')->middleware('can:pengadaan.pesan');
    Route::patch('/pembelian/{pembelian}/batal', [PembelianController::class, 'batal'])->name('pembelian.batal')->middleware('can:pengadaan.pesan');
    Route::post('/pembelian/{pembelian}/terima', [PembelianController::class, 'terima'])->name('pembelian.terima')->middleware('can:pengadaan.terima');

    Route::get('/penjualan', [PenjualanController::class, 'index'])->name('penjualan.index')->middleware('can:penjualan.lihat');
    Route::post('/penjualan', [PenjualanController::class, 'store'])->name('penjualan.store')->middleware('can:penjualan.buat');
    Route::get('/penjualan/{penjualan}/struk', [PenjualanController::class, 'struk'])->name('penjualan.struk')->middleware('can:penjualan.lihat');

    Route::middleware('can:retur')->group(function () {
        Route::get('/retur-obat', [ReturObatController::class, 'index'])->name('retur.index');
        Route::post('/retur-obat', [ReturObatController::class, 'store'])->name('retur.store');
    });

    Route::middleware('can:analisis.obat')->group(function () {
        Route::get('/analisis-obat', [AnalisisObatController::class, 'index'])->name('analisis.index');
        Route::get('/rekomendasi-obat', [RekomendasiController::class, 'index'])->name('rekomendasi.index');
    });
    Route::middleware('can:laporan.obat')->group(function () {
        Route::get('/laporan-obat', [LaporanObatController::class, 'index'])->name('laporan-obat.index');
        Route::get('/laporan-obat/ekspor', [LaporanObatController::class, 'ekspor'])->name('laporan-obat.ekspor');
    });

    // ===== Bersama: manajemen pengguna (admin) =====
    Route::middleware('can:pengguna.kelola')->group(function () {
        Route::get('/pengguna', [PenggunaController::class, 'index'])->name('pengguna.index');
        Route::post('/pengguna', [PenggunaController::class, 'store'])->name('pengguna.store');
        Route::patch('/pengguna/{user}/status', [PenggunaController::class, 'toggle'])->name('pengguna.toggle');
    });
});

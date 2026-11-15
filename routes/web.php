<?php

use App\Http\Controllers\AntreanController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\IdentitasPasienController;
use App\Http\Controllers\LaporanAntreanController;
use App\Http\Controllers\ObatController;
use App\Http\Controllers\PasienController;
use App\Http\Controllers\PembelianController;
use App\Http\Controllers\PenggunaController;
use App\Http\Controllers\PenjualanController;
use App\Http\Controllers\PoliController;
use App\Http\Controllers\RekomendasiAntreanController;
use App\Http\Controllers\RekomendasiController;
use App\Http\Controllers\RiwayatAntreanController;
use App\Http\Controllers\SupplierController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:10,1');
});

Route::middleware('auth')->group(function () {
    Route::get('/', DashboardController::class)->name('dashboard');
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

    // ===== Subsistem 1: Pendaftaran & Identitas Pasien =====
    Route::get('/pasien', [PasienController::class, 'index'])->name('pasien.index');
    Route::post('/pasien', [PasienController::class, 'store'])->name('pasien.store');
    Route::put('/pasien/{pasien}', [PasienController::class, 'update'])->name('pasien.update');

    Route::get('/identitas-pasien', [IdentitasPasienController::class, 'index'])->name('identitas.index');
    Route::post('/identitas-pasien', [IdentitasPasienController::class, 'store'])->name('identitas.store');
    Route::put('/identitas-pasien/{identitas}', [IdentitasPasienController::class, 'update'])->name('identitas.update');
    Route::post('/identitas-pasien/{identitas}/tindakan', [IdentitasPasienController::class, 'tindakan'])->name('identitas.tindakan');
    Route::post('/identitas-pasien/{identitas}/jadikan-pasien', [IdentitasPasienController::class, 'jadikanPasien'])->name('identitas.jadikan-pasien');

    // ===== Subsistem 2: Manajemen Stok Obat (MediStock RS) =====
    Route::get('/obat', [ObatController::class, 'index'])->name('obat.index');
    Route::post('/obat', [ObatController::class, 'store'])->name('obat.store');
    Route::post('/obat/transaksi', [ObatController::class, 'transaksi'])->name('obat.transaksi');

    Route::get('/supplier', [SupplierController::class, 'index'])->name('supplier.index');
    Route::post('/supplier', [SupplierController::class, 'store'])->name('supplier.store');
    Route::patch('/supplier/{supplier}/status', [SupplierController::class, 'toggle'])->name('supplier.toggle');

    Route::get('/pembelian', [PembelianController::class, 'index'])->name('pembelian.index');
    Route::post('/pembelian', [PembelianController::class, 'store'])->name('pembelian.store');

    Route::get('/penjualan', [PenjualanController::class, 'index'])->name('penjualan.index');
    Route::post('/penjualan', [PenjualanController::class, 'store'])->name('penjualan.store');
    Route::get('/penjualan/{penjualan}/struk', [PenjualanController::class, 'struk'])->name('penjualan.struk');

    Route::get('/rekomendasi-obat', [RekomendasiController::class, 'index'])->name('rekomendasi.index');

    // ===== Subsistem 3: Antrean Rawat Jalan (SIRATA) =====
    Route::get('/antrean', [AntreanController::class, 'index'])->name('antrean.index');
    Route::post('/antrean', [AntreanController::class, 'store'])->name('antrean.store');
    Route::post('/antrean/{antrean}/status', [AntreanController::class, 'ubahStatus'])->name('antrean.status');

    Route::get('/poli', [PoliController::class, 'index'])->name('poli.index');
    Route::middleware('can:admin')->group(function () {
        Route::post('/poli', [PoliController::class, 'store'])->name('poli.store');
        Route::put('/poli/{poli}', [PoliController::class, 'update'])->name('poli.update');
        Route::patch('/poli/{poli}/status', [PoliController::class, 'toggle'])->name('poli.toggle');
    });

    Route::get('/riwayat-antrean', [RiwayatAntreanController::class, 'index'])->name('riwayat-antrean.index');
    Route::get('/rekomendasi-antrean', [RekomendasiAntreanController::class, 'index'])->name('rekomendasi-antrean.index');
    Route::get('/laporan-antrean', [LaporanAntreanController::class, 'index'])->name('laporan-antrean.index');
    Route::get('/laporan-antrean/ekspor', [LaporanAntreanController::class, 'ekspor'])->name('laporan-antrean.ekspor');

    // ===== Bersama: manajemen pengguna (admin) =====
    Route::middleware('can:admin')->group(function () {
        Route::get('/pengguna', [PenggunaController::class, 'index'])->name('pengguna.index');
        Route::post('/pengguna', [PenggunaController::class, 'store'])->name('pengguna.store');
        Route::patch('/pengguna/{user}/status', [PenggunaController::class, 'toggle'])->name('pengguna.toggle');
    });
});

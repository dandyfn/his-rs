<?php

namespace App\Services;

use App\Models\Obat;
use App\Models\Pembelian;
use App\Models\Penjualan;
use App\Models\TransaksiStok;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Lapisan logika bisnis (business logic layer) untuk perubahan stok obat.
 * Semua perubahan stok dilakukan di dalam transaksi database dan mengunci
 * baris obat (SELECT ... FOR UPDATE) supaya stok tidak bisa minus walaupun
 * dua petugas menyimpan transaksi bersamaan.
 */
class StokObatService
{
    /** Transaksi stok manual (masuk / keluar) dari halaman Stok Obat. */
    public function catatTransaksiManual(int $obatId, string $jenis, int $jumlah, ?string $keterangan, ?int $userId): void
    {
        DB::transaction(function () use ($obatId, $jenis, $jumlah, $keterangan, $userId) {
            $obat = Obat::lockForUpdate()->findOrFail($obatId);

            if ($jenis === 'keluar' && $jumlah > $obat->stok) {
                throw ValidationException::withMessages([
                    'jumlah' => "Stok tidak mencukupi. Stok tersedia: {$obat->stok}",
                ]);
            }

            $obat->stok += $jenis === 'masuk' ? $jumlah : -$jumlah;
            $obat->save();

            TransaksiStok::create([
                'obat_id' => $obat->id,
                'jenis' => $jenis,
                'jumlah' => $jumlah,
                'keterangan' => $keterangan,
                'sumber' => 'manual',
                'user_id' => $userId,
            ]);
        });
    }

    /** Pembelian dari supplier: stok bertambah dan tercatat sebagai stok masuk. */
    public function prosesPembelian(array $data, ?int $userId): Pembelian
    {
        return DB::transaction(function () use ($data, $userId) {
            $obat = Obat::lockForUpdate()->findOrFail($data['obat_id']);

            $pembelian = Pembelian::create([
                'nomor_pembelian' => 'PB-' . now()->format('YmdHis') . '-' . random_int(100, 999),
                'supplier_id' => $data['supplier_id'],
                'obat_id' => $obat->id,
                'jumlah' => $data['jumlah'],
                'harga_satuan' => $data['harga_satuan'],
                'total_harga' => round($data['jumlah'] * $data['harga_satuan'], 2),
                'tanggal_pembelian' => $data['tanggal_pembelian'],
                'keterangan' => $data['keterangan'] ?? null,
                'user_id' => $userId,
            ]);

            $obat->increment('stok', $data['jumlah']);

            TransaksiStok::create([
                'obat_id' => $obat->id,
                'jenis' => 'masuk',
                'jumlah' => $data['jumlah'],
                'keterangan' => 'Pembelian ' . $pembelian->nomor_pembelian,
                'sumber' => 'pembelian',
                'referensi_id' => $pembelian->id,
                'user_id' => $userId,
            ]);

            return $pembelian;
        });
    }

    /**
     * Penjualan obat ke pasien (F2).
     * $item = [obat_id => jumlah, ...] (obat yang sama sudah dijumlahkan).
     * Setiap item tercatat sebagai transaksi "keluar" = data pemakaian untuk DDDM.
     */
    public function prosesPenjualan(array $header, array $item, ?int $userId): Penjualan
    {
        return DB::transaction(function () use ($header, $item, $userId) {
            $rincian = [];
            $total = 0;

            foreach ($item as $obatId => $jumlah) {
                $obat = Obat::lockForUpdate()->find($obatId);
                if (! $obat) {
                    throw ValidationException::withMessages(['item' => 'Obat tidak ditemukan.']);
                }
                if ($jumlah > $obat->stok) {
                    throw ValidationException::withMessages([
                        'item' => "Stok {$obat->nama_obat} tidak mencukupi. Tersedia: {$obat->stok}",
                    ]);
                }
                $harga = (float) $obat->harga_jual;
                $subtotal = $harga * $jumlah;
                $total += $subtotal;
                $rincian[] = [$obat, $jumlah, $harga, $subtotal];
            }

            $penjualan = Penjualan::create([
                'nomor_penjualan' => 'PJ-' . now()->format('Ymd-His') . '-' . random_int(100, 999),
                'pasien_id' => $header['pasien_id'],
                'nama_pembeli' => $header['nama_pembeli'],
                'jenis' => $header['jenis'],
                'nomor_resep' => $header['jenis'] === 'resep' ? $header['nomor_resep'] : null,
                'total' => $total,
                'user_id' => $userId,
            ]);

            foreach ($rincian as [$obat, $jumlah, $harga, $subtotal]) {
                $penjualan->detail()->create([
                    'obat_id' => $obat->id,
                    'jumlah' => $jumlah,
                    'harga_satuan' => $harga,
                    'subtotal' => $subtotal,
                ]);

                $obat->decrement('stok', $jumlah);

                TransaksiStok::create([
                    'obat_id' => $obat->id,
                    'jenis' => 'keluar',
                    'jumlah' => $jumlah,
                    'keterangan' => 'Penjualan ' . $penjualan->nomor_penjualan,
                    'sumber' => 'penjualan',
                    'referensi_id' => $penjualan->id,
                    'user_id' => $userId,
                ]);
            }

            return $penjualan;
        });
    }
}

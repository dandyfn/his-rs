<?php

namespace App\Services;

use App\Models\BatchObat;
use App\Models\Obat;
use App\Models\Pembelian;
use App\Models\Penerimaan;
use App\Models\Penjualan;
use App\Models\ReturObat;
use App\Models\RiwayatHarga;
use App\Models\TransaksiStok;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Lapisan logika bisnis untuk semua perubahan stok obat MediStock RS.
 *
 * Aturan yang dijaga di sini:
 * - Stok disimpan per batch (FR-05). obat.stok selalu = total sisa semua batch.
 * - Barang masuk wajib punya nomor batch dan tanggal kedaluwarsa (FR-04, US-02).
 * - Penjualan memakai FEFO: batch yang paling cepat kedaluwarsa keluar lebih dulu,
 *   batch yang sudah kedaluwarsa tidak boleh dijual (FR-09).
 * - Semua perubahan berjalan dalam transaksi database dengan penguncian baris
 *   (SELECT ... FOR UPDATE), jadi transaksi gagal tidak mengurangi stok (NFR-06).
 * - Setiap perubahan stok dan harga dicatat: siapa, kapan, apa (NFR-09).
 */
class StokObatService
{
    // ---------------------------------------------------------------- Obat

    /** FR-01: ubah data obat. Perubahan harga dicatat di riwayat harga (NFR-09). */
    public function perbaruiObat(Obat $obat, array $data, ?int $userId): void
    {
        DB::transaction(function () use ($obat, $data, $userId) {
            $obat = Obat::lockForUpdate()->findOrFail($obat->id);
            $hargaLama = (float) $obat->harga_jual;
            $obat->update($data);

            if (abs($hargaLama - (float) $obat->harga_jual) > 0.001) {
                RiwayatHarga::create([
                    'obat_id' => $obat->id, 'harga_lama' => $hargaLama,
                    'harga_baru' => $obat->harga_jual, 'user_id' => $userId,
                ]);
            }
        });
    }

    /**
     * Penyesuaian stok manual oleh apoteker (stock opname).
     * Masuk: wajib nomor batch + tanggal kedaluwarsa. Keluar: dari batch yang dipilih.
     * Penyesuaian tidak dihitung sebagai pemakaian pada analisis DDDM.
     */
    public function penyesuaianStok(array $data, ?int $userId): void
    {
        DB::transaction(function () use ($data, $userId) {
            $obat = Obat::lockForUpdate()->findOrFail($data['obat_id']);
            $keterangan = 'Penyesuaian stok' . (! empty($data['keterangan']) ? ': ' . $data['keterangan'] : '');

            if ($data['jenis'] === 'masuk') {
                $this->tambahKeBatch($obat, $data['nomor_batch'], $data['tanggal_kedaluwarsa'], $data['jumlah'],
                    null, 'penyesuaian', $keterangan, null, $userId);

                return;
            }

            $batch = BatchObat::lockForUpdate()->where('obat_id', $obat->id)->find($data['batch_id'] ?? 0);
            if (! $batch) {
                throw ValidationException::withMessages(['batch_id' => 'Pilih batch obat yang akan dikurangi.']);
            }
            $this->kurangiDariBatch($obat, $batch, $data['jumlah'], 'penyesuaian', $keterangan, null, $userId);
        });
    }

    // ---------------------------------------------------------- Pengadaan

    /** UC-04: buat pesanan pengadaan. Stok belum bertambah sampai barang diterima. */
    public function buatPesanan(array $data, ?int $userId): Pembelian
    {
        $obat = Obat::findOrFail($data['obat_id']);
        if (! $obat->aktif) {
            throw ValidationException::withMessages(['obat_id' => 'Obat nonaktif tidak dapat dipesan.']);
        }

        return Pembelian::create([
            'nomor_pembelian' => $this->nomorUnik('PO', Pembelian::class, 'nomor_pembelian'),
            'supplier_id' => $data['supplier_id'],
            'obat_id' => $obat->id,
            'jumlah' => $data['jumlah'],
            'jumlah_diterima' => 0,
            'harga_satuan' => $data['harga_satuan'],
            'total_harga' => round($data['jumlah'] * $data['harga_satuan'], 2),
            'status' => 'dipesan',
            'tanggal_pembelian' => $data['tanggal_pembelian'],
            'keterangan' => $data['keterangan'] ?? null,
            'user_id' => $userId,
        ]);
    }

    /**
     * UC-06: terima barang dari supplier. Stok bertambah pada batch baru.
     * Jika jumlah diterima kurang dari pesanan, status menjadi "sebagian".
     */
    public function terimaBarang(Pembelian $pesanan, array $data, ?int $userId): Penerimaan
    {
        return DB::transaction(function () use ($pesanan, $data, $userId) {
            $pesanan = Pembelian::lockForUpdate()->findOrFail($pesanan->id);
            if (! $pesanan->bisaDiterima()) {
                throw ValidationException::withMessages(['jumlah' => 'Pesanan ini sudah selesai atau dibatalkan.']);
            }
            if ($data['jumlah'] > $pesanan->sisaBelumDiterima()) {
                throw ValidationException::withMessages(['jumlah' =>
                    "Jumlah diterima melebihi sisa pesanan. Sisa yang belum diterima: {$pesanan->sisaBelumDiterima()}."]);
            }

            $obat = Obat::lockForUpdate()->findOrFail($pesanan->obat_id);

            $penerimaan = Penerimaan::create([
                'nomor_penerimaan' => $this->nomorUnik('GR', Penerimaan::class, 'nomor_penerimaan'),
                'pembelian_id' => $pesanan->id,
                'jumlah' => $data['jumlah'],
                'nomor_batch' => strtoupper(trim($data['nomor_batch'])),
                'tanggal_kedaluwarsa' => $data['tanggal_kedaluwarsa'],
                'tanggal_terima' => $data['tanggal_terima'],
                'keterangan' => $data['keterangan'] ?? null,
                'user_id' => $userId,
            ]);

            $this->tambahKeBatch($obat, $data['nomor_batch'], $data['tanggal_kedaluwarsa'], $data['jumlah'],
                $penerimaan->id, 'pembelian', 'Penerimaan ' . $penerimaan->nomor_penerimaan
                . ' (pesanan ' . $pesanan->nomor_pembelian . ')', $pesanan->id, $userId);

            $pesanan->jumlah_diterima += $data['jumlah'];
            $pesanan->status = $pesanan->jumlah_diterima >= $pesanan->jumlah ? 'diterima' : 'sebagian';
            $pesanan->save();

            return $penerimaan;
        });
    }

    public function batalkanPesanan(Pembelian $pesanan): void
    {
        DB::transaction(function () use ($pesanan) {
            $pesanan = Pembelian::lockForUpdate()->findOrFail($pesanan->id);
            if ($pesanan->status !== 'dipesan') {
                throw ValidationException::withMessages(['pesanan' =>
                    'Hanya pesanan yang belum ada barang diterima yang dapat dibatalkan.']);
            }
            $pesanan->update(['status' => 'batal']);
        });
    }

    // ---------------------------------------------------------- Penjualan

    /**
     * UC-07: penjualan obat ke pasien dengan FEFO.
     * $item = [obat_id => jumlah, ...] (obat yang sama sudah dijumlahkan).
     * Satu obat bisa diambil dari beberapa batch; setiap batch jadi satu baris detail.
     */
    public function prosesPenjualan(array $header, array $item, ?int $userId): Penjualan
    {
        return DB::transaction(function () use ($header, $item, $userId) {
            $rencana = [];
            $total = 0;

            foreach ($item as $obatId => $jumlah) {
                $obat = Obat::lockForUpdate()->find($obatId);
                if (! $obat || ! $obat->aktif) {
                    throw ValidationException::withMessages(['item' => 'Obat tidak ditemukan atau sudah nonaktif.']);
                }

                $daftarBatch = BatchObat::lockForUpdate()->where('obat_id', $obat->id)->layakJual()->get();
                $tersedia = (int) $daftarBatch->sum('sisa');
                if ($jumlah > $tersedia) {
                    throw ValidationException::withMessages(['item' =>
                        "Stok tidak cukup untuk {$obat->nama_obat}. Tersedia (belum kedaluwarsa): {$tersedia} {$obat->satuan}."]);
                }

                // FEFO: ambil dari batch dengan tanggal kedaluwarsa paling dekat.
                $sisaDiminta = $jumlah;
                foreach ($daftarBatch as $batch) {
                    if ($sisaDiminta === 0) {
                        break;
                    }
                    $ambil = min($sisaDiminta, $batch->sisa);
                    $rencana[] = [$obat, $batch, $ambil];
                    $sisaDiminta -= $ambil;
                }
                $total += (float) $obat->harga_jual * $jumlah;
            }

            $penjualan = Penjualan::create([
                'nomor_penjualan' => $this->nomorUnik('PJ', Penjualan::class, 'nomor_penjualan'),
                'pasien_id' => $header['pasien_id'],
                'nama_pembeli' => $header['nama_pembeli'],
                'jenis' => $header['jenis'],
                'nomor_resep' => $header['jenis'] === 'resep' ? $header['nomor_resep'] : null,
                'total' => $total,
                'user_id' => $userId,
            ]);

            foreach ($rencana as [$obat, $batch, $ambil]) {
                $harga = (float) $obat->harga_jual;
                $penjualan->detail()->create([
                    'obat_id' => $obat->id, 'batch_id' => $batch->id, 'jumlah' => $ambil,
                    'harga_satuan' => $harga, 'subtotal' => $harga * $ambil,
                ]);
                $this->kurangiDariBatch($obat, $batch, $ambil, 'penjualan',
                    'Penjualan ' . $penjualan->nomor_penjualan, $penjualan->id, $userId);
            }

            return $penjualan;
        });
    }

    // -------------------------------------------------------------- Retur

    /** UC-09 / US-12: retur obat rusak/kedaluwarsa. Stok batch berkurang, alasan tercatat. */
    public function prosesRetur(array $data, ?int $userId): ReturObat
    {
        return DB::transaction(function () use ($data, $userId) {
            $batch = BatchObat::lockForUpdate()->findOrFail($data['batch_id']);
            $obat = Obat::lockForUpdate()->findOrFail($batch->obat_id);

            $retur = ReturObat::create([
                'nomor_retur' => $this->nomorUnik('RT', ReturObat::class, 'nomor_retur'),
                'obat_id' => $obat->id,
                'batch_id' => $batch->id,
                'jumlah' => $data['jumlah'],
                'alasan' => $data['alasan'],
                'tujuan' => $data['tujuan'],
                'keterangan' => $data['keterangan'] ?? null,
                'user_id' => $userId,
            ]);

            $this->kurangiDariBatch($obat, $batch, $data['jumlah'], 'retur',
                'Retur ' . $retur->nomor_retur . ' (' . ReturObat::ALASAN[$data['alasan']] . ')', $retur->id, $userId);

            return $retur;
        });
    }

    // ------------------------------------------------------------ Internal

    /**
     * Nomor transaksi unik, contoh PJ-261115-7K3QX. Dicek ke database supaya
     * dua transaksi di detik yang sama tidak mendapat nomor kembar.
     */
    private function nomorUnik(string $awalan, string $model, string $kolom): string
    {
        do {
            $nomor = $awalan . '-' . now()->format('ymd') . '-' . strtoupper(\Illuminate\Support\Str::random(5));
        } while ($model::where($kolom, $nomor)->exists());

        return $nomor;
    }

    private function tambahKeBatch(Obat $obat, string $nomorBatch, string $kedaluwarsa, int $jumlah,
        ?int $penerimaanId, string $sumber, string $keterangan, ?int $referensiId, ?int $userId): BatchObat
    {
        $nomorBatch = strtoupper(trim($nomorBatch));
        $batch = BatchObat::lockForUpdate()->where('obat_id', $obat->id)->where('nomor_batch', $nomorBatch)->first();

        if ($batch) {
            // Batch yang sama harus punya tanggal kedaluwarsa yang sama.
            if ($batch->tanggal_kedaluwarsa->toDateString() !== $kedaluwarsa) {
                throw ValidationException::withMessages(['nomor_batch' =>
                    "Batch {$nomorBatch} sudah tercatat dengan tanggal kedaluwarsa "
                    . $batch->tanggal_kedaluwarsa->format('d/m/Y') . '. Periksa kembali nomor batch.']);
            }
            $batch->jumlah_awal += $jumlah;
            $batch->sisa += $jumlah;
            $batch->save();
        } else {
            $batch = BatchObat::create([
                'obat_id' => $obat->id, 'nomor_batch' => $nomorBatch, 'tanggal_kedaluwarsa' => $kedaluwarsa,
                'jumlah_awal' => $jumlah, 'sisa' => $jumlah, 'penerimaan_id' => $penerimaanId,
            ]);
        }

        $obat->increment('stok', $jumlah);
        $this->catat($obat, $batch, 'masuk', $jumlah, $sumber, $keterangan, $referensiId, $userId);

        return $batch;
    }

    private function kurangiDariBatch(Obat $obat, BatchObat $batch, int $jumlah, string $sumber,
        string $keterangan, ?int $referensiId, ?int $userId): void
    {
        if ($jumlah > $batch->sisa) {
            throw ValidationException::withMessages(['jumlah' =>
                "Jumlah melebihi sisa batch {$batch->nomor_batch}. Sisa: {$batch->sisa}."]);
        }
        $batch->decrement('sisa', $jumlah);
        $obat->decrement('stok', $jumlah);
        $this->catat($obat, $batch, 'keluar', $jumlah, $sumber, $keterangan, $referensiId, $userId);
    }

    private function catat(Obat $obat, BatchObat $batch, string $jenis, int $jumlah, string $sumber,
        string $keterangan, ?int $referensiId, ?int $userId): void
    {
        TransaksiStok::create([
            'obat_id' => $obat->id, 'batch_id' => $batch->id, 'jenis' => $jenis, 'jumlah' => $jumlah,
            'keterangan' => $keterangan, 'sumber' => $sumber, 'referensi_id' => $referensiId, 'user_id' => $userId,
        ]);
    }
}

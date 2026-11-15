<?php

namespace App\Services;

use App\Models\BatchObat;
use App\Models\Obat;
use App\Models\Pembelian;
use App\Models\PenjualanDetail;
use App\Models\ReturObat;
use Illuminate\Support\Carbon;

/**
 * FR-15 / UC-12 / US-10: laporan stok, penjualan, pengadaan, dan retur.
 * Menghasilkan struktur yang sama untuk tampilan layar, cetak/PDF, dan unduhan Excel.
 */
class LaporanObatService
{
    public const JENIS = [
        'stok' => 'Laporan Stok Obat',
        'penjualan' => 'Laporan Penjualan Obat',
        'pengadaan' => 'Laporan Pengadaan Obat',
        'retur' => 'Laporan Retur Obat',
    ];

    /** @return array{judul:string, header:array, baris:array, ringkasan:array} */
    public function buat(string $jenis, Carbon $awal, Carbon $akhir): array
    {
        return match ($jenis) {
            'penjualan' => $this->penjualan($awal, $akhir),
            'pengadaan' => $this->pengadaan($awal, $akhir),
            'retur' => $this->retur($awal, $akhir),
            default => $this->stok(),
        };
    }

    private function stok(): array
    {
        $batas = config('hak_akses.batas_kedaluwarsa_hari');
        $baris = [];
        $nilai = 0;
        foreach (Obat::with(['batch' => fn ($q) => $q->where('sisa', '>', 0)])->orderBy('nama_obat')->get() as $o) {
            $layak = $o->batch->filter(fn ($b) => ! $b->sudahKedaluwarsa())->sum('sisa');
            $kedaluwarsa = $o->stok - $layak;
            $terdekat = $o->batch->reject->sudahKedaluwarsa()->sortBy('tanggal_kedaluwarsa')->first();
            $nilai += $o->stok * (float) $o->harga_jual;
            $baris[] = [
                $o->kode_obat, $o->nama_obat, $o->satuan, $o->stok, $layak, $kedaluwarsa, $o->stok_minimum,
                $o->batch->count(), $terdekat?->tanggal_kedaluwarsa->format('d/m/Y') ?? '-',
                (float) $o->harga_jual, $o->stok * (float) $o->harga_jual,
                ! $o->aktif ? 'Nonaktif' : ($o->perluDitinjau() ? 'Di bawah minimum' : 'Cukup'),
            ];
        }

        return [
            'judul' => self::JENIS['stok'] . ' per ' . now()->format('d/m/Y H:i'),
            'header' => ['Kode', 'Nama Obat', 'Satuan', 'Stok Total', 'Layak Jual', 'Kedaluwarsa', 'Stok Minimum',
                'Jumlah Batch', 'ED Terdekat', 'Harga Jual', 'Nilai Persediaan', 'Status'],
            'baris' => $baris,
            'ringkasan' => [
                'Jenis obat' => count($baris),
                'Nilai persediaan' => 'Rp ' . number_format($nilai, 0, ',', '.'),
                "Batch kedaluwarsa / ≤ {$batas} hari" => BatchObat::where('sisa', '>', 0)
                    ->whereDate('tanggal_kedaluwarsa', '<=', today()->addDays($batas))->count(),
            ],
        ];
    }

    private function penjualan(Carbon $awal, Carbon $akhir): array
    {
        $detail = PenjualanDetail::with(['obat', 'batch', 'penjualan'])
            ->whereHas('penjualan', fn ($q) => $q->whereBetween('created_at', [$awal->copy()->startOfDay(), $akhir->copy()->endOfDay()]))
            ->get()->sortBy(fn ($d) => $d->penjualan->created_at);

        $baris = $detail->map(fn ($d) => [
            $d->penjualan->created_at->format('d/m/Y H:i'), $d->penjualan->nomor_penjualan, $d->penjualan->nama_pembeli,
            $d->penjualan->jenis === 'resep' ? 'Resep' : 'Tanpa resep', $d->obat->nama_obat, $d->batch?->nomor_batch ?? '-',
            $d->jumlah, (float) $d->harga_satuan, (float) $d->subtotal,
        ])->values()->all();

        return [
            'judul' => self::JENIS['penjualan'] . ' ' . $awal->format('d/m/Y') . ' s.d. ' . $akhir->format('d/m/Y'),
            'header' => ['Waktu', 'No. Penjualan', 'Pembeli', 'Jenis', 'Obat', 'Batch', 'Jumlah', 'Harga', 'Subtotal'],
            'baris' => $baris,
            'ringkasan' => [
                'Transaksi' => $detail->pluck('penjualan_id')->unique()->count(),
                'Unit terjual' => (int) $detail->sum('jumlah'),
                'Total penjualan' => 'Rp ' . number_format($detail->sum('subtotal'), 0, ',', '.'),
            ],
        ];
    }

    private function pengadaan(Carbon $awal, Carbon $akhir): array
    {
        $daftar = Pembelian::with(['supplier', 'obat', 'penerimaan'])
            ->whereBetween('tanggal_pembelian', [$awal->toDateString(), $akhir->toDateString()])
            ->orderBy('tanggal_pembelian')->orderBy('id')->get();

        $baris = $daftar->map(fn ($p) => [
            $p->tanggal_pembelian->format('d/m/Y'), $p->nomor_pembelian, $p->supplier->nama_supplier, $p->obat->nama_obat,
            $p->jumlah, $p->jumlah_diterima, (float) $p->harga_satuan, (float) $p->total_harga, $p->labelStatus()[0],
            $p->penerimaan->pluck('nomor_batch')->implode(', ') ?: '-',
        ])->all();

        $aktif = $daftar->where('status', '<>', 'batal');

        return [
            'judul' => self::JENIS['pengadaan'] . ' ' . $awal->format('d/m/Y') . ' s.d. ' . $akhir->format('d/m/Y'),
            'header' => ['Tgl Pesan', 'No. Pesanan', 'Supplier', 'Obat', 'Dipesan', 'Diterima', 'Harga Satuan', 'Total', 'Status', 'Batch Diterima'],
            'baris' => $baris,
            'ringkasan' => [
                'Pesanan' => $aktif->count(),
                'Menunggu barang' => $aktif->whereIn('status', ['dipesan', 'sebagian'])->count(),
                'Nilai pengadaan' => 'Rp ' . number_format($aktif->sum('total_harga'), 0, ',', '.'),
            ],
        ];
    }

    private function retur(Carbon $awal, Carbon $akhir): array
    {
        $daftar = ReturObat::with(['obat', 'batch', 'petugas'])
            ->whereBetween('created_at', [$awal->copy()->startOfDay(), $akhir->copy()->endOfDay()])
            ->orderBy('id')->get();

        $baris = $daftar->map(fn ($r) => [
            $r->created_at->format('d/m/Y H:i'), $r->nomor_retur, $r->obat->nama_obat, $r->batch->nomor_batch,
            $r->batch->tanggal_kedaluwarsa->format('d/m/Y'), $r->jumlah, ReturObat::ALASAN[$r->alasan] ?? $r->alasan,
            ReturObat::TUJUAN[$r->tujuan] ?? $r->tujuan, $r->petugas->name ?? '-',
        ])->all();

        return [
            'judul' => self::JENIS['retur'] . ' ' . $awal->format('d/m/Y') . ' s.d. ' . $akhir->format('d/m/Y'),
            'header' => ['Waktu', 'No. Retur', 'Obat', 'Batch', 'ED', 'Jumlah', 'Alasan', 'Tujuan', 'Petugas'],
            'baris' => $baris,
            'ringkasan' => ['Retur' => $daftar->count(), 'Unit diretur' => (int) $daftar->sum('jumlah')],
        ];
    }
}

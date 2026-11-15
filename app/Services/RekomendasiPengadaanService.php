<?php

namespace App\Services;

use App\Models\Obat;
use App\Models\TransaksiStok;
use Illuminate\Support\Collection;

/**
 * F3 - Analisis Penggunaan dan Rekomendasi Pengadaan Obat
 * (fungsi Data-Driven Decision Making MediStock RS).
 *
 * Rumus (SRS MediStock RS, Bagian 2.4.3):
 *   d   = total obat keluar dalam periode / jumlah hari periode
 *   SS  = d x stok_pengaman_hari                (stok pengaman)
 *   ROP = (d x L) + SS                          (titik pesan ulang)
 *   Q   = d x (L + T) + SS - S                  (saran jumlah pesan)
 *   L = lead_time_hari, T = interval_pesan_hari, S = stok saat ini
 *
 * Status: pesan  -> S <= ROP
 *         pantau -> ROP < S <= 1,5 x ROP
 *         aman   -> S > 1,5 x ROP
 *         nodata -> belum ada pemakaian dalam periode
 *         kurang -> data pemakaian kurang dari 1 bulan (UC-11 alur alternatif)
 *
 * Pemakaian = stok keluar karena penjualan (bukan retur / penyesuaian stok).
 * Obat nonaktif tidak dianalisis.
 */
class RekomendasiPengadaanService
{
    public const PERIODE_VALID = [30, 60, 90];

    public const MINIMAL_HARI_DATA = 30;

    private const URUTAN = ['pesan' => 0, 'pantau' => 1, 'aman' => 2, 'kurang' => 3, 'nodata' => 4];

    public function analisis(int $periode = 90): Collection
    {
        $periode = in_array($periode, self::PERIODE_VALID, true) ? $periode : 90;
        $sejak = now()->subDays($periode);

        $pemakaian = fn ($q) => $q->where('jenis', 'keluar')->whereIn('sumber', TransaksiStok::SUMBER_PEMAKAIAN);

        $daftar = Obat::aktif()
            // S = stok layak jual (batch belum kedaluwarsa), karena stok kedaluwarsa tidak bisa dipakai
            ->withSum(['batch as stok_layak' => fn ($q) => $q->where('sisa', '>', 0)
                ->whereDate('tanggal_kedaluwarsa', '>=', today())], 'sisa')
            ->withSum(['transaksiStok as total_keluar' => fn ($q) => $pemakaian($q)
                ->where('created_at', '>=', $sejak)], 'jumlah')
            ->withMin(['transaksiStok as pakai_pertama' => $pemakaian], 'created_at')
            ->orderBy('nama_obat')
            ->get();

        return $daftar
            ->map(function (Obat $obat) use ($periode) {
                $hasil = $this->hitung($obat, (int) $obat->total_keluar, $periode);
                $pertama = $obat->pakai_pertama ? \Illuminate\Support\Carbon::parse($obat->pakai_pertama) : null;
                if ($hasil['status'] !== 'nodata' && $pertama && $pertama->gt(now()->subDays(self::MINIMAL_HARI_DATA))) {
                    $hasil['status'] = 'kurang';
                    $hasil['saran'] = 0;
                    $hasil['hari_data'] = (int) $pertama->diffInDays(now());
                }

                return $hasil;
            })
            ->sortBy(fn ($r) => [self::URUTAN[$r['status']], $r['habis_hari'] ?? PHP_INT_MAX])
            ->values();
    }

    /** Perhitungan untuk satu obat; dipisah supaya mudah diuji (unit test). */
    public function hitung(Obat $obat, int $totalKeluar, int $periode): array
    {
        $S = isset($obat->stok_layak) ? (int) $obat->stok_layak : $obat->stok;
        $L = $obat->lead_time_hari;
        $T = $obat->interval_pesan_hari;
        $d = $totalKeluar / $periode;
        $SS = $d * $obat->stok_pengaman_hari;
        $ROP = ($d * $L) + $SS;

        if ($d <= 0) {
            $status = 'nodata';
            $habis = null;
            $Q = 0;
        } else {
            $habis = (int) floor($S / $d);
            if ($S <= $ROP) {
                $status = 'pesan';
                $Q = (int) max(0, ceil($d * ($L + $T) + $SS - $S));
            } else {
                $status = $S <= $ROP * 1.5 ? 'pantau' : 'aman';
                $Q = 0;
            }
        }

        return [
            'obat' => $obat,
            'stok' => $S,
            'pakai_per_hari' => $d,
            'titik_pesan' => (int) ceil($ROP),
            'habis_hari' => $habis,
            'status' => $status,
            'saran' => $Q,
        ];
    }
}

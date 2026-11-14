<?php

namespace App\Services;

use App\Models\Obat;
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
 */
class RekomendasiPengadaanService
{
    public const PERIODE_VALID = [30, 60, 90];

    private const URUTAN = ['pesan' => 0, 'pantau' => 1, 'aman' => 2, 'nodata' => 3];

    public function analisis(int $periode = 90): Collection
    {
        $periode = in_array($periode, self::PERIODE_VALID, true) ? $periode : 90;
        $sejak = now()->subDays($periode);

        $daftar = Obat::query()
            ->withSum(['transaksiStok as total_keluar' => fn ($q) => $q
                ->where('jenis', 'keluar')
                ->where('created_at', '>=', $sejak)], 'jumlah')
            ->orderBy('nama_obat')
            ->get();

        return $daftar
            ->map(fn (Obat $obat) => $this->hitung($obat, (int) $obat->total_keluar, $periode))
            ->sortBy(fn ($r) => [self::URUTAN[$r['status']], $r['habis_hari'] ?? PHP_INT_MAX])
            ->values();
    }

    /** Perhitungan untuk satu obat; dipisah supaya mudah diuji (unit test). */
    public function hitung(Obat $obat, int $totalKeluar, int $periode): array
    {
        $S = $obat->stok;
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
            'pakai_per_hari' => $d,
            'titik_pesan' => (int) ceil($ROP),
            'habis_hari' => $habis,
            'status' => $status,
            'saran' => $Q,
        ];
    }
}

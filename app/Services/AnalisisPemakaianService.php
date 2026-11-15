<?php

namespace App\Services;

use App\Models\Obat;
use App\Models\TransaksiStok;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * FR-13 / UC-10: analisis tren pemakaian obat per periode.
 * Pengelompokan per bulan dilakukan di PHP supaya sama hasilnya di PostgreSQL, MySQL, dan SQLite.
 */
class AnalisisPemakaianService
{
    public const PILIHAN_BULAN = [3, 6, 12];

    /** Pemakaian per bulan: [['bulan' => Carbon, 'label' => 'Nov 2026', 'jumlah' => int], ...] */
    public function perBulan(int $bulan, ?int $obatId = null): Collection
    {
        $awal = now()->startOfMonth()->subMonths($bulan - 1);

        $baris = $this->pemakaian($awal, $obatId)->get(['created_at', 'jumlah'])
            ->groupBy(fn ($t) => $t->created_at->format('Y-m'))
            ->map->sum('jumlah');

        return collect(range(0, $bulan - 1))->map(function ($i) use ($awal, $baris) {
            $b = $awal->copy()->addMonths($i);

            return ['bulan' => $b, 'label' => $b->translatedFormat('M Y'), 'jumlah' => (int) ($baris[$b->format('Y-m')] ?? 0)];
        });
    }

    /** Obat paling banyak dipakai dalam periode. */
    public function obatTerbanyak(int $bulan, int $batas = 5): Collection
    {
        $awal = now()->startOfMonth()->subMonths($bulan - 1);
        $total = $this->pemakaian($awal)->get(['obat_id', 'jumlah'])->groupBy('obat_id')->map->sum('jumlah')
            ->sortDesc()->take($batas);
        $obat = Obat::whereIn('id', $total->keys())->get()->keyBy('id');

        return $total->map(fn ($jumlah, $id) => ['obat' => $obat[$id], 'jumlah' => (int) $jumlah])->values();
    }

    /** Tabel ringkas per obat: total, rata-rata per bulan, bulan tertinggi. */
    public function ringkasanPerObat(int $bulan): Collection
    {
        $awal = now()->startOfMonth()->subMonths($bulan - 1);
        $data = $this->pemakaian($awal)->get(['obat_id', 'created_at', 'jumlah'])->groupBy('obat_id');

        return Obat::orderBy('nama_obat')->get()->map(function (Obat $o) use ($data, $bulan) {
            $rows = $data[$o->id] ?? collect();
            $perBulan = $rows->groupBy(fn ($t) => $t->created_at->format('Y-m'))->map->sum('jumlah');
            $puncak = $perBulan->sortDesc()->keys()->first();

            return [
                'obat' => $o,
                'total' => (int) $rows->sum('jumlah'),
                'rata_bulan' => $rows->sum('jumlah') / $bulan,
                'puncak' => $puncak ? Carbon::createFromFormat('Y-m', $puncak)->translatedFormat('M Y') : '-',
            ];
        })->sortByDesc('total')->values();
    }

    private function pemakaian(Carbon $sejak, ?int $obatId = null)
    {
        return TransaksiStok::where('jenis', 'keluar')
            ->whereIn('sumber', TransaksiStok::SUMBER_PEMAKAIAN)
            ->where('created_at', '>=', $sejak)
            ->when($obatId, fn ($q) => $q->where('obat_id', $obatId));
    }
}

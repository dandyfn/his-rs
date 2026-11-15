<?php

namespace App\Http\Controllers;

use App\Models\Obat;
use App\Services\AnalisisPemakaianService;
use Illuminate\Http\Request;

/** FR-13 / UC-10 / US-08: grafik dan tabel tren pemakaian obat. */
class AnalisisObatController extends Controller
{
    public function index(Request $request, AnalisisPemakaianService $analisis)
    {
        $bulan = (int) $request->query('bulan', 6);
        $bulan = in_array($bulan, AnalisisPemakaianService::PILIHAN_BULAN, true) ? $bulan : 6;
        $obatId = (int) $request->query('obat_id') ?: null;

        $perBulan = $analisis->perBulan($bulan, $obatId);
        // Tren: bulan lengkap terakhir dibanding bulan sebelumnya (bulan berjalan belum lengkap).
        $n = $perBulan->count();
        $akhir = $perBulan[$n - 2] ?? null;
        $sebelum = $perBulan[$n - 3] ?? null;
        $tren = $akhir && $sebelum && $sebelum['jumlah'] > 0
            ? ['persen' => ($akhir['jumlah'] - $sebelum['jumlah']) / $sebelum['jumlah'] * 100,
                'akhir' => $akhir['label'], 'sebelum' => $sebelum['label']]
            : null;

        return view('analisis.index', [
            'bulan' => $bulan,
            'obatId' => $obatId,
            'obatDipilih' => $obatId ? Obat::find($obatId) : null,
            'daftarObat' => Obat::orderBy('nama_obat')->get(),
            'perBulan' => $perBulan,
            'total' => $perBulan->sum('jumlah'),
            'rataBulan' => $perBulan->avg('jumlah'),
            'tren' => $tren,
            'terbanyak' => $analisis->obatTerbanyak($bulan),
            'ringkasan' => $obatId ? collect() : $analisis->ringkasanPerObat($bulan),
        ]);
    }
}

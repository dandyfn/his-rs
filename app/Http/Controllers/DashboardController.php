<?php

namespace App\Http\Controllers;

use App\Models\Antrean;
use App\Models\IdentitasPasien;
use App\Models\Pasien;
use App\Models\Penjualan;
use App\Services\RekomendasiAntreanService;
use App\Services\RekomendasiPengadaanService;

/**
 * Dashboard gabungan HIS: ringkasan dan rekomendasi dari ketiga subsistem.
 */
class DashboardController extends Controller
{
    public function __invoke(RekomendasiPengadaanService $rekomObat, RekomendasiAntreanService $rekomAntrean)
    {
        $antreanHariIni = Antrean::with(['pasien', 'poli'])->whereDate('tanggal_kunjungan', today())->orderBy('id')->get();
        $hasilObat = $rekomObat->analisis(90);

        return view('dashboard', [
            'totalPasien' => Pasien::count(),
            'identitasBelumVerif' => IdentitasPasien::where('status_verifikasi', '<>', 'terverifikasi')->count(),
            'antrean' => ['total' => $antreanHariIni->count()] + collect(Antrean::STATUS)
                ->mapWithKeys(fn ($s) => [$s => $antreanHariIni->where('status', $s)->count()])->all(),
            'antreanHariIni' => $antreanHariIni->whereIn('status', ['menunggu', 'dipanggil', 'dilayani'])->take(15),
            'rekomAntrean' => $rekomAntrean->analisisHariIni()->where('total', '>', 0)->take(6),
            'obatPerluDipesan' => $hasilObat->where('status', 'pesan')->count(),
            'obatMendesak' => $hasilObat->where('status', 'pesan')->take(3),
            'omzetHariIni' => (float) Penjualan::whereDate('created_at', today())->sum('total'),
        ]);
    }
}

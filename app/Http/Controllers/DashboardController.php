<?php

namespace App\Http\Controllers;

use App\Models\Obat;
use App\Models\Penjualan;
use App\Services\RekomendasiPengadaanService;

class DashboardController extends Controller
{
    public function __invoke(RekomendasiPengadaanService $rekomendasi)
    {
        $hasil = $rekomendasi->analisis(90);

        return view('dashboard', [
            'jumlahObat' => Obat::count(),
            'perluDipesan' => $hasil->where('status', 'pesan')->count(),
            'penjualanHariIni' => Penjualan::whereDate('created_at', today())->count(),
            'omzetHariIni' => (float) Penjualan::whereDate('created_at', today())->sum('total'),
            'mendesak' => $hasil->where('status', 'pesan')->take(5),
        ]);
    }
}

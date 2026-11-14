<?php

namespace App\Http\Controllers;

use App\Services\RekomendasiPengadaanService;
use Illuminate\Http\Request;

class RekomendasiController extends Controller
{
    public function index(Request $request, RekomendasiPengadaanService $service)
    {
        $periode = (int) $request->query('periode', 90);
        if (! in_array($periode, RekomendasiPengadaanService::PERIODE_VALID, true)) {
            $periode = 90;
        }

        $hasil = $service->analisis($periode);

        return view('rekomendasi.index', [
            'periode' => $periode,
            'hasil' => $hasil,
            'jumlah' => $hasil->countBy('status'),
        ]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Services\RekomendasiAntreanService;

class RekomendasiAntreanController extends Controller
{
    public function index(RekomendasiAntreanService $service)
    {
        return view('antrean.rekomendasi', ['hasil' => $service->analisisHariIni()]);
    }
}

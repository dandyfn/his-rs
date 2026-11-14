<?php

namespace App\Http\Controllers;

use App\Models\RiwayatStatus;

class RiwayatAntreanController extends Controller
{
    public function index()
    {
        return view('antrean.riwayat', [
            'daftar' => RiwayatStatus::with(['antrean.pasien', 'antrean.poli', 'petugas'])
                ->latest('id')->limit(200)->get(),
        ]);
    }
}

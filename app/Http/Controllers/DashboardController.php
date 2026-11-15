<?php

namespace App\Http\Controllers;

use App\Models\Antrean;
use App\Models\BatchObat;
use App\Models\IdentitasPasien;
use App\Models\Pasien;
use App\Models\Pembelian;
use App\Models\Penjualan;
use App\Services\RekomendasiAntreanService;
use App\Services\RekomendasiPengadaanService;
use Illuminate\Http\Request;

/**
 * Dashboard gabungan HIS: ringkasan dan rekomendasi dari ketiga subsistem.
 * Bagian yang tampil mengikuti peran pengguna (US-11).
 */
class DashboardController extends Controller
{
    public function __invoke(Request $request, RekomendasiPengadaanService $rekomObat, RekomendasiAntreanService $rekomAntrean)
    {
        $user = $request->user();
        $data = ['lihatPendaftaran' => $user->can('pasien.lihat'), 'lihatAntrean' => $user->can('antrean.lihat'),
            'lihatObat' => $user->can('obat.lihat')];

        if ($data['lihatPendaftaran']) {
            $data['totalPasien'] = Pasien::count();
            $data['identitasBelumVerif'] = IdentitasPasien::whereNull('pasien_id')
                ->where('status_verifikasi', '<>', 'terverifikasi')->count();
        }

        if ($data['lihatAntrean']) {
            $antreanHariIni = Antrean::with(['pasien', 'poli'])->whereDate('tanggal_kunjungan', today())->orderBy('id')->get();
            $data['antrean'] = ['total' => $antreanHariIni->count()] + collect(Antrean::STATUS)
                ->mapWithKeys(fn ($s) => [$s => $antreanHariIni->where('status', $s)->count()])->all();
            $data['antreanHariIni'] = $antreanHariIni->whereIn('status', ['menunggu', 'dipanggil', 'dilayani'])->take(15);
            $data['rekomAntrean'] = $rekomAntrean->analisisHariIni()->where('total', '>', 0)->take(6);
        }

        if ($data['lihatObat']) {
            $hasilObat = $rekomObat->analisis(90);
            $batas = config('hak_akses.batas_kedaluwarsa_hari');
            $data += [
                'obatPerluDipesan' => $hasilObat->where('status', 'pesan')->count(),
                'obatMendesak' => $hasilObat->where('status', 'pesan')->take(3),
                'omzetHariIni' => (float) Penjualan::whereDate('created_at', today())->sum('total'),
                'batchKedaluwarsa' => BatchObat::with('obat')->where('sisa', '>', 0)
                    ->whereDate('tanggal_kedaluwarsa', '<=', today()->addDays($batas))
                    ->orderBy('tanggal_kedaluwarsa')->get(),
                'pesananMenunggu' => Pembelian::whereIn('status', ['dipesan', 'sebagian'])->count(),
                'batas' => $batas,
            ];
        }

        return view('dashboard', $data);
    }
}

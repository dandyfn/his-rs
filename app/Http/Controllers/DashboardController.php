<?php

namespace App\Http\Controllers;

use App\Models\Antrean;
use App\Models\BatchObat;
use App\Services\RekomendasiAntreanService;
use App\Services\RekomendasiPengadaanService;
use App\Support\Relasi;
use Illuminate\Support\Facades\DB;
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

        // Angka-angka ringkasan diambil dalam satu query (hemat bolak-balik ke database awan).
        $angka = DB::selectOne("SELECT
            (SELECT COUNT(*) FROM pasien) AS pasien,
            (SELECT COUNT(*) FROM identitas_pasien_detail WHERE pasien_id IS NULL AND status_verifikasi <> 'terverifikasi') AS identitas,
            (SELECT COUNT(*) FROM pembelian_obat WHERE status IN ('dipesan','sebagian')) AS pesanan,
            (SELECT COALESCE(SUM(total), 0) FROM penjualan_obat WHERE created_at >= ? AND created_at < ?) AS omzet",
            [today(), today()->addDay()]);

        if ($data['lihatPendaftaran']) {
            $data['totalPasien'] = (int) $angka->pasien;
            $data['identitasBelumVerif'] = (int) $angka->identitas;
        }

        if ($data['lihatAntrean']) {
            $antreanHariIni = Antrean::with(['pasien', 'poli'])->whereDate('tanggal_kunjungan', today())->orderBy('id')->get();
            $data['antrean'] = ['total' => $antreanHariIni->count()] + collect(Antrean::STATUS)
                ->mapWithKeys(fn ($s) => [$s => $antreanHariIni->where('status', $s)->count()])->all();
            $data['antreanHariIni'] = $antreanHariIni->whereIn('status', ['menunggu', 'dipanggil', 'dilayani'])->take(15);
            $data['rekomAntrean'] = $rekomAntrean->analisisHariIni($antreanHariIni)->where('total', '>', 0)->take(6);
        }

        if ($data['lihatObat']) {
            $hasilObat = $rekomObat->analisis(90);
            $batas = config('hak_akses.batas_kedaluwarsa_hari');
            $batch = BatchObat::where('sisa', '>', 0)
                ->whereDate('tanggal_kedaluwarsa', '<=', today()->addDays($batas))
                ->orderBy('tanggal_kedaluwarsa')->get();
            Relasi::pasang($batch, 'obat_id', 'obat', $hasilObat->pluck('obat'), \App\Models\Obat::class);
            $data += [
                'obatPerluDipesan' => $hasilObat->where('status', 'pesan')->count(),
                'obatMendesak' => $hasilObat->where('status', 'pesan')->take(3),
                'omzetHariIni' => (float) $angka->omzet,
                'batchKedaluwarsa' => $batch,
                'pesananMenunggu' => (int) $angka->pesanan,
                'batas' => $batas,
            ];
        }

        return view('dashboard', $data);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Antrean;
use App\Models\Poli;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class LaporanAntreanController extends Controller
{
    public function index(Request $request)
    {
        [$awal, $akhir, $poliId] = $this->filter($request);
        $data = $this->query($awal, $akhir, $poliId)->get();

        $ringkasan = ['total' => $data->count()] + collect(Antrean::STATUS)
            ->mapWithKeys(fn ($s) => [$s => $data->where('status', $s)->count()])->all();

        $menit = $data->map->menitTunggu()->filter(fn ($m) => $m !== null);

        return view('antrean.laporan', [
            'data' => $data,
            'ringkasan' => $ringkasan,
            'rataTunggu' => $menit->isEmpty() ? null : (int) round($menit->avg()),
            'daftarPoli' => Poli::orderBy('nama_poli')->get(),
            'awal' => $awal, 'akhir' => $akhir, 'poliId' => $poliId,
        ]);
    }

    public function ekspor(Request $request)
    {
        [$awal, $akhir, $poliId] = $this->filter($request);
        $data = $this->query($awal, $akhir, $poliId)->get();
        $namaFile = "laporan_antrean_{$awal}_sampai_{$akhir}.csv";

        return response()->streamDownload(function () use ($data) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // BOM agar terbaca rapi di Excel
            fputcsv($out, ['Tanggal', 'No. Antrean', 'Nama Pasien', 'Poli', 'Status', 'Lama Tunggu (menit)']);
            foreach ($data as $a) {
                fputcsv($out, [
                    $a->tanggal_kunjungan->format('Y-m-d'), $a->nomor_antrean, $a->pasien->nama,
                    $a->poli->nama_poli, $a->status, $a->menitTunggu() ?? '',
                ]);
            }
            fclose($out);
        }, $namaFile, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function filter(Request $request): array
    {
        $tanggal = function ($nilai) {
            try {
                return $nilai ? Carbon::createFromFormat('!Y-m-d', $nilai)->format('Y-m-d') : today()->format('Y-m-d');
            } catch (\Throwable) {
                return today()->format('Y-m-d');
            }
        };
        $awal = $tanggal($request->query('tanggal_awal'));
        $akhir = $tanggal($request->query('tanggal_akhir'));
        if ($awal > $akhir) {
            [$awal, $akhir] = [$akhir, $awal];
        }
        $poliId = ctype_digit((string) $request->query('poli_id')) ? (int) $request->query('poli_id') : null;

        return [$awal, $akhir, $poliId];
    }

    private function query(string $awal, string $akhir, ?int $poliId)
    {
        return Antrean::with(['pasien', 'poli'])
            ->whereBetween('tanggal_kunjungan', [$awal, $akhir])
            ->when($poliId, fn ($q) => $q->where('poli_id', $poliId))
            ->orderByDesc('tanggal_kunjungan')->orderBy('poli_id')->orderBy('nomor_antrean');
    }
}

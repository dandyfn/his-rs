<?php

namespace App\Http\Controllers;

use App\Services\LaporanObatService;
use App\Support\XlsxSederhana;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/** FR-15 / UC-12: laporan stok, penjualan, pengadaan, retur — tampil, cetak/PDF, unduh Excel. */
class LaporanObatController extends Controller
{
    public function index(Request $request, LaporanObatService $laporan)
    {
        [$jenis, $awal, $akhir] = $this->filter($request);

        return view('laporan-obat.index', [
            'jenis' => $jenis,
            'awal' => $awal,
            'akhir' => $akhir,
            'laporan' => $laporan->buat($jenis, $awal, $akhir),
            'excel' => XlsxSederhana::tersedia(),
        ]);
    }

    public function ekspor(Request $request, LaporanObatService $laporan)
    {
        [$jenis, $awal, $akhir] = $this->filter($request);
        $data = $laporan->buat($jenis, $awal, $akhir);
        $nama = 'laporan_' . $jenis . '_' . ($jenis === 'stok' ? now()->format('Y-m-d') : $awal->format('Y-m-d') . '_sd_' . $akhir->format('Y-m-d'));

        $judul = [config('app.name') . ' - MediStock RS', $data['judul'],
            collect($data['ringkasan'])->map(fn ($v, $k) => "{$k}: {$v}")->implode(' | '),
            'Dicetak oleh ' . $request->user()->name . ' pada ' . now()->format('d/m/Y H:i')];

        if (XlsxSederhana::tersedia()) {
            $isi = XlsxSederhana::buat(ucfirst($jenis), $judul, $data['header'], $data['baris']);

            return response($isi, 200, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Content-Disposition' => 'attachment; filename="' . $nama . '.xlsx"',
            ]);
        }

        // Cadangan bila ekstensi zip tidak aktif: CSV yang tetap bisa dibuka di Excel.
        return response()->streamDownload(function () use ($judul, $data) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            foreach ($judul as $j) {
                fputcsv($out, [$j], ';');
            }
            fputcsv($out, [], ';');
            fputcsv($out, $data['header'], ';');
            foreach ($data['baris'] as $b) {
                fputcsv($out, $b, ';');
            }
            fclose($out);
        }, $nama . '.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function filter(Request $request): array
    {
        $jenis = array_key_exists((string) $request->query('jenis'), LaporanObatService::JENIS) ? $request->query('jenis') : 'stok';
        try {
            $awal = Carbon::createFromFormat('Y-m-d', (string) $request->query('tanggal_awal'))->startOfDay();
        } catch (\Throwable) {
            $awal = now()->startOfMonth();
        }
        try {
            $akhir = Carbon::createFromFormat('Y-m-d', (string) $request->query('tanggal_akhir'))->startOfDay();
        } catch (\Throwable) {
            $akhir = today();
        }
        if ($awal->gt($akhir)) {
            [$awal, $akhir] = [$akhir, $awal];
        }

        return [$jenis, $awal, $akhir];
    }
}

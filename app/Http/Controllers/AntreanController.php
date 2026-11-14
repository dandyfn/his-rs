<?php

namespace App\Http\Controllers;

use App\Models\Antrean;
use App\Models\Pasien;
use App\Models\Poli;
use App\Services\AntreanService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AntreanController extends Controller
{
    private const URUTAN = ['dipanggil' => 1, 'dilayani' => 2, 'menunggu' => 3, 'selesai' => 4, 'batal' => 5];

    public function index(Request $request)
    {
        $cari = trim((string) $request->query('cari', ''));
        $filterStatus = in_array($request->query('filter_status'), Antrean::STATUS, true) ? $request->query('filter_status') : '';
        $pola = '%' . strtolower($cari) . '%';

        $hariIni = Antrean::with(['pasien', 'poli'])->whereDate('tanggal_kunjungan', today())->get();

        $daftar = $hariIni
            ->when($filterStatus !== '', fn ($c) => $c->where('status', $filterStatus))
            ->when($cari !== '', fn ($c) => $c->filter(fn ($a) => str_contains(strtolower(
                $a->nomor_antrean . ' ' . $a->pasien->kode_pasien . ' ' . $a->pasien->nama . ' ' . $a->poli->nama_poli
            ), strtolower($cari))))
            ->sortBy(fn ($a) => [self::URUTAN[$a->status] ?? 6, $a->id])
            ->values();

        $ringkasan = ['total' => $hariIni->count()] + collect(Antrean::STATUS)
            ->mapWithKeys(fn ($s) => [$s => $hariIni->where('status', $s)->count()])->all();

        return view('antrean.index', [
            'daftar' => $daftar,
            'ringkasan' => $ringkasan,
            'cari' => $cari,
            'filterStatus' => $filterStatus,
            'daftarPasien' => Pasien::orderBy('nama')->get(),
            'daftarPoli' => Poli::where('aktif', true)->orderBy('nama_poli')->get(),
        ]);
    }

    public function store(Request $request, AntreanService $service)
    {
        $data = $request->validate([
            'pasien_id' => ['required', 'integer', 'exists:pasien,id'],
            'poli_id' => ['required', 'integer'],
        ], ['pasien_id.required' => 'Silakan pilih pasien dan poli.', 'poli_id.required' => 'Silakan pilih pasien dan poli.']);

        $antrean = $service->daftar($data['pasien_id'], $data['poli_id']);

        return redirect()->route('antrean.index')
            ->with('pesan', "Pendaftaran antrean berhasil disimpan. Nomor antrean: {$antrean->nomor_antrean}.");
    }

    public function ubahStatus(Request $request, Antrean $antrean, AntreanService $service)
    {
        $data = $request->validate(['aksi' => ['required', Rule::in(array_keys(AntreanService::ALUR))]]);

        $service->ubahStatus($antrean->id, $data['aksi'], $request->user()->id);

        return redirect()->route('antrean.index', $request->only('cari', 'filter_status'))
            ->with('pesan', 'Status antrean berhasil diperbarui.');
    }
}

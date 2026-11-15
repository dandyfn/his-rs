<?php

namespace App\Http\Controllers;

use App\Models\BatchObat;
use App\Models\Obat;
use Illuminate\Http\Request;

/** FR-05 stok per batch dan FR-07 / US-04 peringatan kedaluwarsa. */
class BatchObatController extends Controller
{
    public function index(Request $request)
    {
        $batasDefault = config('hak_akses.batas_kedaluwarsa_hari');
        $batas = (int) $request->query('batas', $batasDefault);
        $batas = $batas >= 7 && $batas <= 365 ? $batas : $batasDefault;
        $obatId = (int) $request->query('obat_id') ?: null;
        $tampilHabis = $request->boolean('habis');

        $semua = BatchObat::with('obat')
            ->when($obatId, fn ($q) => $q->where('obat_id', $obatId))
            ->when(! $tampilHabis, fn ($q) => $q->where('sisa', '>', 0))
            ->orderBy('tanggal_kedaluwarsa')->orderBy('id')->get();

        $peringatan = BatchObat::with('obat')->where('sisa', '>', 0)
            ->whereDate('tanggal_kedaluwarsa', '<=', today()->addDays($batas))
            ->orderBy('tanggal_kedaluwarsa')->get();

        return view('batch.index', [
            'batas' => $batas,
            'obatId' => $obatId,
            'tampilHabis' => $tampilHabis,
            'daftarObat' => Obat::orderBy('nama_obat')->get(),
            'semua' => $semua,
            'peringatan' => $peringatan,
            'jumlahKedaluwarsa' => $peringatan->filter->sudahKedaluwarsa()->count(),
            'jumlahHampir' => $peringatan->reject->sudahKedaluwarsa()->count(),
            'unitBerisiko' => (int) $peringatan->sum('sisa'),
        ]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\BatchObat;
use App\Models\ReturObat;
use App\Services\StokObatService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** FR-11 / UC-09 / US-12: retur obat rusak atau kedaluwarsa. */
class ReturObatController extends Controller
{
    public function index(Request $request)
    {
        return view('retur.index', [
            // Batch kedaluwarsa tampil paling atas
            'daftarBatch' => BatchObat::with('obat')->where('sisa', '>', 0)->orderBy('tanggal_kedaluwarsa')->get(),
            'riwayat' => ReturObat::with(['obat', 'batch', 'petugas'])->latest('id')->limit(30)->get(),
            'pilihBatch' => (int) $request->query('batch_id'),
        ]);
    }

    public function store(Request $request, StokObatService $stok)
    {
        $data = $request->validate([
            'batch_id' => ['required', 'integer', 'exists:batch_obat,id'],
            'jumlah' => ['required', 'integer', 'min:1'],
            'alasan' => ['required', Rule::in(array_keys(ReturObat::ALASAN))],
            'tujuan' => ['required', Rule::in(array_keys(ReturObat::TUJUAN))],
            'keterangan' => ['nullable', 'string', 'max:255'],
        ]);

        $retur = $stok->prosesRetur($data, $request->user()->id);

        return redirect()->route('retur.index')->with('pesan',
            "Retur {$retur->nomor_retur} tersimpan. Stok {$retur->obat->nama_obat} berkurang {$retur->jumlah}.");
    }
}

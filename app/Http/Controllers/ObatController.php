<?php

namespace App\Http\Controllers;

use App\Models\Obat;
use App\Models\TransaksiStok;
use App\Services\StokObatService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ObatController extends Controller
{
    public function index()
    {
        $daftarObat = Obat::orderBy('nama_obat')->get();

        return view('obat.index', [
            'daftarObat' => $daftarObat,
            'ringkasan' => [
                'jumlah_jenis' => $daftarObat->count(),
                'total_stok' => $daftarObat->sum('stok'),
                'perlu_pengadaan' => $daftarObat->filter->perluDitinjau()->count(),
            ],
            'riwayat' => TransaksiStok::with('obat')->latest('id')->limit(20)->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'kode_obat' => ['required', 'regex:/^[A-Za-z0-9-]{3,30}$/', Rule::unique('obat', 'kode_obat')],
            'nama_obat' => ['required', 'string', 'max:150'],
            'satuan' => ['required', 'string', 'max:30'],
            'stok_minimum' => ['required', 'integer', 'min:0'],
            'harga_jual' => ['required', 'numeric', 'min:0'],
        ], [
            'kode_obat.unique' => 'Kode obat sudah digunakan.',
            'kode_obat.regex' => 'Kode obat 3-30 karakter: huruf, angka, atau tanda minus.',
        ]);
        $data['kode_obat'] = strtoupper($data['kode_obat']);

        Obat::create($data);

        return redirect()->route('obat.index')->with('pesan', 'Data obat berhasil ditambahkan.');
    }

    public function transaksi(Request $request, StokObatService $stok)
    {
        $data = $request->validate([
            'obat_id' => ['required', 'integer', 'exists:obat,id'],
            'jenis' => ['required', Rule::in(['masuk', 'keluar'])],
            'jumlah' => ['required', 'integer', 'min:1'],
            'keterangan' => ['nullable', 'string', 'max:255'],
        ]);

        $stok->catatTransaksiManual($data['obat_id'], $data['jenis'], $data['jumlah'], $data['keterangan'] ?? null, $request->user()->id);

        return redirect()->route('obat.index')->with('pesan', 'Transaksi stok berhasil disimpan.');
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Obat;
use App\Models\Pasien;
use App\Models\Penjualan;
use App\Services\StokObatService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PenjualanController extends Controller
{
    public function index()
    {
        $hariIni = Penjualan::whereDate('created_at', today());

        return view('penjualan.index', [
            'daftarPasien' => Pasien::orderBy('nama')->get(),
            'daftarObat' => Obat::orderBy('nama_obat')->get(),
            'riwayat' => Penjualan::latest('id')->limit(15)->get(),
            'transaksiHariIni' => (clone $hariIni)->count(),
            'omzetHariIni' => (float) (clone $hariIni)->sum('total'),
        ]);
    }

    public function store(Request $request, StokObatService $stok)
    {
        $data = $request->validate([
            'pasien_id' => ['nullable', 'integer', 'exists:pasien,id'],
            'nama_pembeli' => ['nullable', 'string', 'max:100', 'required_without:pasien_id'],
            'jenis' => ['required', Rule::in(['resep', 'non_resep'])],
            'nomor_resep' => ['nullable', 'string', 'max:50', 'required_if:jenis,resep'],
            'obat_id' => ['required', 'array', 'min:1'],
            'obat_id.*' => ['nullable', 'integer'],
            'jumlah' => ['required', 'array'],
            'jumlah.*' => ['nullable', 'integer', 'min:1'],
        ], [
            'nama_pembeli.required_without' => 'Pilih pasien atau isi nama pembeli.',
            'nomor_resep.required_if' => 'Nomor resep wajib diisi untuk penjualan dengan resep.',
        ]);

        // Gabungkan item: obat yang sama dijumlahkan
        $item = [];
        foreach ($data['obat_id'] as $i => $obatId) {
            $jumlah = (int) ($data['jumlah'][$i] ?? 0);
            if ($obatId && $jumlah > 0) {
                $item[$obatId] = ($item[$obatId] ?? 0) + $jumlah;
            }
        }
        if (! $item) {
            throw ValidationException::withMessages(['item' => 'Tambahkan minimal satu obat dengan jumlah lebih dari nol.']);
        }

        $pasienId = $data['pasien_id'] ?? null;
        $namaPembeli = $pasienId ? Pasien::find($pasienId)->nama : $data['nama_pembeli'];

        $penjualan = $stok->prosesPenjualan([
            'pasien_id' => $pasienId,
            'nama_pembeli' => $namaPembeli,
            'jenis' => $data['jenis'],
            'nomor_resep' => $data['nomor_resep'] ?? null,
        ], $item, $request->user()->id);

        return redirect()->route('penjualan.struk', [$penjualan, 'baru' => 1]);
    }

    public function struk(Penjualan $penjualan)
    {
        $penjualan->load(['detail.obat', 'petugas']);

        return view('penjualan.struk', ['jual' => $penjualan]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Obat;
use App\Models\Pembelian;
use App\Models\Supplier;
use App\Services\StokObatService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PembelianController extends Controller
{
    public function index(Request $request)
    {
        return view('pembelian.index', [
            'daftarSupplier' => Supplier::where('aktif', true)->orderBy('nama_supplier')->get(),
            'daftarObat' => Obat::orderBy('nama_obat')->get(),
            'riwayat' => Pembelian::with(['supplier', 'obat'])->latest('id')->limit(50)->get(),
            'jumlahTransaksi' => Pembelian::count(),
            'nilaiPembelian' => (float) Pembelian::sum('total_harga'),
            // Diisi otomatis dari tombol "Buat pembelian" di halaman rekomendasi
            'pilihObat' => (int) $request->query('obat_id'),
            'isiJumlah' => (int) $request->query('jumlah') ?: null,
        ]);
    }

    public function store(Request $request, StokObatService $stok)
    {
        $data = $request->validate([
            'supplier_id' => ['required', 'integer', Rule::exists('supplier_obat', 'id')->where('aktif', true)],
            'obat_id' => ['required', 'integer', 'exists:obat,id'],
            'jumlah' => ['required', 'integer', 'min:1'],
            'harga_satuan' => ['required', 'numeric', 'min:0', 'max:9999999999999'],
            'tanggal_pembelian' => ['required', 'date_format:Y-m-d'],
            'keterangan' => ['nullable', 'string', 'max:255'],
        ]);

        $pembelian = $stok->prosesPembelian($data, $request->user()->id);

        return redirect()->route('pembelian.index')
            ->with('pesan', "Pembelian {$pembelian->nomor_pembelian} tersimpan. Stok obat bertambah.");
    }
}

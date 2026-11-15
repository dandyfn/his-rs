<?php

namespace App\Http\Controllers;

use App\Models\Obat;
use App\Models\Pembelian;
use App\Models\Penerimaan;
use App\Models\Supplier;
use App\Services\StokObatService;
use App\Support\Relasi;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** UC-04 buat pesanan pengadaan dan UC-06 terima barang dari supplier. */
class PembelianController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status');
        $daftarSupplier = Supplier::orderBy('nama_supplier')->get();
        $daftarObat = Obat::orderBy('nama_obat')->get();

        $riwayat = Pembelian::with('penerimaan')
            ->when(array_key_exists((string) $status, Pembelian::STATUS), fn ($q) => $q->where('status', $status))
            ->orderByRaw("CASE WHEN status IN ('dipesan','sebagian') THEN 0 ELSE 1 END")
            ->latest('id')->limit(50)->get();
        $penerimaanTerakhir = Penerimaan::latest('id')->limit(10)->get();

        // Relasi dipasang dari data yang sudah dimuat (hemat query ke database awan).
        $pesanan = Relasi::pasang($penerimaanTerakhir, 'pembelian_id', 'pembelian', $riwayat, Pembelian::class);
        Relasi::pasang($riwayat->concat($pesanan->values()), 'supplier_id', 'supplier', $daftarSupplier, Supplier::class);
        Relasi::pasang($riwayat->concat($pesanan->values()), 'obat_id', 'obat', $daftarObat, Obat::class);
        Relasi::pasang($penerimaanTerakhir, 'user_id', 'petugas', collect(), \App\Models\User::class);

        // Tiga angka ringkasan dalam satu query
        $angka = Pembelian::selectRaw("
            SUM(CASE WHEN status IN ('dipesan','sebagian') THEN 1 ELSE 0 END) AS menunggu,
            SUM(CASE WHEN status <> 'batal' THEN 1 ELSE 0 END) AS jumlah,
            SUM(CASE WHEN status <> 'batal' THEN total_harga ELSE 0 END) AS nilai")->toBase()->first();

        $terimaId = $request->user()->can('pengadaan.terima') && $request->filled('terima') ? (int) $request->query('terima') : null;
        $terima = $terimaId ? ($riwayat->firstWhere('id', $terimaId) ?? Pembelian::with(['obat', 'supplier'])->find($terimaId)) : null;

        return view('pembelian.index', [
            'daftarSupplier' => $daftarSupplier->where('aktif', true)->values(),
            'daftarObat' => $daftarObat->where('aktif', true)->values(),
            'riwayat' => $riwayat,
            'status' => $status,
            'jumlahMenunggu' => (int) $angka->menunggu,
            'jumlahTransaksi' => (int) $angka->jumlah,
            'nilaiPembelian' => (float) $angka->nilai,
            'penerimaanTerakhir' => $penerimaanTerakhir,
            'terima' => $terima,
            // Diisi otomatis dari tombol "Buat pesanan" di halaman rekomendasi (UC-11 -> UC-04)
            'pilihObat' => (int) $request->query('obat_id'),
            'isiJumlah' => (int) $request->query('jumlah') ?: null,
            'dariRekomendasi' => $request->boolean('rekomendasi'),
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

        $pesanan = $stok->buatPesanan($data, $request->user()->id);

        return redirect()->route('pembelian.index')
            ->with('pesan', "Pesanan {$pesanan->nomor_pembelian} dibuat. Stok bertambah setelah barang diterima oleh staf gudang.");
    }

    public function terima(Request $request, Pembelian $pembelian, StokObatService $stok)
    {
        $data = $request->validate([
            'jumlah' => ['required', 'integer', 'min:1'],
            'nomor_batch' => ['required', 'string', 'max:50', 'regex:/^[A-Za-z0-9\-\/\.]+$/'],
            'tanggal_kedaluwarsa' => ['required', 'date_format:Y-m-d', 'after:tanggal_terima'],
            'tanggal_terima' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'keterangan' => ['nullable', 'string', 'max:255'],
        ], [
            'nomor_batch.required' => 'Nomor batch wajib diisi.',
            'nomor_batch.regex' => 'Nomor batch hanya boleh huruf, angka, dan tanda - / .',
            'tanggal_kedaluwarsa.required' => 'Tanggal kedaluwarsa wajib diisi.',
            'tanggal_kedaluwarsa.after' => 'Tanggal kedaluwarsa harus setelah tanggal terima.',
        ]);

        $penerimaan = $stok->terimaBarang($pembelian, $data, $request->user()->id);
        $pembelian->refresh();

        return redirect()->route('pembelian.index')->with('pesan',
            "Penerimaan {$penerimaan->nomor_penerimaan} tersimpan. Stok bertambah {$penerimaan->jumlah} pada batch {$penerimaan->nomor_batch}. "
            . ($pembelian->status === 'sebagian'
                ? "Pesanan diterima sebagian, masih kurang {$pembelian->sisaBelumDiterima()}."
                : 'Pesanan diterima lengkap.'));
    }

    public function batal(Pembelian $pembelian, StokObatService $stok)
    {
        $stok->batalkanPesanan($pembelian);

        return redirect()->route('pembelian.index')->with('pesan', "Pesanan {$pembelian->nomor_pembelian} dibatalkan.");
    }
}

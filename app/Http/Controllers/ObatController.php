<?php

namespace App\Http\Controllers;

use App\Models\BatchObat;
use App\Models\Obat;
use App\Models\RiwayatHarga;
use App\Models\TransaksiStok;
use App\Models\User;
use App\Support\Relasi;
use App\Services\StokObatService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ObatController extends Controller
{
    public function index(Request $request)
    {
        $daftarObat = Obat::withSum(['batch as stok_layak' => fn ($q) => $q->where('sisa', '>', 0)
            ->whereDate('tanggal_kedaluwarsa', '>=', today())], 'sisa')
            ->orderByDesc('aktif')->orderBy('nama_obat')->get();
        $batas = config('hak_akses.batas_kedaluwarsa_hari');

        // Relasi dipasang dari data yang sudah dimuat supaya halaman ini cukup sedikit query (database di awan).
        $daftarBatch = BatchObat::where('sisa', '>', 0)->orderBy('tanggal_kedaluwarsa')->get();
        $riwayat = TransaksiStok::latest('id')->limit(20)->get();
        $riwayatHarga = RiwayatHarga::latest('id')->limit(10)->get();
        Relasi::pasang($daftarBatch, 'obat_id', 'obat', $daftarObat, Obat::class);
        Relasi::pasang($riwayat, 'obat_id', 'obat', $daftarObat, Obat::class);
        Relasi::pasang($riwayatHarga, 'obat_id', 'obat', $daftarObat, Obat::class);
        Relasi::pasang($riwayat, 'batch_id', 'batch', $daftarBatch, BatchObat::class);
        $pengguna = Relasi::pasang($riwayat, 'user_id', 'petugas', collect(), User::class);
        Relasi::pasang($riwayatHarga, 'user_id', 'petugas', $pengguna, User::class);

        return view('obat.index', [
            'daftarObat' => $daftarObat,
            'ringkasan' => [
                'jumlah_jenis' => $daftarObat->where('aktif', true)->count(),
                'total_stok' => $daftarObat->sum('stok'),
                'perlu_pengadaan' => $daftarObat->where('aktif', true)->filter->perluDitinjau()->count(),
                'mendekati_kedaluwarsa' => $daftarBatch->filter(fn ($b) => $b->tanggal_kedaluwarsa->lte(today()->addDays($batas)))->count(),
            ],
            'batas' => $batas,
            'daftarBatch' => $daftarBatch,
            'riwayat' => $riwayat,
            'riwayatHarga' => $riwayatHarga,
            'edit' => $request->user()->can('obat.kelola') && $request->filled('edit') ? $daftarObat->firstWhere('id', (int) $request->query('edit')) : null,
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validasi($request);
        $data['kode_obat'] = strtoupper($data['kode_obat']);
        Obat::create($data + ['stok' => 0, 'aktif' => true]);

        return redirect()->route('obat.index')->with('pesan', 'Data obat berhasil ditambahkan. Stok bertambah saat barang diterima dari supplier.');
    }

    public function update(Request $request, Obat $obat, StokObatService $stok)
    {
        $data = $this->validasi($request, $obat);
        $data['kode_obat'] = strtoupper($data['kode_obat']);
        $stok->perbaruiObat($obat, $data, $request->user()->id);

        return redirect()->route('obat.index')->with('pesan', "Data obat {$obat->nama_obat} berhasil diperbarui.");
    }

    public function toggle(Obat $obat)
    {
        $obat->update(['aktif' => ! $obat->aktif]);

        return redirect()->route('obat.index')->with('pesan', $obat->aktif
            ? "{$obat->nama_obat} diaktifkan kembali."
            : "{$obat->nama_obat} dinonaktifkan. Obat tidak muncul lagi di penjualan dan pesanan pengadaan.");
    }

    /** Penyesuaian stok (stock opname) per batch. */
    public function transaksi(Request $request, StokObatService $stok)
    {
        $data = $request->validate([
            'obat_id' => ['required', 'integer', 'exists:obat,id'],
            'jenis' => ['required', Rule::in(['masuk', 'keluar'])],
            'jumlah' => ['required', 'integer', 'min:1'],
            'nomor_batch' => ['required_if:jenis,masuk', 'nullable', 'string', 'max:50'],
            'tanggal_kedaluwarsa' => ['required_if:jenis,masuk', 'nullable', 'date_format:Y-m-d', 'after:today'],
            'batch_id' => ['required_if:jenis,keluar', 'nullable', 'integer'],
            'keterangan' => ['required', 'string', 'max:255'],
        ], [
            'nomor_batch.required_if' => 'Nomor batch wajib diisi untuk stok masuk.',
            'tanggal_kedaluwarsa.required_if' => 'Tanggal kedaluwarsa wajib diisi untuk stok masuk.',
            'tanggal_kedaluwarsa.after' => 'Tanggal kedaluwarsa harus setelah hari ini.',
            'batch_id.required_if' => 'Pilih batch yang dikurangi.',
            'keterangan.required' => 'Isi alasan penyesuaian stok (contoh: hasil stock opname).',
        ]);

        $stok->penyesuaianStok($data, $request->user()->id);

        return redirect()->route('obat.index')->with('pesan', 'Penyesuaian stok berhasil disimpan.');
    }

    private function validasi(Request $request, ?Obat $obat = null): array
    {
        $validator = validator($request->all(), [
            'kode_obat' => ['required', 'regex:/^[A-Za-z0-9-]{3,30}$/', Rule::unique('obat', 'kode_obat')->ignore($obat?->id)],
            'nama_obat' => ['required', 'string', 'max:150'],
            'satuan' => ['required', 'string', 'max:30'],
            'stok_minimum' => ['required', 'integer', 'min:0'],
            'harga_jual' => ['required', 'numeric', 'min:0', 'max:9999999999'],
            'lead_time_hari' => ['required', 'integer', 'min:1', 'max:180'],
            'stok_pengaman_hari' => ['required', 'integer', 'min:0', 'max:180'],
            'interval_pesan_hari' => ['required', 'integer', 'min:1', 'max:365'],
        ], [
            'kode_obat.unique' => 'Kode obat sudah digunakan.',
            'kode_obat.regex' => 'Kode obat 3-30 karakter: huruf, angka, atau tanda minus.',
        ], [
            'lead_time_hari' => 'lama kirim supplier', 'stok_pengaman_hari' => 'stok pengaman',
            'interval_pesan_hari' => 'jarak antar pesanan',
        ]);

        // US-01: nama obat tidak boleh kembar (tidak membedakan huruf besar/kecil).
        $validator->after(function (Validator $v) use ($request, $obat) {
            $kembar = Obat::whereRaw('LOWER(nama_obat) = ?', [mb_strtolower(trim((string) $request->input('nama_obat')))])
                ->when($obat, fn ($q) => $q->where('id', '<>', $obat->id))->exists();
            if ($kembar) {
                $v->errors()->add('nama_obat', 'Nama obat sudah terdaftar. Gunakan data obat yang sudah ada.');
            }
        });

        $data = $validator->validate();
        $data['nama_obat'] = trim($data['nama_obat']);

        return $data;
    }
}

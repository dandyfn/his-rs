<?php

namespace App\Http\Controllers;

use App\Models\IdentitasPasien;
use App\Models\Pasien;
use App\Services\IdentitasPasienService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class IdentitasPasienController extends Controller
{
    public function index(Request $request, IdentitasPasienService $service)
    {
        $cari = trim((string) $request->query('cari', ''));
        $pola = '%' . strtolower($cari) . '%';

        $daftar = IdentitasPasien::with(['riwayat' => fn ($q) => $q->limit(50), 'pasien:id,kode_pasien,nama'])
            ->when($cari !== '', fn ($q) => $q->where(fn ($q) => $q
                ->whereRaw('LOWER(nama_sementara) LIKE ?', [$pola])
                ->orWhereRaw('LOWER(kode_sementara) LIKE ?', [$pola])
                ->orWhereRaw("LOWER(COALESCE(nomor_identitas, '')) LIKE ?", [$pola])))
            ->latest('id')
            ->limit(200)
            ->get();

        // Kandidat pasien tetap hanya untuk identitas terverifikasi yang belum diselesaikan.
        $kandidat = $daftar->filter(fn ($r) => $r->status_verifikasi === 'terverifikasi' && ! $r->pasien_id)
            ->mapWithKeys(fn ($r) => [$r->id => $service->kandidatPasien($r)]);

        $semuaPasien = $kandidat->isNotEmpty()
            ? Pasien::whereDoesntHave('identitasAsal')->orderBy('kode_pasien')->get(['id', 'kode_pasien', 'nama'])
            : collect();

        $edit = $request->filled('edit') ? IdentitasPasien::whereNull('pasien_id')->find($request->query('edit')) : null;

        return view('pendaftaran.identitas', compact('daftar', 'cari', 'kandidat', 'semuaPasien', 'edit'));
    }

    public function store(Request $request, IdentitasPasienService $service)
    {
        $identitas = $service->daftarSementara($this->validasi($request), $request->user()->id);
        $mirip = $service->cariKemiripan($identitas);

        $pesan = 'Identitas sementara berhasil disimpan. Kode: ' . $identitas->kode_sementara;
        if ($mirip->isNotEmpty()) {
            $pesan .= '. Perhatian: ada ' . $mirip->count() . ' identitas dengan nama mirip ('
                . $mirip->pluck('kode_sementara')->implode(', ') . '). Periksa kemungkinan pendaftaran ganda.';
        }

        return redirect()->route('identitas.index')->with('pesan', $pesan);
    }

    /** FR-06: melengkapi / memperbarui identitas sementara. */
    public function update(Request $request, IdentitasPasien $identitas, IdentitasPasienService $service)
    {
        $berubah = $service->perbarui($identitas, $this->validasi($request), $request->user()->id);

        return redirect()->route('identitas.index')->with('pesan', $berubah
            ? 'Identitas ' . $identitas->kode_sementara . ' berhasil diperbarui. Perubahan tercatat di riwayat.'
            : 'Tidak ada data yang berubah.');
    }

    public function tindakan(Request $request, IdentitasPasien $identitas, IdentitasPasienService $service)
    {
        $data = $request->validate([
            'aksi' => ['required', Rule::in(['verifikasi', 'konfirmasi'])],
            'catatan_verifikasi' => ['nullable', 'string', 'max:2000'],
        ]);

        $service->ubahStatus($identitas, $data['aksi'], $data['catatan_verifikasi'] ?? null, $request->user()->id);

        return redirect()->route('identitas.index')->with('pesan', $data['aksi'] === 'verifikasi'
            ? 'Identitas berhasil ditandai terverifikasi.'
            : 'Identitas ditandai perlu konfirmasi.');
    }

    /** FR-07: menjadikan identitas terverifikasi sebagai pasien tetap. */
    public function jadikanPasien(Request $request, IdentitasPasien $identitas, IdentitasPasienService $service)
    {
        $data = $request->validate(['pasien_id' => ['nullable', 'integer', 'exists:pasien,id']]);

        $pasien = $service->jadikanPasienTetap($identitas, $data['pasien_id'] ?? null, $request->user()->id);

        return redirect()->route('identitas.index')->with('pesan', 'Identitas ' . $identitas->kode_sementara
            . ' kini terkait dengan pasien tetap ' . $pasien->kode_pasien . ' - ' . $pasien->nama
            . '. Pasien sudah bisa didaftarkan ke antrean.');
    }

    private function validasi(Request $request): array
    {
        $data = $request->validate([
            'nama_sementara' => ['required', 'string', 'max:100'],
            'tanggal_lahir' => ['nullable', 'date_format:Y-m-d', 'before_or_equal:today'],
            'nomor_identitas' => ['nullable', 'string', 'max:50'],
            'kontak' => ['nullable', 'string', 'max:30'],
        ], [
            'tanggal_lahir.before_or_equal' => 'Tanggal lahir tidak boleh melebihi hari ini.',
        ]);

        foreach (['tanggal_lahir', 'nomor_identitas', 'kontak'] as $f) {
            $data[$f] = isset($data[$f]) && trim($data[$f]) !== '' ? trim($data[$f]) : null;
        }

        return $data;
    }
}

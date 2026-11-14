<?php

namespace App\Http\Controllers;

use App\Models\IdentitasPasien;
use App\Services\IdentitasPasienService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class IdentitasPasienController extends Controller
{
    public function index(Request $request)
    {
        $cari = trim((string) $request->query('cari', ''));
        $pola = '%' . strtolower($cari) . '%';

        $daftar = IdentitasPasien::with(['riwayat' => fn ($q) => $q->limit(50)])
            ->when($cari !== '', fn ($q) => $q->where(fn ($q) => $q
                ->whereRaw('LOWER(nama_sementara) LIKE ?', [$pola])
                ->orWhereRaw('LOWER(kode_sementara) LIKE ?', [$pola])
                ->orWhereRaw("LOWER(COALESCE(nomor_identitas, '')) LIKE ?", [$pola])))
            ->latest('id')
            ->limit(200)
            ->get();

        return view('pendaftaran.identitas', compact('daftar', 'cari'));
    }

    public function store(Request $request, IdentitasPasienService $service)
    {
        $data = $request->validate([
            'nama_sementara' => ['required', 'string', 'max:100'],
            'tanggal_lahir' => ['nullable', 'date_format:Y-m-d', 'before_or_equal:today'],
            'nomor_identitas' => ['nullable', 'string', 'max:50'],
            'kontak' => ['nullable', 'string', 'max:30'],
        ]);

        $identitas = $service->daftarSementara($data, $request->user()->id);
        $mirip = $service->cariKemiripan($identitas);

        $pesan = 'Identitas sementara berhasil disimpan. Kode: ' . $identitas->kode_sementara;
        if ($mirip->isNotEmpty()) {
            $pesan .= '. Perhatian: ada ' . $mirip->count() . ' identitas dengan nama mirip ('
                . $mirip->pluck('kode_sementara')->implode(', ') . '). Periksa kemungkinan pendaftaran ganda.';
        }

        return redirect()->route('identitas.index')->with('pesan', $pesan);
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
}

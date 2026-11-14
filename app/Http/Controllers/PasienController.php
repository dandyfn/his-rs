<?php

namespace App\Http\Controllers;

use App\Models\Pasien;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PasienController extends Controller
{
    public function index(Request $request)
    {
        $cari = trim((string) $request->query('cari', ''));

        $daftar = Pasien::query()
            ->when($cari !== '', fn ($q) => $q->where(fn ($q) => $q
                ->whereRaw('LOWER(kode_pasien) LIKE ?', ['%' . strtolower($cari) . '%'])
                ->orWhereRaw('LOWER(nama) LIKE ?', ['%' . strtolower($cari) . '%'])))
            ->latest('id')
            ->get();

        $edit = $request->filled('edit') ? Pasien::find($request->query('edit')) : null;

        return view('pendaftaran.pasien', compact('daftar', 'cari', 'edit'));
    }

    public function store(Request $request)
    {
        Pasien::create($this->validasi($request));

        return redirect()->route('pasien.index')->with('pesan', 'Data pasien berhasil ditambahkan.');
    }

    public function update(Request $request, Pasien $pasien)
    {
        $pasien->update($this->validasi($request, $pasien->id));

        return redirect()->route('pasien.index')->with('pesan', 'Data pasien berhasil diperbarui.');
    }

    private function validasi(Request $request, ?int $kecualiId = null): array
    {
        $request->merge(['kode_pasien' => strtoupper(trim((string) $request->input('kode_pasien')))]);

        return $request->validate([
            'kode_pasien' => ['required', 'regex:/^[A-Z0-9-]{3,20}$/', Rule::unique('pasien', 'kode_pasien')->ignore($kecualiId)],
            'nama' => ['required', 'string', 'max:100'],
        ], [
            'kode_pasien.regex' => 'Kode pasien harus 3–20 karakter (huruf, angka, tanda hubung).',
            'kode_pasien.unique' => 'Kode pasien sudah digunakan. Gunakan kode lain.',
        ]);
    }
}

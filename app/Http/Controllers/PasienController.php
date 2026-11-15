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
        $pola = '%' . strtolower($cari) . '%';

        $daftar = Pasien::with('identitasAsal:id,pasien_id,kode_sementara')
            ->when($cari !== '', fn ($q) => $q->where(fn ($q) => $q
                ->whereRaw('LOWER(kode_pasien) LIKE ?', [$pola])
                ->orWhereRaw('LOWER(nama) LIKE ?', [$pola])
                ->orWhereRaw("COALESCE(nik, '') LIKE ?", [$pola])))
            ->latest('id')
            ->get();

        $edit = $request->filled('edit') ? Pasien::find($request->query('edit')) : null;
        $kodeBaru = Pasien::kodeBerikutnya();

        return view('pendaftaran.pasien', compact('daftar', 'cari', 'edit', 'kodeBaru'));
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

    /** FR-04: validasi kelengkapan dan format data pendaftaran. */
    private function validasi(Request $request, ?int $kecualiId = null): array
    {
        $request->merge([
            'kode_pasien' => strtoupper(trim((string) $request->input('kode_pasien'))),
            'nik' => trim((string) $request->input('nik')) ?: null,
        ]);

        return $request->validate([
            'kode_pasien' => ['required', 'regex:/^[A-Z0-9-]{3,20}$/', Rule::unique('pasien', 'kode_pasien')->ignore($kecualiId)],
            'nama' => ['required', 'string', 'max:100'],
            'nik' => ['nullable', 'digits:16', Rule::unique('pasien', 'nik')->ignore($kecualiId)],
            'tanggal_lahir' => ['nullable', 'date_format:Y-m-d', 'before_or_equal:today'],
            'jenis_kelamin' => ['nullable', Rule::in(['L', 'P'])],
            'alamat' => ['nullable', 'string', 'max:255'],
            'no_hp' => ['nullable', 'regex:/^[0-9+\- ]{8,20}$/'],
        ], [
            'kode_pasien.regex' => 'Kode pasien harus 3–20 karakter (huruf, angka, tanda hubung).',
            'kode_pasien.unique' => 'Kode pasien sudah digunakan. Gunakan kode lain.',
            'nik.digits' => 'NIK harus 16 digit angka.',
            'nik.unique' => 'NIK sudah terdaftar pada pasien lain. Periksa kemungkinan data ganda.',
            'tanggal_lahir.before_or_equal' => 'Tanggal lahir tidak boleh melebihi hari ini.',
            'no_hp.regex' => 'No. HP 8–20 karakter (angka, spasi, +, atau -).',
        ]);
    }
}

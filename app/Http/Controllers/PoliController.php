<?php

namespace App\Http\Controllers;

use App\Models\Poli;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PoliController extends Controller
{
    public function index(Request $request)
    {
        $edit = $request->filled('edit') && $request->user()->role === 'admin'
            ? Poli::find($request->query('edit'))
            : null;

        return view('antrean.poli', [
            'daftar' => Poli::orderBy('nama_poli')->get(),
            'edit' => $edit,
        ]);
    }

    public function store(Request $request)
    {
        Poli::create($this->validasi($request));

        return redirect()->route('poli.index')->with('pesan', 'Poli berhasil ditambahkan.');
    }

    public function update(Request $request, Poli $poli)
    {
        $poli->update($this->validasi($request, $poli->id));

        return redirect()->route('poli.index')->with('pesan', 'Data poli berhasil diperbarui.');
    }

    public function toggle(Poli $poli)
    {
        $poli->update(['aktif' => ! $poli->aktif]);

        return redirect()->route('poli.index')->with('pesan', 'Status poli berhasil diperbarui.');
    }

    private function validasi(Request $request, ?int $kecualiId = null): array
    {
        return $request->validate([
            'nama_poli' => ['required', 'string', 'max:100', Rule::unique('poli', 'nama_poli')->ignore($kecualiId)],
            'batas_antrean' => ['required', 'integer', 'min:1', 'max:10000'],
        ], ['nama_poli.unique' => 'Nama poli sudah ada.']);
    }
}

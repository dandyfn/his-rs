<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SupplierController extends Controller
{
    public function index()
    {
        $daftar = Supplier::orderBy('nama_supplier')->get();

        return view('supplier.index', [
            'daftar' => $daftar,
            'total' => $daftar->count(),
            'aktif' => $daftar->where('aktif', true)->count(),
            'nonaktif' => $daftar->where('aktif', false)->count(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'kode_supplier' => ['required', 'string', 'max:30', Rule::unique('supplier_obat', 'kode_supplier')],
            'nama_supplier' => ['required', 'string', 'max:150'],
            'kontak' => ['nullable', 'string', 'max:50'],
            'alamat' => ['nullable', 'string', 'max:1000'],
        ], ['kode_supplier.unique' => 'Kode supplier sudah digunakan.']);
        $data['kode_supplier'] = strtoupper($data['kode_supplier']);

        Supplier::create($data);

        return redirect()->route('supplier.index')->with('pesan', 'Supplier berhasil ditambahkan.');
    }

    public function toggle(Supplier $supplier)
    {
        $supplier->update(['aktif' => ! $supplier->aktif]);

        return redirect()->route('supplier.index')->with('pesan', 'Status supplier berhasil diperbarui.');
    }
}

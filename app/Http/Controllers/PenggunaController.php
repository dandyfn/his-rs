<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class PenggunaController extends Controller
{
    public function index()
    {
        return view('pengguna.index', ['daftar' => User::orderBy('name')->get()]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'username' => ['required', 'string', 'min:4', 'max:50', 'regex:/^[A-Za-z0-9_.]+$/', Rule::unique('users', 'username')],
            'password' => ['required', 'confirmed', Password::min(8)],
            'role' => ['required', Rule::in(['admin', 'petugas'])],
        ], [
            'username.unique' => 'Username sudah digunakan.',
            'username.regex' => 'Username hanya boleh huruf, angka, titik, dan garis bawah.',
        ]);

        User::create($data + ['aktif' => true]);

        return redirect()->route('pengguna.index')->with('pesan', 'Akun pengguna berhasil ditambahkan.');
    }

    public function toggle(Request $request, User $user)
    {
        if ($user->id === $request->user()->id) {
            throw ValidationException::withMessages(['akun' => 'Akun yang sedang dipakai tidak boleh dinonaktifkan.']);
        }

        DB::transaction(function () use ($user) {
            $user = User::lockForUpdate()->find($user->id);
            if ($user->role === 'admin' && $user->aktif
                && User::where('role', 'admin')->where('aktif', true)->count() <= 1) {
                throw ValidationException::withMessages(['akun' => 'Admin aktif terakhir tidak boleh dinonaktifkan.']);
            }
            $user->update(['aktif' => ! $user->aktif]);
        });

        return redirect()->route('pengguna.index')->with('pesan', 'Status akun berhasil diperbarui.');
    }
}

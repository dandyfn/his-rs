@extends('layouts.app')
@section('title', 'Manajemen Pengguna')

@section('content')
    <h2>Manajemen Pengguna</h2>
    <p class="teks-bantu">Kelola akun petugas yang dapat masuk ke sistem. Halaman ini hanya untuk admin.</p>

    <section class="obat-card">
        <h3>Tambah Akun Pengguna</h3>
        <form method="POST" action="{{ route('pengguna.store') }}">
            @csrf
            <div class="obat-grid">
                <div><label for="name">Nama lengkap *</label><input id="name" name="name" required maxlength="100" value="{{ old('name') }}"></div>
                <div><label for="username">Username *</label><input id="username" name="username" required minlength="4" maxlength="50" value="{{ old('username') }}" autocomplete="off"></div>
                <div>
                    <label for="role">Role *</label>
                    <select id="role" name="role" required>
                        <option value="petugas" @selected(old('role') === 'petugas')>Petugas</option>
                        <option value="admin" @selected(old('role') === 'admin')>Admin</option>
                    </select>
                </div>
                <div><label for="password">Password * (min. 8 karakter)</label><input type="password" id="password" name="password" required minlength="8" autocomplete="new-password"></div>
                <div><label for="password_confirmation">Ulangi password *</label><input type="password" id="password_confirmation" name="password_confirmation" required minlength="8" autocomplete="new-password"></div>
            </div>
            <p class="teks-bantu">Admin dapat mengelola pengguna dan data poli. Petugas dapat memakai seluruh fitur operasional.</p>
            <button type="submit">Simpan Akun</button>
        </form>
    </section>

    <section class="obat-card">
        <h3>Daftar Akun</h3>
        <div class="obat-scroll">
            <table class="obat-table">
                <thead><tr><th>No.</th><th>Nama</th><th>Username</th><th>Role</th><th>Status</th><th>Aksi</th></tr></thead>
                <tbody>
                @foreach ($daftar as $u)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>{{ $u->name }}</td>
                        <td>{{ $u->username }}</td>
                        <td><span class="badge {{ $u->role === 'admin' ? 'badge-biru' : 'badge-abu' }}">{{ $u->role }}</span></td>
                        <td><span class="badge {{ $u->aktif ? 'badge-hijau' : 'badge-merah' }}">{{ $u->aktif ? 'Aktif' : 'Nonaktif' }}</span></td>
                        <td>
                            @if ($u->id === auth()->id())
                                <span class="teks-bantu">Akun Anda</span>
                            @else
                                <form method="POST" action="{{ route('pengguna.toggle', $u) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="btn-garis btn-kecil">{{ $u->aktif ? 'Nonaktifkan' : 'Aktifkan' }}</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </section>
@endsection

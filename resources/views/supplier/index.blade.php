@extends('layouts.app')
@section('title', 'Data Supplier Obat')

@section('content')
    <h2>Manajemen Supplier Obat</h2>
    <p class="teks-bantu">Kelola data pemasok untuk mendukung proses pembelian dan pengadaan obat.</p>

    <section class="obat-card">
        <h3>Ringkasan Supplier</h3>
        <div class="obat-ringkasan">
            <div class="obat-stat"><div class="obat-stat-label">Total supplier</div><div class="obat-angka">{{ $total }}</div></div>
            <div class="obat-stat"><div class="obat-stat-label">Supplier aktif</div><div class="obat-angka angka-hijau">{{ $aktif }}</div></div>
            <div class="obat-stat"><div class="obat-stat-label">Supplier nonaktif</div><div class="obat-angka">{{ $nonaktif }}</div></div>
        </div>
    </section>

    <section class="obat-card">
        <h3>Tambah Supplier</h3>
        <form method="POST" action="{{ route('supplier.store') }}">
            @csrf
            <div class="obat-grid">
                <div>
                    <label for="kode_supplier">Kode Supplier *</label>
                    <input id="kode_supplier" name="kode_supplier" value="{{ old('kode_supplier') }}" required maxlength="30" placeholder="SUP-001">
                </div>
                <div>
                    <label for="nama_supplier">Nama Supplier *</label>
                    <input id="nama_supplier" name="nama_supplier" value="{{ old('nama_supplier') }}" required maxlength="150" placeholder="Contoh: PT Sehat Farma">
                </div>
                <div>
                    <label for="kontak">Nomor Telepon / Kontak</label>
                    <input id="kontak" name="kontak" value="{{ old('kontak') }}" maxlength="50" placeholder="0341-xxxxxx">
                </div>
                <div>
                    <label for="alamat">Alamat Supplier</label>
                    <input id="alamat" name="alamat" value="{{ old('alamat') }}" placeholder="Alamat lengkap">
                </div>
            </div>
            <p class="teks-bantu">Supplier yang dinonaktifkan tidak muncul di formulir pembelian obat.</p>
            <button type="submit">Simpan Supplier</button>
        </form>
    </section>

    <section class="obat-card">
        <h3>Daftar Supplier</h3>
        <div class="obat-scroll">
            <table class="obat-table">
                <thead>
                    <tr><th>No.</th><th>Kode Supplier</th><th>Nama Supplier</th><th>Kontak</th><th>Alamat</th><th>Status</th><th>Aksi</th></tr>
                </thead>
                <tbody>
                @forelse ($daftar as $s)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>{{ $s->kode_supplier }}</td>
                        <td>{{ $s->nama_supplier }}</td>
                        <td>{{ $s->kontak ?: '-' }}</td>
                        <td>{{ $s->alamat ?: '-' }}</td>
                        <td>
                            @if ($s->aktif)
                                <span class="badge badge-hijau">Aktif</span>
                            @else
                                <span class="badge badge-abu">Nonaktif</span>
                            @endif
                        </td>
                        <td>
                            <form method="POST" action="{{ route('supplier.toggle', $s) }}">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="btn-garis btn-kecil">{{ $s->aktif ? 'Nonaktifkan' : 'Aktifkan' }}</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7">Belum ada data supplier.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection

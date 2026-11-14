@extends('layouts.app')
@section('title', 'Data Pasien')

@section('content')
    <h2>Data Pasien</h2>
    <p class="teks-bantu">Kelola data induk pasien yang dipakai oleh pendaftaran antrean dan penjualan obat.</p>

    <section class="obat-card">
        <h3>{{ $edit ? 'Edit Data Pasien' : 'Tambah Pasien' }}</h3>
        <form method="POST" action="{{ $edit ? route('pasien.update', $edit) : route('pasien.store') }}">
            @csrf
            @if ($edit) @method('PUT') @endif
            <div class="obat-grid">
                <div>
                    <label for="kode_pasien">Kode pasien *</label>
                    <input id="kode_pasien" name="kode_pasien" required maxlength="20" pattern="[A-Za-z0-9-]{3,20}"
                           value="{{ old('kode_pasien', $edit?->kode_pasien) }}" placeholder="P-0001">
                </div>
                <div>
                    <label for="nama">Nama pasien *</label>
                    <input id="nama" name="nama" required maxlength="100" value="{{ old('nama', $edit?->nama) }}" placeholder="Nama lengkap">
                </div>
            </div>
            <p class="teks-bantu">Kode pasien 3–20 karakter: huruf, angka, atau tanda hubung.</p>
            <button type="submit">{{ $edit ? 'Simpan Perubahan' : 'Simpan Pasien' }}</button>
            @if ($edit)
                <a class="btn-garis" href="{{ route('pasien.index') }}">Batal</a>
            @endif
        </form>
    </section>

    <section class="obat-card">
        <h3>Daftar Pasien</h3>
        <form class="obat-cari" method="GET" action="{{ route('pasien.index') }}">
            <input name="cari" value="{{ $cari }}" placeholder="Cari kode atau nama pasien">
            <button type="submit">Cari</button>
        </form>
        <div class="obat-scroll">
            <table class="obat-table">
                <thead><tr><th>No.</th><th>Kode Pasien</th><th>Nama</th><th>Terdaftar</th><th>Aksi</th></tr></thead>
                <tbody>
                @forelse ($daftar as $p)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>{{ $p->kode_pasien }}</td>
                        <td>{{ $p->nama }}</td>
                        <td>{{ $p->created_at?->format('d/m/Y H:i') }}</td>
                        <td><a class="btn-garis btn-kecil" href="{{ route('pasien.index', ['edit' => $p->id]) }}">Edit</a></td>
                    </tr>
                @empty
                    <tr><td colspan="5">{{ $cari !== '' ? 'Pasien tidak ditemukan.' : 'Belum ada data pasien.' }}</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection

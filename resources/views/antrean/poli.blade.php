@extends('layouts.app')
@section('title', 'Data Poli')

@section('content')
    <h2>Data Poli</h2>
    <p class="teks-bantu">Daftar poli rawat jalan beserta batas antrean per hari. Penambahan dan perubahan data poli hanya untuk admin.</p>

    @can('admin')
        <section class="obat-card">
            <h3>{{ $edit ? 'Edit Poli' : 'Tambah Poli' }}</h3>
            <form method="POST" action="{{ $edit ? route('poli.update', $edit) : route('poli.store') }}">
                @csrf
                @if ($edit) @method('PUT') @endif
                <div class="obat-grid">
                    <div>
                        <label for="nama_poli">Nama poli *</label>
                        <input id="nama_poli" name="nama_poli" required maxlength="100" value="{{ old('nama_poli', $edit?->nama_poli) }}" placeholder="Contoh: Poli Mata">
                    </div>
                    <div>
                        <label for="batas_antrean">Batas antrean per hari *</label>
                        <input type="number" id="batas_antrean" name="batas_antrean" min="1" max="10000" required value="{{ old('batas_antrean', $edit?->batas_antrean ?? 20) }}">
                    </div>
                </div>
                <p class="teks-bantu"></p>
                <button type="submit">{{ $edit ? 'Simpan Perubahan' : 'Simpan Poli' }}</button>
                @if ($edit)
                    <a class="btn-garis" href="{{ route('poli.index') }}">Batal</a>
                @endif
            </form>
        </section>
    @else
        <div class="obat-alert">Anda login sebagai petugas. Data poli hanya dapat dilihat.</div>
    @endcan

    <section class="obat-card">
        <h3>Daftar Poli</h3>
        <div class="obat-scroll">
            <table class="obat-table">
                <thead><tr><th>No.</th><th>Nama Poli</th><th>Batas Antrean</th><th>Status</th>@can('admin')<th>Aksi</th>@endcan</tr></thead>
                <tbody>
                @forelse ($daftar as $p)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>{{ $p->nama_poli }}</td>
                        <td>{{ $p->batas_antrean }} / hari</td>
                        <td><span class="badge {{ $p->aktif ? 'badge-hijau' : 'badge-abu' }}">{{ $p->aktif ? 'Aktif' : 'Nonaktif' }}</span></td>
                        @can('admin')
                            <td>
                                <div class="aksi-baris">
                                    <a class="btn-garis btn-kecil" href="{{ route('poli.index', ['edit' => $p->id]) }}">Edit</a>
                                    <form method="POST" action="{{ route('poli.toggle', $p) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="btn-garis btn-kecil">{{ $p->aktif ? 'Nonaktifkan' : 'Aktifkan' }}</button>
                                    </form>
                                </div>
                            </td>
                        @endcan
                    </tr>
                @empty
                    <tr><td colspan="5">Belum ada data poli.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection

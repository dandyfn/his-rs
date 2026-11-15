@extends('layouts.app')
@section('title', 'Data Pasien')

@section('content')
    <h2>Data Pasien</h2>
    <p class="teks-bantu">Kelola data induk pasien yang dipakai oleh pendaftaran antrean dan penjualan obat.</p>

    @can('pasien.kelola')
    <section class="obat-card">
        <h3>{{ $edit ? 'Edit Data Pasien' : 'Tambah Pasien Baru' }}</h3>
        <form method="POST" action="{{ $edit ? route('pasien.update', $edit) : route('pasien.store') }}">
            @csrf
            @if ($edit) @method('PUT') @endif
            <div class="obat-grid">
                <div>
                    <label for="kode_pasien">Kode pasien *</label>
                    <input id="kode_pasien" name="kode_pasien" required maxlength="20" pattern="[A-Za-z0-9-]{3,20}"
                           value="{{ old('kode_pasien', $edit?->kode_pasien ?? $kodeBaru) }}" placeholder="P-0001">
                </div>
                <div>
                    <label for="nama">Nama lengkap *</label>
                    <input id="nama" name="nama" required maxlength="100" value="{{ old('nama', $edit?->nama) }}" placeholder="Nama lengkap">
                </div>
                <div>
                    <label for="nik">NIK</label>
                    <input id="nik" name="nik" maxlength="16" inputmode="numeric" pattern="[0-9]{16}"
                           value="{{ old('nik', $edit?->nik) }}" placeholder="16 digit">
                </div>
                <div>
                    <label for="tanggal_lahir">Tanggal lahir</label>
                    <input id="tanggal_lahir" type="date" name="tanggal_lahir" max="{{ now()->toDateString() }}"
                           value="{{ old('tanggal_lahir', $edit?->tanggal_lahir?->format('Y-m-d')) }}">
                </div>
                <div>
                    <label for="jenis_kelamin">Jenis kelamin</label>
                    @php $jk = old('jenis_kelamin', $edit?->jenis_kelamin); @endphp
                    <select id="jenis_kelamin" name="jenis_kelamin">
                        <option value="">-- Pilih --</option>
                        <option value="L" @selected($jk === 'L')>Laki-laki</option>
                        <option value="P" @selected($jk === 'P')>Perempuan</option>
                    </select>
                </div>
                <div>
                    <label for="no_hp">No. HP</label>
                    <input id="no_hp" name="no_hp" maxlength="20" value="{{ old('no_hp', $edit?->no_hp) }}" placeholder="08xx-xxxx-xxxx">
                </div>
            </div>
            <label for="alamat">Alamat</label>
            <textarea id="alamat" name="alamat" rows="2" maxlength="255" placeholder="Alamat domisili">{{ old('alamat', $edit?->alamat) }}</textarea>
            <p class="teks-bantu">* wajib diisi. Kode pasien 3–20 karakter (huruf, angka, tanda hubung); NIK 16 digit dan tidak boleh sama dengan pasien lain.</p>
            <button type="submit">{{ $edit ? 'Simpan Perubahan' : 'Simpan Pasien' }}</button>
            @if ($edit)
                <a class="btn-garis" href="{{ route('pasien.index') }}">Batal</a>
            @endif
        </form>
    </section>
    @endcan

    <section class="obat-card">
        <h3>Daftar Pasien</h3>
        <form class="obat-cari" method="GET" action="{{ route('pasien.index') }}">
            <input name="cari" value="{{ $cari }}" placeholder="Cari kode, nama, atau NIK pasien">
            <button type="submit">Cari</button>
        </form>
        <div class="obat-scroll">
            <table class="obat-table">
                <thead><tr><th>No.</th><th>Kode</th><th>Nama</th><th>NIK</th><th>JK / Umur</th><th>No. HP</th><th>Alamat</th>@can('pasien.kelola')<th>Aksi</th>@endcan</tr></thead>
                <tbody>
                @forelse ($daftar as $p)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>
                            <strong>{{ $p->kode_pasien }}</strong>
                            @if ($p->identitasAsal)
                                <br><small>dari {{ $p->identitasAsal->kode_sementara }}</small>
                            @endif
                        </td>
                        <td>{{ $p->nama }}</td>
                        <td>{{ $p->nik ?: '-' }}</td>
                        <td>{{ $p->labelJenisKelamin() }}{{ $p->umur() !== null ? ' / ' . $p->umur() . ' th' : '' }}</td>
                        <td>{{ $p->no_hp ?: '-' }}</td>
                        <td>{{ $p->alamat ?: '-' }}</td>
                        @can('pasien.kelola')<td><a class="btn-garis btn-kecil" href="{{ route('pasien.index', ['edit' => $p->id]) }}">Edit</a></td>@endcan
                    </tr>
                @empty
                    <tr><td colspan="8">{{ $cari !== '' ? 'Pasien tidak ditemukan.' : 'Belum ada data pasien.' }}</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection

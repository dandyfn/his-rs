@extends('layouts.app')
@section('title', 'Identitas Pasien')

@section('content')
    <h2>Pendaftaran dan Verifikasi Identitas Pasien</h2>
    <p class="teks-bantu">
        Modul ini mencatat identitas sementara, status verifikasi,
        rekomendasi tindak lanjut berbasis aturan, dan riwayat perubahan.
    </p>

    <section class="obat-card">
        <h3>{{ $edit ? 'Lengkapi / Perbarui Identitas ' . $edit->kode_sementara : 'Daftarkan Identitas Sementara' }}</h3>
        @if ($edit && $edit->status_verifikasi === 'terverifikasi')
            <p class="teks-bantu">Identitas ini sudah terverifikasi. Jika datanya diubah, status kembali menjadi <strong>perlu konfirmasi</strong>.</p>
        @endif
        <form method="POST" action="{{ $edit ? route('identitas.update', $edit) : route('identitas.store') }}">
            @csrf
            @if ($edit) @method('PUT') @endif
            <div class="obat-grid">
                <div>
                    <label for="nama_sementara">Nama sementara *</label>
                    <input id="nama_sementara" name="nama_sementara" required maxlength="100"
                           value="{{ old('nama_sementara', $edit?->nama_sementara) }}" placeholder="Nama yang diketahui saat ini">
                </div>
                <div>
                    <label for="tanggal_lahir">Tanggal lahir (jika diketahui)</label>
                    <input id="tanggal_lahir" type="date" name="tanggal_lahir" max="{{ now()->toDateString() }}"
                           value="{{ old('tanggal_lahir', $edit?->tanggal_lahir?->format('Y-m-d')) }}">
                </div>
                <div>
                    <label for="nomor_identitas">Nomor identitas / NIK (jika diketahui)</label>
                    <input id="nomor_identitas" name="nomor_identitas" maxlength="50" value="{{ old('nomor_identitas', $edit?->nomor_identitas) }}">
                </div>
                <div>
                    <label for="kontak">Kontak (jika diketahui)</label>
                    <input id="kontak" name="kontak" maxlength="30" value="{{ old('kontak', $edit?->kontak) }}">
                </div>
            </div>
            <p class="teks-bantu">
                Gunakan data fiktif untuk pengujian. Data pasien nyata hanya boleh digunakan
                sesuai izin dan perlindungan yang berlaku.
            </p>
            <button type="submit">{{ $edit ? 'Simpan Perubahan' : 'Simpan Identitas Sementara' }}</button>
            @if ($edit)
                <a class="btn-garis" href="{{ route('identitas.index') }}">Batal</a>
            @endif
        </form>
    </section>

    <section class="obat-card">
        <h3>Daftar Identitas dan Rekomendasi Tindak Lanjut</h3>
        <form class="obat-cari" method="GET" action="{{ route('identitas.index') }}">
            <input name="cari" value="{{ $cari }}" placeholder="Cari nama, kode, atau nomor identitas">
            <button type="submit">Cari</button>
        </form>
        <div class="obat-scroll">
            <table class="obat-table">
                <thead>
                    <tr><th>Kode / Identitas</th><th>Informasi</th><th>Status</th><th>Rekomendasi dan Riwayat</th><th>Tindakan</th></tr>
                </thead>
                <tbody>
                @forelse ($daftar as $r)
                    <tr>
                        <td>
                            <strong>{{ $r->kode_sementara }}</strong><br>
                            {{ $r->nama_sementara }}<br>
                            <small>{{ $r->created_at->format('d/m/Y H:i') }}</small>
                        </td>
                        <td>
                            Tanggal lahir: {{ $r->tanggal_lahir?->format('d/m/Y') ?? 'Belum tersedia' }}<br>
                            Nomor identitas: {{ $r->nomor_identitas ?: 'Belum tersedia' }}<br>
                            Kontak: {{ $r->kontak ?: 'Belum tersedia' }}
                        </td>
                        <td>
                            <span class="badge {{ $r->kelasStatus() }}">{{ $r->labelStatus() }}</span>
                            @if ($r->pasien)
                                <br><small>→ {{ $r->pasien->kode_pasien }} - {{ $r->pasien->nama }}</small>
                            @endif
                            @if ($r->catatan_verifikasi)
                                <br><small>{{ $r->catatan_verifikasi }}</small>
                            @endif
                        </td>
                        <td>
                            {{ $r->rekomendasi() }}
                            <details>
                                <summary>Riwayat perubahan</summary>
                                @if ($r->riwayat->isEmpty())
                                    <p class="teks-bantu">Belum ada riwayat.</p>
                                @else
                                    <ul>
                                        @foreach ($r->riwayat->take(5) as $h)
                                            <li>{{ $h->created_at->format('d/m/Y H:i') }} — {{ $h->aksi }}<br>{{ $h->detail }}</li>
                                        @endforeach
                                    </ul>
                                @endif
                            </details>
                        </td>
                        <td>
                            @if ($r->pasien_id)
                                <span class="teks-bantu">Selesai. Lihat di <a href="{{ route('pasien.index', ['cari' => $r->pasien->kode_pasien]) }}">Data Pasien</a>.</span>
                            @else
                                <a class="btn-garis btn-kecil" href="{{ route('identitas.index', ['edit' => $r->id]) }}" style="margin-bottom:6px;display:inline-block">Edit / Lengkapi</a>
                                <form method="POST" action="{{ route('identitas.tindakan', $r) }}">
                                    @csrf
                                    <input name="catatan_verifikasi" maxlength="2000" placeholder="Catatan petugas" style="margin-bottom:6px">
                                    <div class="aksi-baris">
                                        @if ($r->status_verifikasi !== 'terverifikasi')
                                            <button name="aksi" value="verifikasi" class="btn-kecil">Tandai Terverifikasi</button>
                                        @endif
                                        <button name="aksi" value="konfirmasi" class="btn-kecil btn-kuning">Perlu Konfirmasi</button>
                                    </div>
                                </form>
                                @if ($r->status_verifikasi === 'terverifikasi')
                                    <form method="POST" action="{{ route('identitas.jadikan-pasien', $r) }}" class="form-jadikan">
                                        @csrf
                                        <label for="pasien_id_{{ $r->id }}">Jadikan pasien tetap</label>
                                        <select id="pasien_id_{{ $r->id }}" name="pasien_id">
                                            <option value="">+ Buat pasien baru ({{ \App\Models\Pasien::kodeBerikutnya() }})</option>
                                            @foreach ($kandidat[$r->id] ?? [] as $k)
                                                <option value="{{ $k->id }}">⚠ Cocok ({{ $k->alasan_cocok }}): {{ $k->kode_pasien }} - {{ $k->nama }}</option>
                                            @endforeach
                                            <optgroup label="Kaitkan ke pasien yang sudah ada">
                                                @foreach ($semuaPasien as $p)
                                                    <option value="{{ $p->id }}">{{ $p->kode_pasien }} - {{ $p->nama }}</option>
                                                @endforeach
                                            </optgroup>
                                        </select>
                                        @if (($kandidat[$r->id] ?? collect())->isNotEmpty())
                                            <small class="teks-peringatan">⚠ Mungkin orang yang sama:
                                                @foreach ($kandidat[$r->id] as $k){{ $k->kode_pasien }} {{ $k->nama }} ({{ $k->alasan_cocok }}){{ $loop->last ? '' : ', ' }}@endforeach.
                                                Periksa sebelum membuat pasien baru.</small>
                                        @endif
                                        <button type="submit" class="btn-kecil">Jadikan Pasien Tetap</button>
                                    </form>
                                @endif
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5">{{ $cari !== '' ? 'Identitas tidak ditemukan.' : 'Belum ada identitas sementara.' }}</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection

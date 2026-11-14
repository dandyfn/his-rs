@extends('layouts.app')
@section('title', 'Identitas Pasien')

@section('content')
    <h2>Pendaftaran dan Verifikasi Identitas Pasien</h2>
    <p class="teks-bantu">
        Modul ini mencatat identitas sementara, status verifikasi,
        rekomendasi tindak lanjut berbasis aturan, dan riwayat perubahan.
    </p>

    <section class="obat-card">
        <h3>Daftarkan Identitas Sementara</h3>
        <form method="POST" action="{{ route('identitas.store') }}">
            @csrf
            <div class="obat-grid">
                <div>
                    <label>Nama sementara *</label>
                    <input name="nama_sementara" required maxlength="100" value="{{ old('nama_sementara') }}" placeholder="Nama yang diketahui saat ini">
                </div>
                <div>
                    <label>Tanggal lahir (jika diketahui)</label>
                    <input type="date" name="tanggal_lahir" value="{{ old('tanggal_lahir') }}">
                </div>
                <div>
                    <label>Nomor identitas (jika diketahui)</label>
                    <input name="nomor_identitas" maxlength="50" value="{{ old('nomor_identitas') }}">
                </div>
                <div>
                    <label>Kontak (jika diketahui)</label>
                    <input name="kontak" maxlength="30" value="{{ old('kontak') }}">
                </div>
            </div>
            <p class="teks-bantu">
                Gunakan data fiktif untuk pengujian. Data pasien nyata hanya boleh digunakan
                sesuai izin dan perlindungan yang berlaku.
            </p>
            <button type="submit">Simpan Identitas Sementara</button>
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
                            <span class="badge {{ $r->kelasStatus() }}">{{ $r->status_verifikasi }}</span>
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
                            <form method="POST" action="{{ route('identitas.tindakan', $r) }}">
                                @csrf
                                <input name="catatan_verifikasi" maxlength="2000" placeholder="Catatan petugas" style="margin-bottom:6px">
                                <div class="aksi-baris">
                                    <button name="aksi" value="verifikasi" class="btn-kecil">Tandai Terverifikasi</button>
                                    <button name="aksi" value="konfirmasi" class="btn-kecil btn-kuning">Perlu Konfirmasi</button>
                                </div>
                            </form>
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

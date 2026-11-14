@extends('layouts.app')
@section('title', 'Laporan Antrean')

@section('content')
    <h2>Laporan Antrean</h2>
    <p class="teks-bantu">Rekap antrean berdasarkan rentang tanggal dan poli. Hasil dapat diekspor ke CSV (Excel).</p>

    <section class="obat-card">
        <h3>Filter Laporan</h3>
        <form method="GET" action="{{ route('laporan-antrean.index') }}">
            <div class="obat-grid">
                <div><label for="tanggal_awal">Tanggal awal</label><input type="date" id="tanggal_awal" name="tanggal_awal" value="{{ $awal }}"></div>
                <div><label for="tanggal_akhir">Tanggal akhir</label><input type="date" id="tanggal_akhir" name="tanggal_akhir" value="{{ $akhir }}"></div>
                <div>
                    <label for="poli_id">Poli</label>
                    <select id="poli_id" name="poli_id">
                        <option value="">Semua poli</option>
                        @foreach ($daftarPoli as $p)
                            <option value="{{ $p->id }}" @selected($poliId === $p->id)>{{ $p->nama_poli }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <p class="teks-bantu"></p>
            <div class="aksi-baris">
                <button type="submit">Tampilkan</button>
                <a class="btn-garis" href="{{ route('laporan-antrean.ekspor', ['tanggal_awal' => $awal, 'tanggal_akhir' => $akhir, 'poli_id' => $poliId]) }}">Ekspor CSV</a>
            </div>
        </form>
    </section>

    <section class="obat-card">
        <h3>Ringkasan</h3>
        <div class="obat-ringkasan">
            <div class="obat-stat"><div class="obat-stat-label">Total antrean</div><div class="obat-angka">{{ $ringkasan['total'] }}</div></div>
            <div class="obat-stat"><div class="obat-stat-label">Selesai</div><div class="obat-angka angka-hijau">{{ $ringkasan['selesai'] }}</div></div>
            <div class="obat-stat"><div class="obat-stat-label">Batal</div><div class="obat-angka angka-merah">{{ $ringkasan['batal'] }}</div></div>
            <div class="obat-stat"><div class="obat-stat-label">Masih aktif</div><div class="obat-angka angka-kuning">{{ $ringkasan['menunggu'] + $ringkasan['dipanggil'] + $ringkasan['dilayani'] }}</div></div>
            <div class="obat-stat"><div class="obat-stat-label">Rata-rata waktu tunggu</div><div class="obat-angka">{{ $rataTunggu === null ? '-' : $rataTunggu . ' mnt' }}</div></div>
        </div>
    </section>

    <section class="obat-card">
        <h3>Detail Antrean</h3>
        <div class="obat-scroll">
            <table class="obat-table">
                <thead><tr><th>Tanggal</th><th>No. Antrean</th><th>Nama Pasien</th><th>Poli</th><th>Status</th><th>Lama Tunggu</th></tr></thead>
                <tbody>
                @forelse ($data as $a)
                    <tr>
                        <td>{{ $a->tanggal_kunjungan->format('d/m/Y') }}</td>
                        <td>{{ $a->nomor_antrean }}</td>
                        <td>{{ $a->pasien->nama }}</td>
                        <td>{{ $a->poli->nama_poli }}</td>
                        <td><span class="badge {{ $a->kelasStatus() }}">{{ $a->status }}</span></td>
                        <td>{{ $a->menitTunggu() === null ? '-' : $a->menitTunggu() . ' menit' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6">Tidak ada antrean pada rentang tanggal ini.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection

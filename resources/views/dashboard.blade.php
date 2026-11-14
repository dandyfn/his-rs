@extends('layouts.app')
@section('title', 'Dashboard')

@section('content')
    <h2>Dashboard</h2>
    <p class="teks-bantu">Selamat datang, {{ auth()->user()->name }}. Ringkasan ketiga subsistem hari ini, {{ today()->translatedFormat('l, d F Y') }}.</p>

    <section class="obat-card">
        <h3>Ringkasan Hari Ini</h3>
        <div class="obat-ringkasan">
            <div class="obat-stat"><div class="obat-stat-label">Pasien terdaftar</div><div class="obat-angka">{{ $totalPasien }}</div></div>
            <div class="obat-stat"><div class="obat-stat-label">Identitas belum terverifikasi</div><div class="obat-angka angka-kuning">{{ $identitasBelumVerif }}</div></div>
            <div class="obat-stat"><div class="obat-stat-label">Antrean hari ini</div><div class="obat-angka">{{ $antrean['total'] }}</div></div>
            <div class="obat-stat"><div class="obat-stat-label">Antrean menunggu</div><div class="obat-angka angka-kuning">{{ $antrean['menunggu'] }}</div></div>
            <div class="obat-stat"><div class="obat-stat-label">Obat perlu dipesan</div><div class="obat-angka angka-merah">{{ $obatPerluDipesan }}</div></div>
            <div class="obat-stat"><div class="obat-stat-label">Penjualan obat hari ini</div><div class="obat-angka">Rp {{ number_format($omzetHariIni, 0, ',', '.') }}</div></div>
        </div>
    </section>

    <section class="obat-card">
        <h3>Progres Pelayanan Hari Ini</h3>
        @php $persen = $antrean['total'] > 0 ? round($antrean['selesai'] / $antrean['total'] * 100) : 0; @endphp
        <p class="teks-bantu">{{ $antrean['selesai'] }} dari {{ $antrean['total'] }} antrean selesai dilayani ({{ $persen }}%) · Dipanggil {{ $antrean['dipanggil'] }} · Dilayani {{ $antrean['dilayani'] }} · Batal {{ $antrean['batal'] }}</p>
        <div class="bar"><span style="width: {{ $persen }}%"></span></div>
    </section>

    <section class="obat-card">
        <h3>Rekomendasi Berbasis Data</h3>
        @php $kelas = ['Tinggi' => 'badge-merah', 'Sedang' => 'badge-kuning', 'Normal' => 'badge-hijau']; @endphp
        <div class="grid-rekom">
            @foreach ($rekomAntrean as $r)
                <div class="kartu-rekom">
                    <span class="badge {{ $kelas[$r['prioritas']] }}">Antrean · {{ $r['prioritas'] }}</span>
                    <h4>{{ $r['poli']->nama_poli }}: {{ $r['judul'] }}</h4>
                    <p>{{ $r['saran'] }}</p>
                </div>
            @endforeach
            @foreach ($obatMendesak as $r)
                <div class="kartu-rekom">
                    <span class="badge badge-merah">Obat · Pesan sekarang</span>
                    <h4>{{ $r['obat']->nama_obat }}</h4>
                    <p>Stok {{ $r['obat']->stok }} {{ $r['obat']->satuan }}, perkiraan habis dalam {{ $r['habis_hari'] }} hari. Saran pesan {{ $r['saran'] }} {{ $r['obat']->satuan }}.
                        <a href="{{ route('pembelian.index', ['obat_id' => $r['obat']->id, 'jumlah' => $r['saran']]) }}">Buat pembelian</a></p>
                </div>
            @endforeach
            @if ($identitasBelumVerif > 0)
                <div class="kartu-rekom">
                    <span class="badge badge-kuning">Pendaftaran</span>
                    <h4>{{ $identitasBelumVerif }} identitas belum terverifikasi</h4>
                    <p>Lengkapi data dan cocokkan dengan dokumen pendukung. <a href="{{ route('identitas.index') }}">Buka halaman identitas</a></p>
                </div>
            @endif
        </div>
    </section>

    <section class="obat-card">
        <h3>Antrean Hari Ini</h3>
        <div class="obat-scroll">
            <table class="obat-table">
                <thead><tr><th>No. Antrean</th><th>Kode Pasien</th><th>Nama Pasien</th><th>Poli</th><th>Status</th></tr></thead>
                <tbody>
                @forelse ($antreanHariIni as $a)
                    <tr>
                        <td>{{ $a->nomor_antrean }}</td>
                        <td>{{ $a->pasien->kode_pasien }}</td>
                        <td>{{ $a->pasien->nama }}</td>
                        <td>{{ $a->poli->nama_poli }}</td>
                        <td><span class="badge {{ $a->kelasStatus() }}">{{ $a->status }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="5">Belum ada antrean hari ini.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <p class="teks-bantu"><a href="{{ route('antrean.index') }}">Kelola antrean</a></p>
    </section>
@endsection

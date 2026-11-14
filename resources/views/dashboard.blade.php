@extends('layouts.app')
@section('title', 'Dashboard')

@section('content')
    <h2>Dashboard</h2>
    <p class="teks-bantu">Selamat datang, {{ auth()->user()->name }}. Ringkasan kondisi subsistem obat hari ini.</p>

    <section class="obat-card">
        <h3>Ringkasan</h3>
        <div class="obat-ringkasan">
            <div class="obat-stat"><div class="obat-stat-label">Jenis obat</div><div class="obat-angka">{{ $jumlahObat }}</div></div>
            <div class="obat-stat"><div class="obat-stat-label">Obat perlu dipesan</div><div class="obat-angka angka-merah">{{ $perluDipesan }}</div></div>
            <div class="obat-stat"><div class="obat-stat-label">Penjualan hari ini</div><div class="obat-angka">{{ $penjualanHariIni }}</div></div>
            <div class="obat-stat"><div class="obat-stat-label">Total penjualan hari ini</div><div class="obat-angka">Rp {{ number_format($omzetHariIni, 0, ',', '.') }}</div></div>
        </div>
    </section>

    <section class="obat-card">
        <h3>Obat yang Perlu Segera Dipesan</h3>
        <div class="obat-scroll">
            <table class="obat-table">
                <thead><tr><th>Obat</th><th>Stok</th><th>Perkiraan habis</th><th>Saran pesan</th><th></th></tr></thead>
                <tbody>
                @forelse ($mendesak as $r)
                    <tr>
                        <td>{{ $r['obat']->nama_obat }}</td>
                        <td>{{ $r['obat']->stok }} {{ $r['obat']->satuan }}</td>
                        <td>{{ $r['habis_hari'] }} hari</td>
                        <td><strong>{{ $r['saran'] }} {{ $r['obat']->satuan }}</strong></td>
                        <td><a class="btn-pesan" href="{{ route('pembelian.index', ['obat_id' => $r['obat']->id, 'jumlah' => $r['saran']]) }}">Buat pembelian</a></td>
                    </tr>
                @empty
                    <tr><td colspan="5">Tidak ada obat yang perlu dipesan saat ini.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <p class="teks-bantu"><a href="{{ route('rekomendasi.index') }}">Lihat analisis lengkap di halaman Rekomendasi Obat</a></p>
    </section>
@endsection

@extends('layouts.app')
@section('title', 'Rekomendasi Pengadaan Obat')

@section('content')
    <h2>Analisis dan Rekomendasi Pengadaan Obat</h2>
    <p class="teks-bantu">
        Saran waktu dan jumlah pemesanan dihitung dari data pemakaian obat
        {{ $periode }} hari terakhir. Keputusan akhir tetap di tangan apoteker.
    </p>

    <form class="obat-filter" method="GET" action="{{ route('rekomendasi.index') }}">
        <label for="periode"><strong>Periode analisis:</strong></label>
        <select name="periode" id="periode" onchange="this.form.submit()">
            @foreach ([30, 60, 90] as $p)
                <option value="{{ $p }}" @selected($p === $periode)>{{ $p }} hari</option>
            @endforeach
        </select>
    </form>

    <section class="obat-card">
        <h3>Ringkasan Status Stok</h3>
        <div class="obat-ringkasan">
            <div class="obat-stat"><div class="obat-stat-label">Perlu dipesan</div><div class="obat-angka angka-merah">{{ $jumlah['pesan'] ?? 0 }}</div></div>
            <div class="obat-stat"><div class="obat-stat-label">Perlu dipantau</div><div class="obat-angka angka-kuning">{{ $jumlah['pantau'] ?? 0 }}</div></div>
            <div class="obat-stat"><div class="obat-stat-label">Aman</div><div class="obat-angka angka-hijau">{{ $jumlah['aman'] ?? 0 }}</div></div>
            <div class="obat-stat"><div class="obat-stat-label">Belum ada data pemakaian</div><div class="obat-angka">{{ $jumlah['nodata'] ?? 0 }}</div></div>
        </div>
    </section>

    @php
        $label = [
            'pesan' => ['Pesan sekarang', 'badge-merah'],
            'pantau' => ['Pantau', 'badge-kuning'],
            'aman' => ['Aman', 'badge-hijau'],
            'nodata' => ['Belum ada data', 'badge-abu'],
        ];
    @endphp

    <section class="obat-card">
        <h3>Rekomendasi per Obat</h3>
        <div class="obat-scroll">
            <table class="obat-table">
                <thead>
                    <tr><th>Obat</th><th>Stok</th><th>Pakai/hari</th><th>Titik pesan ulang</th><th>Perkiraan habis</th><th>Status</th><th>Saran pesan</th><th></th></tr>
                </thead>
                <tbody>
                @forelse ($hasil as $r)
                    @php([$teks, $kelas] = $label[$r['status']])
                    <tr>
                        <td><strong>{{ $r['obat']->nama_obat }}</strong><br><small>{{ $r['obat']->kode_obat }}</small></td>
                        <td>{{ $r['obat']->stok }} {{ $r['obat']->satuan }}</td>
                        <td>{{ number_format($r['pakai_per_hari'], 1, ',', '.') }}</td>
                        <td>{{ $r['status'] === 'nodata' ? '-' : $r['titik_pesan'] }}</td>
                        <td>{{ $r['habis_hari'] === null ? '-' : $r['habis_hari'] . ' hari' }}</td>
                        <td><span class="badge {{ $kelas }}">{{ $teks }}</span></td>
                        <td>
                            @if ($r['saran'] > 0)
                                <strong>{{ $r['saran'] }} {{ $r['obat']->satuan }}</strong>
                            @else
                                Belum perlu
                            @endif
                        </td>
                        <td>
                            @if ($r['saran'] > 0)
                                <a class="btn-pesan" href="{{ route('pembelian.index', ['obat_id' => $r['obat']->id, 'jumlah' => $r['saran']]) }}">Buat pembelian</a>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8">Belum ada data obat.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <p class="teks-bantu">
            Rumus: titik pesan ulang = (pakai/hari &times; lama kirim supplier) + stok pengaman.
            Jumlah pesan = pakai/hari &times; (lama kirim + jarak antar pesan) + stok pengaman &minus; stok saat ini.
        </p>
    </section>
@endsection

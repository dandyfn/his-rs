@extends('layouts.app')
@section('title', 'Rekomendasi Antrean')

@section('content')
    <h2>Rekomendasi Antrean</h2>
    <p class="teks-bantu">
        Rekomendasi operasional per poli berdasarkan data antrean hari ini: jumlah antrean,
        status pelayanan, kapasitas, dan rata-rata waktu tunggu (dari daftar sampai mulai dilayani).
    </p>

    @php
        $kelas = ['Tinggi' => 'badge-merah', 'Sedang' => 'badge-kuning', 'Normal' => 'badge-hijau'];
    @endphp

    <section class="obat-card">
        <h3>Rekomendasi per Poli</h3>
        <div class="obat-scroll">
            <table class="obat-table">
                <thead><tr><th>Poli</th><th>Total Antrean</th><th>Menunggu</th><th>Dipanggil</th><th>Dilayani</th><th>Rata-rata Tunggu</th><th>Prioritas</th><th>Rekomendasi</th></tr></thead>
                <tbody>
                @forelse ($hasil as $r)
                    <tr>
                        <td><strong>{{ $r['poli']->nama_poli }}</strong><br><small>{{ $r['total'] }}/{{ $r['poli']->batas_antrean }} ({{ $r['kapasitas'] ?? 0 }}% kapasitas)</small></td>
                        <td>{{ $r['total'] }}</td>
                        <td>{{ $r['menunggu'] }}</td>
                        <td>{{ $r['dipanggil'] }}</td>
                        <td>{{ $r['dilayani'] }}</td>
                        <td>{{ $r['rata_tunggu'] === null ? '-' : $r['rata_tunggu'] . ' menit' }}</td>
                        <td><span class="badge {{ $kelas[$r['prioritas']] }}">{{ $r['prioritas'] }}</span></td>
                        <td>{{ $r['saran'] }}</td>
                    </tr>
                @empty
                    <tr><td colspan="8">Belum ada poli aktif.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <p class="teks-bantu">
            Aturan: kapasitas tercapai atau ≥ 5 antrean menunggu → prioritas Tinggi;
            3–4 antrean menunggu → Sedang; selain itu Normal.
        </p>
    </section>
@endsection

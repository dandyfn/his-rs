@extends('layouts.app')
@section('title', 'Rekomendasi Antrean')

@section('content')
    <h2>Rekomendasi Antrean</h2>
    <p class="teks-bantu">
        Rekomendasi operasional per poli berdasarkan data antrean hari ini, dibandingkan dengan riwayat
        {{ \App\Services\PrediksiAntreanService::MINGGU }} minggu terakhir: beban dibanding hari yang sama
        dan perkiraan waktu tunggu pasien yang baru mendaftar.
    </p>

    @php
        $kelas = ['Tinggi' => 'badge-merah', 'Sedang' => 'badge-kuning', 'Normal' => 'badge-hijau'];
    @endphp

    <section class="obat-card">
        <h3>Rekomendasi per Poli</h3>
        <div class="obat-scroll">
            <table class="obat-table">
                <thead>
                    <tr>
                        <th>Poli</th><th>Menunggu</th><th>Dipanggil</th><th>Dilayani</th>
                        <th>Rata-rata tunggu hari ini</th><th>Beban dibanding biasa</th><th>Perkiraan tunggu pasien baru</th>
                        <th>Prioritas</th><th>Rekomendasi</th>
                    </tr>
                </thead>
                <tbody>
                @forelse ($hasil as $r)
                    @php $p = $r['prediksi']; @endphp
                    <tr>
                        <td><strong>{{ $r['poli']->nama_poli }}</strong><br><small>{{ $r['total'] }}/{{ $r['poli']->batas_antrean }} ({{ $r['kapasitas'] ?? 0 }}% kapasitas)</small></td>
                        <td>{{ $r['menunggu'] }}</td>
                        <td>{{ $r['dipanggil'] }}</td>
                        <td>{{ $r['dilayani'] }}</td>
                        <td>{{ $r['rata_tunggu'] === null ? '-' : $r['rata_tunggu'] . ' menit' }}</td>
                        <td>
                            @if ($p['biasa_sampai_jam'] === null)
                                <span class="teks-bantu">Data riwayat belum cukup ({{ $p['hari_pembanding'] }} dari {{ \App\Services\PrediksiAntreanService::HARI_MINIMAL }} hari {{ $p['hari'] }})</span>
                            @else
                                {{ $p['jumlah_hari_ini'] }} vs biasa {{ rtrim(rtrim(number_format($p['biasa_sampai_jam'], 1, ',', '.'), '0'), ',') }}
                                @if ($p['selisih_persen'] !== null)
                                    <span @class(['badge', 'badge-merah' => $p['ramai'], 'badge-abu' => ! $p['ramai']])>{{ $p['selisih_persen'] > 0 ? '+' : '' }}{{ $p['selisih_persen'] }}%</span>
                                @endif
                                <br><small>sampai jam ini · biasanya {{ rtrim(rtrim(number_format($p['biasa_sehari'], 1, ',', '.'), '0'), ',') }} sehari ({{ $p['hari_pembanding'] }} hari {{ $p['hari'] }} terakhir)</small>
                            @endif
                        </td>
                        <td>
                            @if ($p['tunggu_baru'] === null)
                                <span class="teks-bantu">Data riwayat belum cukup</span>
                            @else
                                {{ $p['tunggu_baru'] === 0 ? 'Segera dilayani' : '± ' . $p['tunggu_baru'] . ' menit' }}
                                <br><small>{{ $p['di_depan'] }} antrean di depan × {{ $p['lama_layanan'] }} menit</small>
                            @endif
                        </td>
                        <td><span class="badge {{ $kelas[$r['prioritas']] }}">{{ $r['prioritas'] }}</span></td>
                        <td>{{ $r['saran'] }}</td>
                    </tr>
                @empty
                    <tr><td colspan="9">Belum ada poli aktif.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <p class="teks-bantu">
            Aturan prioritas: kapasitas tercapai atau ≥ 5 antrean menunggu → Tinggi; 3–4 antrean menunggu → Sedang; selain itu Normal.
            Poli yang ≥ {{ \App\Services\PrediksiAntreanService::AMBANG_RAMAI }}% lebih ramai dari biasanya (dan minimal
            {{ \App\Services\PrediksiAntreanService::ANTREAN_MINIMAL_RAMAI }} antrean) dinaikkan satu tingkat.
        </p>
    </section>

    <section class="obat-card">
        <h3>Cara menghitung prediksi</h3>
        <div class="obat-scroll">
            <table class="obat-table">
                <thead><tr><th>Angka</th><th>Cara menghitung</th><th>Syarat data</th></tr></thead>
                <tbody>
                    <tr>
                        <td><strong>Lama layanan biasa</strong></td>
                        <td>Median lama pelayanan (mulai dilayani sampai selesai) poli tersebut, {{ \App\Services\PrediksiAntreanService::MINGGU }} minggu terakhir.</td>
                        <td>Minimal {{ \App\Services\PrediksiAntreanService::LAYANAN_MINIMAL }} pelayanan selesai.</td>
                    </tr>
                    <tr>
                        <td><strong>Perkiraan tunggu pasien baru</strong></td>
                        <td>Jumlah antrean di depan (menunggu, dipanggil, dilayani) × lama layanan biasa.</td>
                        <td>Sama dengan lama layanan biasa.</td>
                    </tr>
                    <tr>
                        <td><strong>Beban dibanding biasa</strong></td>
                        <td>Jumlah antrean hari ini dibandingkan median jumlah antrean pada hari yang sama di {{ \App\Services\PrediksiAntreanService::MINGGU }} minggu terakhir, dihitung sampai jam yang sama.</td>
                        <td>Minimal {{ \App\Services\PrediksiAntreanService::HARI_MINIMAL }} hari pembanding yang tercatat.</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <p class="teks-bantu">
            Median adalah nilai tengah setelah data diurutkan, sehingga satu hari atau satu pasien yang luar biasa tidak membuat hasilnya melenceng.
            Jika data belum cukup, halaman menulis "data riwayat belum cukup" dan prioritas hanya memakai aturan hari ini.
        </p>
    </section>
@endsection

@extends('layouts.app')
@section('title', 'Analisis Pemakaian Obat')

@section('content')
    <h2>Analisis Pemakaian Obat</h2>
    <p class="teks-bantu">
        Tren pemakaian obat dari data penjualan (retur dan penyesuaian stok tidak dihitung).
        Data ini menjadi dasar <a href="{{ route('rekomendasi.index') }}">Rekomendasi Pengadaan</a>.
    </p>

    <form class="obat-filter" method="GET" action="{{ route('analisis.index') }}">
        <label for="bulan"><strong>Periode:</strong></label>
        <select name="bulan" id="bulan" onchange="this.form.submit()">
            @foreach (\App\Services\AnalisisPemakaianService::PILIHAN_BULAN as $b)
                <option value="{{ $b }}" @selected($b === $bulan)>{{ $b }} bulan terakhir</option>
            @endforeach
        </select>
        <label for="obat_id"><strong>Obat:</strong></label>
        <select name="obat_id" id="obat_id" onchange="this.form.submit()">
            <option value="">Semua obat</option>
            @foreach ($daftarObat as $o)
                <option value="{{ $o->id }}" @selected($obatId === $o->id)>{{ $o->nama_obat }}</option>
            @endforeach
        </select>
    </form>

    <section class="obat-card">
        <h3>Ringkasan {{ $obatDipilih?->nama_obat ?? 'Semua Obat' }}</h3>
        <div class="obat-ringkasan">
            <div class="obat-stat"><div class="obat-stat-label">Total dipakai ({{ $bulan }} bulan)</div><div class="obat-angka">{{ number_format($total, 0, ',', '.') }}</div></div>
            <div class="obat-stat"><div class="obat-stat-label">Rata-rata per bulan</div><div class="obat-angka">{{ number_format($rataBulan, 0, ',', '.') }}</div></div>
            <div class="obat-stat">
                <div class="obat-stat-label">Tren {{ $tren ? $tren['akhir'] . ' vs ' . $tren['sebelum'] : 'bulanan' }}</div>
                <div @class(['obat-angka', 'angka-merah' => $tren && $tren['persen'] > 0, 'angka-hijau' => $tren && $tren['persen'] < 0])>
                    {{ $tren ? ($tren['persen'] > 0 ? '▲ ' : ($tren['persen'] < 0 ? '▼ ' : '')) . number_format(abs($tren['persen']), 1, ',', '.') . '%' : '-' }}
                </div>
            </div>
        </div>
    </section>

    {{-- FR-13: grafik batang pemakaian per bulan (SVG, tanpa library luar) --}}
    @php
        $maks = max(1, $perBulan->max('jumlah'));
        // pembulatan skala sumbu Y ke angka "rapi"
        $langkah = max(1, (int) pow(10, floor(log10($maks))));
        foreach ([1, 2, 5, 10] as $k) { if ($maks / ($langkah * $k) <= 5) { $langkah *= $k; break; } }
        $skala = (int) (ceil($maks / $langkah) * $langkah);
        $W = 760; $H = 280; $kiri = 48; $bawah = 32; $atas = 16;
        $lebarArea = $W - $kiri - 8; $tinggiArea = $H - $bawah - $atas;
        $n = $perBulan->count(); $slot = $lebarArea / max(1, $n); $lebarBatang = min(56, $slot * 0.6);
    @endphp
    <section class="obat-card">
        <h3>Pemakaian per Bulan — {{ $obatDipilih?->nama_obat ?? 'semua obat' }}</h3>
        <div class="grafik-wrap">
            <svg viewBox="0 0 {{ $W }} {{ $H }}" class="grafik" role="img"
                 aria-label="Grafik batang pemakaian obat per bulan, {{ $bulan }} bulan terakhir">
                @for ($v = 0; $v <= $skala; $v += $langkah)
                    @php $y = $atas + $tinggiArea - ($v / $skala) * $tinggiArea; @endphp
                    <line x1="{{ $kiri }}" x2="{{ $W - 8 }}" y1="{{ $y }}" y2="{{ $y }}" class="grafik-grid"/>
                    <text x="{{ $kiri - 8 }}" y="{{ $y + 4 }}" class="grafik-sumbu" text-anchor="end">{{ number_format($v, 0, ',', '.') }}</text>
                @endfor
                @foreach ($perBulan as $i => $b)
                    @php
                        $h = ($b['jumlah'] / $skala) * $tinggiArea;
                        $x = $kiri + $slot * $i + ($slot - $lebarBatang) / 2;
                        $y = $atas + $tinggiArea - $h;
                        $berjalan = $loop->last;
                    @endphp
                    <g @class(['grafik-batang-grup', 'puncak' => $b['jumlah'] > 0 && $b['jumlah'] === $perBulan->max('jumlah')])>
                        <rect x="{{ $kiri + $slot * $i }}" y="{{ $atas }}" width="{{ $slot }}" height="{{ $tinggiArea }}" fill="transparent"/>
                        @if ($h > 0)
                            <path class="grafik-batang {{ $berjalan ? 'berjalan' : '' }}"
                                  d="M{{ $x }},{{ $atas + $tinggiArea }} V{{ $y + min(4, $h) }} Q{{ $x }},{{ $y }} {{ $x + min(4, $h) }},{{ $y }} H{{ $x + $lebarBatang - min(4, $h) }} Q{{ $x + $lebarBatang }},{{ $y }} {{ $x + $lebarBatang }},{{ $y + min(4, $h) }} V{{ $atas + $tinggiArea }} Z"/>
                        @endif
                        <text x="{{ $x + $lebarBatang / 2 }}" y="{{ $y - 6 }}" class="grafik-nilai" text-anchor="middle">{{ number_format($b['jumlah'], 0, ',', '.') }}</text>
                        <text x="{{ $x + $lebarBatang / 2 }}" y="{{ $H - 10 }}" class="grafik-sumbu" text-anchor="middle">{{ $b['label'] }}</text>
                        <title>{{ $b['label'] }}: {{ number_format($b['jumlah'], 0, ',', '.') }} unit{{ $berjalan ? ' (bulan berjalan, belum lengkap)' : '' }}</title>
                    </g>
                @endforeach
                <line x1="{{ $kiri }}" x2="{{ $W - 8 }}" y1="{{ $atas + $tinggiArea }}" y2="{{ $atas + $tinggiArea }}" class="grafik-dasar"/>
            </svg>
        </div>
        <p class="teks-bantu">Batang paling kanan (lebih muda) adalah bulan berjalan dan belum lengkap. Arahkan kursor ke batang untuk melihat angkanya.</p>
    </section>

    <section class="obat-card">
        <h3>5 Obat Paling Banyak Dipakai ({{ $bulan }} bulan)</h3>
        @php $maksTop = max(1, $terbanyak->max('jumlah')); @endphp
        <div class="top-obat">
            @forelse ($terbanyak as $t)
                <div class="top-baris">
                    <span class="top-nama">{{ $t['obat']->nama_obat }}</span>
                    <span class="top-bar"><span style="width: {{ round($t['jumlah'] / $maksTop * 100) }}%"></span></span>
                    <span class="top-nilai">{{ number_format($t['jumlah'], 0, ',', '.') }} {{ $t['obat']->satuan }}</span>
                </div>
            @empty
                <p class="teks-bantu">Belum ada data pemakaian.</p>
            @endforelse
        </div>
    </section>

    @if ($ringkasan->isNotEmpty())
        <section class="obat-card">
            <h3>Tabel Pemakaian per Obat</h3>
            <div class="obat-scroll">
                <table class="obat-table">
                    <thead><tr><th>Obat</th><th>Total {{ $bulan }} bulan</th><th>Rata-rata / bulan</th><th>Bulan tertinggi</th><th>Stok sekarang</th><th></th></tr></thead>
                    <tbody>
                    @foreach ($ringkasan as $r)
                        <tr>
                            <td>{{ $r['obat']->nama_obat }}</td>
                            <td>{{ number_format($r['total'], 0, ',', '.') }} {{ $r['obat']->satuan }}</td>
                            <td>{{ number_format($r['rata_bulan'], 1, ',', '.') }}</td>
                            <td>{{ $r['puncak'] }}</td>
                            <td>{{ $r['obat']->stok }}</td>
                            <td><a class="btn-garis btn-kecil" href="{{ route('analisis.index', ['bulan' => $bulan, 'obat_id' => $r['obat']->id]) }}">Grafik</a></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    @endif

    <p><a class="btn-pesan" href="{{ route('rekomendasi.index') }}">Lihat Rekomendasi Pengadaan →</a></p>
@endsection

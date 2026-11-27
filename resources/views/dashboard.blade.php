@extends('layouts.app')
@section('title', 'Dashboard')

@php
    /*
     * Daftar "Perlu tindakan hari ini": semua rekomendasi dari ketiga subsistem
     * digabung jadi satu tabel, lalu diurutkan dari yang paling mendesak (kolom 'urut').
     * Datanya sama dengan versi lama; yang berubah hanya cara menampilkannya.
     */
    $tindakan = collect();

    if ($lihatObat) {
        foreach ($batchKedaluwarsa as $b) {
            $kedaluwarsa = $b->sudahKedaluwarsa();
            [$label] = $b->statusKedaluwarsa($batas);
            $tindakan->push([
                'urut' => $kedaluwarsa ? 1 : 4,
                'status' => $kedaluwarsa ? 'Kedaluwarsa' : 'Hampir kedaluwarsa',
                'kelas' => $kedaluwarsa ? 'badge-merah' : 'badge-kuning',
                'nama' => $b->obat->nama_obat,
                'kode' => $b->nomor_batch,
                'ket' => "{$b->sisa} {$b->obat->satuan}, ED {$b->tanggal_kedaluwarsa->format('d/m/Y')}"
                    . ($kedaluwarsa ? '. Pisahkan dari rak dan catat retur.' : " ({$label}). Jual lebih dulu (FEFO)."),
                'aksi' => $kedaluwarsa && auth()->user()->can('retur')
                    ? ['Catat retur', route('retur.index'), false]
                    : ['Lihat batch', route('batch.index'), false],
            ]);
        }
        foreach ($obatMendesak as $r) {
            $tindakan->push([
                'urut' => 2,
                'status' => 'Pesan sekarang',
                'kelas' => 'badge-merah',
                'nama' => $r['obat']->nama_obat,
                'kode' => null,
                'ket' => "Stok {$r['stok']} {$r['obat']->satuan}, habis ± {$r['habis_hari']} hari. Saran pesan {$r['saran']} {$r['obat']->satuan}.",
                'aksi' => auth()->user()->can('pengadaan.pesan')
                    ? ['Buat pesanan', route('pembelian.index', ['obat_id' => $r['obat']->id, 'jumlah' => $r['saran'], 'rekomendasi' => 1]), true]
                    : null,
            ]);
        }
    }

    if ($lihatAntrean) {
        $peta = ['Tinggi' => [3, 'badge-merah'], 'Sedang' => [5, 'badge-kuning'], 'Normal' => [7, 'badge-abu']];
        foreach ($rekomAntrean as $r) {
            [$urut, $kelas] = $peta[$r['prioritas']] ?? [7, 'badge-abu'];
            $tindakan->push([
                'urut' => $urut,
                'status' => 'Antrean · ' . $r['prioritas'],
                'kelas' => $kelas,
                'nama' => $r['poli']->nama_poli . ': ' . $r['judul'],
                'kode' => null,
                'ket' => $r['saran'],
                'aksi' => ['Buka antrean', route('antrean.index'), false],
            ]);
        }
    }

    if ($lihatPendaftaran && $identitasBelumVerif > 0) {
        $tindakan->push([
            'urut' => 6,
            'status' => 'Pendaftaran',
            'kelas' => 'badge-abu',
            'nama' => "{$identitasBelumVerif} identitas belum terverifikasi",
            'kode' => null,
            'ket' => 'Lengkapi data dan cocokkan dengan dokumen pendukung.',
            'aksi' => auth()->user()->can('identitas.lihat') ? ['Buka daftar', route('identitas.index'), false] : null,
        ]);
    }

    $tindakan = $tindakan->sortBy('urut')->values();

    // Pintasan ke halaman yang paling sering dipakai, sesuai izin peran.
    $pintasan = collect([
        ['Daftarkan pasien', 'pasien.index', 'pasien.kelola'],
        ['Antrean hari ini', 'antrean.index', 'antrean.lihat'],
        ['Penjualan obat', 'penjualan.index', 'penjualan.buat'],
        ['Pesanan & penerimaan barang', 'pembelian.index', 'pengadaan.lihat'],
        ['Rekomendasi pengadaan', 'rekomendasi.index', 'analisis.obat'],
        ['Laporan obat', 'laporan-obat.index', 'laporan.obat'],
    ])->filter(fn ($p) => auth()->user()->can($p[2]));
@endphp

@section('content')
    <div class="dash-kepala">
        <h2>Dashboard</h2>
        <p>{{ auth()->user()->name }} · {{ auth()->user()->labelPeran() }} · {{ today()->translatedFormat('l, d F Y') }}</p>
    </div>

    <section class="ringkas" aria-label="Ringkasan hari ini">
        <div class="obat-ringkasan">
            @if ($lihatPendaftaran)
                <div class="obat-stat"><div class="obat-stat-label">Pasien terdaftar</div><div class="obat-angka">{{ $totalPasien }}</div></div>
                <div class="obat-stat"><div class="obat-stat-label">Identitas belum terverifikasi</div><div class="obat-angka">{{ $identitasBelumVerif }}</div></div>
            @endif
            @if ($lihatAntrean)
                <div class="obat-stat"><div class="obat-stat-label">Antrean hari ini</div><div class="obat-angka">{{ $antrean['total'] }}</div></div>
                <div class="obat-stat"><div class="obat-stat-label">Antrean menunggu</div><div class="obat-angka">{{ $antrean['menunggu'] }}</div></div>
            @endif
            @if ($lihatObat)
                <div class="obat-stat"><div class="obat-stat-label">Obat perlu dipesan</div><div class="obat-angka">{{ $obatPerluDipesan }}</div></div>
                <div class="obat-stat"><div class="obat-stat-label">Batch kedaluwarsa / ≤ {{ $batas }} hari</div><div class="obat-angka">{{ $batchKedaluwarsa->count() }}</div></div>
                <div class="obat-stat"><div class="obat-stat-label">Pesanan menunggu barang</div><div class="obat-angka">{{ $pesananMenunggu }}</div></div>
                @can('penjualan.lihat')
                    <div class="obat-stat"><div class="obat-stat-label">Penjualan obat hari ini</div><div class="obat-angka">Rp {{ number_format($omzetHariIni, 0, ',', '.') }}</div></div>
                @endcan
            @endif
        </div>
    </section>

    <div class="dash-grid">
        <section class="dash-utama" aria-labelledby="judul-tindakan">
            <div class="dash-judul">
                <h3 id="judul-tindakan">Perlu tindakan hari ini</h3>
                <p>Urut dari yang paling mendesak</p>
            </div>
            <div class="panel obat-scroll">
                @if ($tindakan->isEmpty())
                    <p class="tabel-kosong">Tidak ada yang perlu ditindaklanjuti saat ini.</p>
                @else
                    <table class="obat-table tabel-tindakan">
                        <thead>
                            <tr><th scope="col">Status</th><th scope="col">Item</th><th scope="col">Keterangan</th><th scope="col" class="kolom-aksi">Tindakan</th></tr>
                        </thead>
                        <tbody>
                            @foreach ($tindakan as $t)
                                <tr>
                                    <td><span class="badge {{ $t['kelas'] }}">{{ $t['status'] }}</span></td>
                                    <td>
                                        <span class="nama">{{ $t['nama'] }}</span>
                                        @if ($t['kode'])<br><span class="kode">{{ $t['kode'] }}</span>@endif
                                    </td>
                                    <td>{{ $t['ket'] }}</td>
                                    <td class="kolom-aksi">
                                        @if ($t['aksi'])
                                            <a href="{{ $t['aksi'][1] }}" @class(['btn-pesan' => $t['aksi'][2]])>{{ $t['aksi'][0] }}</a>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </section>

        @if ($pintasan->isNotEmpty())
            <aside class="dash-samping" aria-labelledby="judul-pintasan">
                <div class="dash-judul"><h3 id="judul-pintasan">Pintasan</h3></div>
                <nav class="panel pintasan" aria-label="Pintasan">
                    @foreach ($pintasan as [$label, $rute])
                        <a href="{{ route($rute) }}"><span>{{ $label }}</span><span aria-hidden="true">→</span></a>
                    @endforeach
                </nav>
                @if ($lihatObat)
                    <p class="teks-bantu" style="margin: 0">Rekomendasi obat dihitung dari pemakaian 90 hari terakhir. Keputusan akhir tetap di tangan apoteker.</p>
                @endif
            </aside>
        @endif
    </div>

    @if ($lihatAntrean)
        @php $persen = $antrean['total'] > 0 ? round($antrean['selesai'] / $antrean['total'] * 100) : 0; @endphp
        <section aria-labelledby="judul-antrean">
            <div class="dash-judul">
                <h3 id="judul-antrean">Antrean hari ini</h3>
                <p>{{ $antrean['selesai'] }} dari {{ $antrean['total'] }} selesai ({{ $persen }}%) · Dipanggil {{ $antrean['dipanggil'] }} · Dilayani {{ $antrean['dilayani'] }} · Batal {{ $antrean['batal'] }}</p>
            </div>
            <div class="bar" style="margin: 0 0 14px"><span style="width: {{ $persen }}%"></span></div>
            <div class="panel obat-scroll">
                <table class="obat-table">
                    <thead><tr><th>No. Antrean</th><th>Kode Pasien</th><th>Nama Pasien</th><th>Poli</th><th>Status</th></tr></thead>
                    <tbody>
                    @forelse ($antreanHariIni as $a)
                        <tr>
                            <td>{{ $a->nomor_antrean }}</td>
                            <td><span class="kode">{{ $a->pasien->kode_pasien }}</span></td>
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
            <p class="teks-bantu"><a href="{{ route('antrean.index') }}">Buka halaman antrean</a></p>
        </section>
    @endif
@endsection

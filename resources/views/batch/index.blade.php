@extends('layouts.app')
@section('title', 'Batch & Kedaluwarsa')

@section('content')
    <h2>Stok per Batch dan Peringatan Kedaluwarsa</h2>
    <p class="teks-bantu">
        Setiap penerimaan barang tercatat sebagai batch dengan tanggal kedaluwarsa (ED).
        Penjualan memakai prinsip <strong>FEFO</strong> (First Expired, First Out): batch dengan ED paling dekat keluar lebih dulu,
        dan batch yang sudah kedaluwarsa tidak dapat dijual.
    </p>

    <section class="obat-card">
        <h3>Peringatan Kedaluwarsa (≤ {{ $batas }} hari)</h3>
        <form class="obat-filter" method="GET" action="{{ route('batch.index') }}">
            <label for="batas"><strong>Batas peringatan:</strong></label>
            <select name="batas" id="batas" onchange="this.form.submit()">
                @foreach ([30, 60, 90, 180] as $b)
                    <option value="{{ $b }}" @selected($b === $batas)>{{ $b }} hari</option>
                @endforeach
            </select>
            @if ($obatId)<input type="hidden" name="obat_id" value="{{ $obatId }}">@endif
        </form>
        <div class="obat-ringkasan">
            <div class="obat-stat"><div class="obat-stat-label">Batch sudah kedaluwarsa</div><div class="obat-angka angka-merah">{{ $jumlahKedaluwarsa }}</div></div>
            <div class="obat-stat"><div class="obat-stat-label">Batch kedaluwarsa ≤ {{ $batas }} hari</div><div class="obat-angka angka-kuning">{{ $jumlahHampir }}</div></div>
            <div class="obat-stat"><div class="obat-stat-label">Unit obat berisiko</div><div class="obat-angka">{{ $unitBerisiko }}</div></div>
        </div>

        <div class="obat-scroll">
            <table class="obat-table">
                <thead><tr><th>Obat</th><th>Batch</th><th>Tanggal kedaluwarsa</th><th>Sisa</th><th>Status</th><th>Tindak lanjut</th></tr></thead>
                <tbody>
                @forelse ($peringatan as $b)
                    @php [$label, $kelas] = $b->statusKedaluwarsa($batas); @endphp
                    <tr>
                        <td><strong>{{ $b->obat->nama_obat }}</strong><br><small>{{ $b->obat->kode_obat }}</small></td>
                        <td>{{ $b->nomor_batch }}</td>
                        <td>{{ $b->tanggal_kedaluwarsa->translatedFormat('d M Y') }}</td>
                        <td>{{ $b->sisa }} {{ $b->obat->satuan }}</td>
                        <td><span class="badge {{ $kelas }}">{{ $label }}</span></td>
                        <td>
                            @if ($b->sudahKedaluwarsa())
                                Pisahkan dari rak dan catat retur/pemusnahan.
                                @can('retur')<a class="btn-garis btn-kecil" href="{{ route('retur.index', ['batch_id' => $b->id]) }}">Retur</a>@endcan
                            @else
                                Gunakan lebih dulu (FEFO). Letakkan di depan rak.
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6">Tidak ada batch yang kedaluwarsa dalam {{ $batas }} hari ke depan.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="obat-card">
        <h3>Daftar Stok per Batch</h3>
        <form class="obat-cari" method="GET" action="{{ route('batch.index') }}">
            <input type="hidden" name="batas" value="{{ $batas }}">
            <select name="obat_id">
                <option value="">Semua obat</option>
                @foreach ($daftarObat as $o)
                    <option value="{{ $o->id }}" @selected($obatId === $o->id)>{{ $o->kode_obat }} — {{ $o->nama_obat }}</option>
                @endforeach
            </select>
            <label class="cek-inline"><input type="checkbox" name="habis" value="1" @checked($tampilHabis)> Tampilkan batch habis</label>
            <button type="submit">Tampilkan</button>
        </form>
        <div class="obat-scroll">
            <table class="obat-table">
                <thead><tr><th>Obat</th><th>Batch</th><th>ED</th><th>Masuk</th><th>Sisa</th><th>Asal</th><th>Status</th></tr></thead>
                <tbody>
                @forelse ($semua as $b)
                    @php [$label, $kelas] = $b->statusKedaluwarsa($batas); @endphp
                    <tr @class(['baris-nonaktif' => $b->sisa === 0])>
                        <td>{{ $b->obat->nama_obat }}</td>
                        <td>{{ $b->nomor_batch }}</td>
                        <td>{{ $b->tanggal_kedaluwarsa->format('d/m/Y') }}</td>
                        <td>{{ $b->jumlah_awal }}</td>
                        <td><strong>{{ $b->sisa }}</strong> {{ $b->obat->satuan }}</td>
                        <td>{{ $b->penerimaan_id ? 'Penerimaan supplier' : 'Stok awal / penyesuaian' }}</td>
                        <td>@if ($b->sisa === 0)<span class="badge badge-abu">Habis</span>@else<span class="badge {{ $kelas }}">{{ $label }}</span>@endif</td>
                    </tr>
                @empty
                    <tr><td colspan="7">Belum ada batch.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection

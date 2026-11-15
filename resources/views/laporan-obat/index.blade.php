@extends('layouts.app')
@section('title', 'Laporan Obat')

@section('content')
    <h2 class="tidak-cetak">Laporan MediStock RS</h2>
    <p class="teks-bantu tidak-cetak">Pilih jenis laporan dan periode, lalu cetak/simpan sebagai PDF atau unduh sebagai file Excel.</p>

    <form class="obat-filter tidak-cetak" method="GET" action="{{ route('laporan-obat.index') }}" id="formLaporan">
        <label for="jenis"><strong>Jenis:</strong></label>
        <select name="jenis" id="jenis">
            @foreach (\App\Services\LaporanObatService::JENIS as $k => $label)
                <option value="{{ $k }}" @selected($jenis === $k)>{{ $label }}</option>
            @endforeach
        </select>
        <span class="isi-periode">
            <label for="tanggal_awal">Dari</label>
            <input type="date" name="tanggal_awal" id="tanggal_awal" value="{{ $awal->toDateString() }}">
            <label for="tanggal_akhir">s.d.</label>
            <input type="date" name="tanggal_akhir" id="tanggal_akhir" value="{{ $akhir->toDateString() }}">
        </span>
        <button type="submit">Tampilkan</button>
        <button type="button" class="btn-garis" onclick="window.print()">Cetak / Simpan PDF</button>
        <a class="btn-garis" href="{{ route('laporan-obat.ekspor', request()->only('jenis', 'tanggal_awal', 'tanggal_akhir') + ['jenis' => $jenis]) }}">
            Unduh {{ $excel ? 'Excel (.xlsx)' : 'CSV untuk Excel' }}
        </a>
    </form>

    <section class="obat-card laporan">
        <div class="kop-laporan">
            <strong>{{ config('app.name') }} · Instalasi Farmasi (MediStock RS)</strong>
            <h3>{{ $laporan['judul'] }}</h3>
            <small>Dicetak oleh {{ auth()->user()->name }} ({{ auth()->user()->labelPeran() }}) · {{ now()->format('d/m/Y H:i') }}</small>
        </div>
        <div class="obat-ringkasan">
            @foreach ($laporan['ringkasan'] as $k => $v)
                <div class="obat-stat"><div class="obat-stat-label">{{ $k }}</div><div class="obat-angka">{{ $v }}</div></div>
            @endforeach
        </div>
        <div class="obat-scroll">
            <table class="obat-table">
                <thead><tr>@foreach ($laporan['header'] as $h)<th>{{ $h }}</th>@endforeach</tr></thead>
                <tbody>
                @forelse ($laporan['baris'] as $b)
                    <tr>
                        @foreach ($b as $i => $v)
                            <td>{{ is_float($v) ? 'Rp ' . number_format($v, 0, ',', '.') : $v }}</td>
                        @endforeach
                    </tr>
                @empty
                    <tr><td colspan="{{ count($laporan['header']) }}">Tidak ada data pada periode ini.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection

@push('scripts')
<script>
    const jenisEl = document.getElementById('jenis');
    const atur = () => document.querySelector('.isi-periode').hidden = jenisEl.value === 'stok';
    jenisEl.addEventListener('change', atur);
    atur();
</script>
@endpush

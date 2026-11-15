@extends('layouts.app')
@section('title', 'Pesanan & Penerimaan Obat')

@section('content')
    <h2>Pesanan Pengadaan dan Penerimaan Barang</h2>
    <p class="teks-bantu">
        Alur dua tahap: <strong>1) apoteker membuat pesanan</strong> ke supplier, lalu
        <strong>2) staf gudang mencatat penerimaan</strong> beserta nomor batch dan tanggal kedaluwarsa.
        Stok bertambah saat barang diterima, bukan saat dipesan.
    </p>

    <section class="obat-card">
        <h3>Ringkasan Pengadaan</h3>
        <div class="obat-ringkasan">
            <div class="obat-stat"><div class="obat-stat-label">Pesanan menunggu barang</div><div class="obat-angka angka-kuning">{{ $jumlahMenunggu }}</div></div>
            <div class="obat-stat"><div class="obat-stat-label">Total pesanan</div><div class="obat-angka">{{ number_format($jumlahTransaksi) }}</div></div>
            <div class="obat-stat"><div class="obat-stat-label">Nilai pengadaan</div><div class="obat-angka">Rp {{ number_format($nilaiPembelian, 0, ',', '.') }}</div></div>
        </div>
    </section>

    @if ($terima)
        {{-- UC-06: TERIMA BARANG --}}
        <section class="obat-card sorot" id="form-terima">
            <h3>Terima Barang: {{ $terima->nomor_pembelian }}</h3>
            <p class="teks-bantu">
                {{ $terima->obat->nama_obat }} dari {{ $terima->supplier->nama_supplier }}.
                Dipesan {{ $terima->jumlah }} {{ $terima->obat->satuan }}, sudah diterima {{ $terima->jumlah_diterima }},
                <strong>sisa {{ $terima->sisaBelumDiterima() }}</strong>.
            </p>
            @if ($terima->bisaDiterima())
                <form method="POST" action="{{ route('pembelian.terima', $terima) }}">
                    @csrf
                    <div class="obat-grid">
                        <div>
                            <label for="t_jumlah">Jumlah diterima *</label>
                            <input id="t_jumlah" type="number" name="jumlah" min="1" max="{{ $terima->sisaBelumDiterima() }}" required value="{{ old('jumlah', $terima->sisaBelumDiterima()) }}">
                        </div>
                        <div>
                            <label for="t_batch">Nomor batch *</label>
                            <input id="t_batch" name="nomor_batch" maxlength="50" required value="{{ old('nomor_batch') }}" placeholder="Lihat kemasan, contoh: B2611A">
                        </div>
                        <div>
                            <label for="t_ed">Tanggal kedaluwarsa *</label>
                            <input id="t_ed" type="date" name="tanggal_kedaluwarsa" required min="{{ today()->addDay()->toDateString() }}" value="{{ old('tanggal_kedaluwarsa') }}">
                        </div>
                        <div>
                            <label for="t_tgl">Tanggal terima *</label>
                            <input id="t_tgl" type="date" name="tanggal_terima" required max="{{ today()->toDateString() }}" value="{{ old('tanggal_terima', today()->toDateString()) }}">
                        </div>
                        <div>
                            <label for="t_ket">Keterangan</label>
                            <input id="t_ket" name="keterangan" maxlength="255" value="{{ old('keterangan') }}" placeholder="Contoh: nomor surat jalan">
                        </div>
                    </div>
                    <p class="teks-bantu">Jika jumlah diterima kurang dari sisa pesanan, pesanan ditandai <em>diterima sebagian</em> dan dapat diterima lagi nanti.</p>
                    <button type="submit">Simpan Penerimaan</button>
                    <a class="btn-garis" href="{{ route('pembelian.index') }}">Batal</a>
                </form>
            @else
                <p>Pesanan ini sudah {{ strtolower($terima->labelStatus()[0]) }}.</p>
            @endif
        </section>
    @endif

    @can('pengadaan.pesan')
        {{-- UC-04: BUAT PESANAN --}}
        <section class="obat-card">
            <h3>Buat Pesanan Pengadaan</h3>
            @if ($dariRekomendasi)
                <p class="obat-info">Jumlah diisi dari saran <strong>Rekomendasi Pengadaan</strong>. Anda tetap dapat mengubahnya.</p>
            @endif
            <form method="POST" action="{{ route('pembelian.store') }}">
                @csrf
                <div class="obat-grid">
                    <div>
                        <label for="supplier_id">Supplier *</label>
                        <select name="supplier_id" id="supplier_id" required>
                            <option value="">-- Pilih Supplier --</option>
                            @foreach ($daftarSupplier as $s)
                                <option value="{{ $s->id }}" @selected(old('supplier_id') == $s->id)>{{ $s->kode_supplier }} - {{ $s->nama_supplier }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="obat_id">Obat *</label>
                        <select name="obat_id" id="obat_id" required>
                            <option value="">-- Pilih Obat --</option>
                            @foreach ($daftarObat as $o)
                                <option value="{{ $o->id }}" @selected(old('obat_id', $pilihObat) == $o->id)>{{ $o->kode_obat }} - {{ $o->nama_obat }} (Stok: {{ $o->stok }} {{ $o->satuan }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="jumlah">Jumlah dipesan *</label>
                        <input type="number" name="jumlah" id="jumlah" min="1" required value="{{ old('jumlah', $isiJumlah) }}" placeholder="Masukkan jumlah obat">
                    </div>
                    <div>
                        <label for="harga_satuan">Harga beli per satuan (Rp) *</label>
                        <input type="number" name="harga_satuan" id="harga_satuan" min="0" step="0.01" required value="{{ old('harga_satuan') }}" placeholder="Contoh: 3500">
                    </div>
                    <div>
                        <label for="tanggal_pembelian">Tanggal pesan *</label>
                        <input type="date" name="tanggal_pembelian" id="tanggal_pembelian" required value="{{ old('tanggal_pembelian', now()->format('Y-m-d')) }}">
                    </div>
                    <div>
                        <label for="keterangan">Keterangan</label>
                        <input name="keterangan" id="keterangan" maxlength="255"
                               value="{{ old('keterangan', $dariRekomendasi ? 'Berdasarkan rekomendasi pengadaan (saran ' . $isiJumlah . ')' : '') }}" placeholder="Contoh: nomor PO manual">
                    </div>
                </div>
                <p class="teks-bantu">Total: <strong id="totalPembelian">Rp 0</strong></p>
                <button type="submit">Simpan Pesanan</button>
            </form>
        </section>
    @endcan

    <section class="obat-card">
        <h3>Daftar Pesanan</h3>
        <form class="obat-filter" method="GET" action="{{ route('pembelian.index') }}">
            <label for="status"><strong>Status:</strong></label>
            <select name="status" id="status" onchange="this.form.submit()">
                <option value="">Semua</option>
                @foreach (\App\Models\Pembelian::STATUS as $k => [$label])
                    <option value="{{ $k }}" @selected($status === $k)>{{ $label }}</option>
                @endforeach
            </select>
        </form>
        <div class="obat-scroll">
            <table class="obat-table">
                <thead>
                    <tr><th>Nomor</th><th>Tgl pesan</th><th>Supplier</th><th>Obat</th><th>Dipesan</th><th>Diterima</th><th>Total</th><th>Status</th><th>Aksi</th></tr>
                </thead>
                <tbody>
                @forelse ($riwayat as $p)
                    @php [$label, $kelas] = $p->labelStatus(); @endphp
                    <tr>
                        <td>{{ $p->nomor_pembelian }}@if ($p->keterangan)<br><small>{{ $p->keterangan }}</small>@endif</td>
                        <td>{{ $p->tanggal_pembelian->format('d/m/Y') }}</td>
                        <td>{{ $p->supplier->nama_supplier }}</td>
                        <td>{{ $p->obat->nama_obat }}</td>
                        <td>{{ $p->jumlah }} {{ $p->obat->satuan }}</td>
                        <td>
                            {{ $p->jumlah_diterima }}
                            @foreach ($p->penerimaan as $g)
                                <br><small>{{ $g->nomor_batch }} · ED {{ $g->tanggal_kedaluwarsa->format('d/m/Y') }}</small>
                            @endforeach
                        </td>
                        <td>Rp {{ number_format($p->total_harga, 0, ',', '.') }}</td>
                        <td><span class="badge {{ $kelas }}">{{ $label }}</span></td>
                        <td>
                            <div class="aksi-baris">
                                @if ($p->bisaDiterima())
                                    @can('pengadaan.terima')
                                        <a class="btn-kecil btn-tautan" href="{{ route('pembelian.index', ['terima' => $p->id]) }}#form-terima">Terima barang</a>
                                    @endcan
                                @endif
                                @if ($p->status === 'dipesan')
                                    @can('pengadaan.pesan')
                                        <form method="POST" action="{{ route('pembelian.batal', $p) }}" onsubmit="return confirm('Batalkan pesanan {{ $p->nomor_pembelian }}?')">
                                            @csrf @method('PATCH')
                                            <button type="submit" class="btn-kecil btn-merah">Batalkan</button>
                                        </form>
                                    @endcan
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="9">Belum ada pesanan.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="obat-card">
        <h3>Penerimaan Barang Terakhir</h3>
        <div class="obat-scroll">
            <table class="obat-table">
                <thead><tr><th>No. Penerimaan</th><th>Tgl terima</th><th>Pesanan</th><th>Obat</th><th>Jumlah</th><th>Batch</th><th>ED</th><th>Petugas</th></tr></thead>
                <tbody>
                @forelse ($penerimaanTerakhir as $g)
                    <tr>
                        <td>{{ $g->nomor_penerimaan }}</td>
                        <td>{{ $g->tanggal_terima->format('d/m/Y') }}</td>
                        <td>{{ $g->pembelian->nomor_pembelian }}</td>
                        <td>{{ $g->pembelian->obat->nama_obat }}</td>
                        <td>{{ $g->jumlah }}</td>
                        <td>{{ $g->nomor_batch }}</td>
                        <td>{{ $g->tanggal_kedaluwarsa->format('d/m/Y') }}</td>
                        <td>{{ $g->petugas->name ?? '-' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="8">Belum ada penerimaan barang.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection

@push('scripts')
<script>
    const jumlahEl = document.getElementById('jumlah');
    const hargaEl = document.getElementById('harga_satuan');
    if (jumlahEl && hargaEl) {
        const hitungTotal = () => {
            const total = (Number(jumlahEl.value) || 0) * (Number(hargaEl.value) || 0);
            document.getElementById('totalPembelian').textContent = 'Rp ' + Math.round(total).toLocaleString('id-ID');
        };
        jumlahEl.addEventListener('input', hitungTotal);
        hargaEl.addEventListener('input', hitungTotal);
        hitungTotal();
    }
</script>
@endpush

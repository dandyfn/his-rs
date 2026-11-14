@extends('layouts.app')
@section('title', 'Pembelian Obat')

@section('content')
    <h2>Pembelian Obat dari Supplier</h2>
    <p class="teks-bantu">Catat pembelian obat dari supplier. Stok obat bertambah otomatis.</p>

    <section class="obat-card">
        <h3>Ringkasan Pembelian</h3>
        <div class="obat-ringkasan">
            <div class="obat-stat"><div class="obat-stat-label">Total Transaksi Pembelian</div><div class="obat-angka">{{ number_format($jumlahTransaksi) }}</div></div>
            <div class="obat-stat"><div class="obat-stat-label">Total Nilai Pembelian</div><div class="obat-angka">Rp {{ number_format($nilaiPembelian, 0, ',', '.') }}</div></div>
        </div>
    </section>

    <section class="obat-card">
        <h3>Input Pembelian Obat</h3>
        <p class="teks-bantu">
            <strong>Informasi:</strong> Setelah pembelian disimpan, jumlah stok obat akan bertambah
            dan transaksi otomatis tercatat sebagai stok masuk.
        </p>
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
                    <label for="jumlah">Jumlah Pembelian *</label>
                    <input type="number" name="jumlah" id="jumlah" min="1" required value="{{ old('jumlah', $isiJumlah) }}" placeholder="Masukkan jumlah obat">
                </div>
                <div>
                    <label for="harga_satuan">Harga per Satuan (Rp) *</label>
                    <input type="number" name="harga_satuan" id="harga_satuan" min="0" step="0.01" required value="{{ old('harga_satuan') }}" placeholder="Contoh: 3500">
                </div>
                <div>
                    <label for="tanggal_pembelian">Tanggal Pembelian *</label>
                    <input type="date" name="tanggal_pembelian" id="tanggal_pembelian" required value="{{ old('tanggal_pembelian', now()->format('Y-m-d')) }}">
                </div>
                <div>
                    <label for="keterangan">Keterangan</label>
                    <input name="keterangan" id="keterangan" maxlength="255" value="{{ old('keterangan') }}" placeholder="Contoh: nomor faktur">
                </div>
            </div>
            <p class="teks-bantu">Total: <strong id="totalPembelian">Rp 0</strong></p>
            <button type="submit">Simpan Pembelian</button>
        </form>
    </section>

    <section class="obat-card">
        <h3>Riwayat Pembelian Obat</h3>
        <div class="obat-scroll">
            <table class="obat-table">
                <thead>
                    <tr><th>No.</th><th>Nomor Pembelian</th><th>Tanggal</th><th>Supplier</th><th>Nama Obat</th><th>Jumlah</th><th>Harga Satuan</th><th>Total Harga</th></tr>
                </thead>
                <tbody>
                @forelse ($riwayat as $p)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>{{ $p->nomor_pembelian }}</td>
                        <td>{{ $p->tanggal_pembelian->format('d/m/Y') }}</td>
                        <td>{{ $p->supplier->nama_supplier }}</td>
                        <td>{{ $p->obat->nama_obat }}</td>
                        <td>{{ $p->jumlah }} {{ $p->obat->satuan }}</td>
                        <td>Rp {{ number_format($p->harga_satuan, 0, ',', '.') }}</td>
                        <td>Rp {{ number_format($p->total_harga, 0, ',', '.') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="8">Belum ada transaksi pembelian.</td></tr>
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
    function hitungTotal() {
        const total = (Number(jumlahEl.value) || 0) * (Number(hargaEl.value) || 0);
        document.getElementById('totalPembelian').textContent = 'Rp ' + Math.round(total).toLocaleString('id-ID');
    }
    jumlahEl.addEventListener('input', hitungTotal);
    hargaEl.addEventListener('input', hitungTotal);
    hitungTotal();
</script>
@endpush

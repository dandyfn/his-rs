@extends('layouts.app')
@section('title', 'Penjualan Obat')

@section('content')
    <h2>Penjualan Obat ke Pasien</h2>
    <p class="teks-bantu">
        Catat penjualan obat dengan atau tanpa resep. Stok berkurang otomatis dan
        menjadi data pemakaian untuk halaman Rekomendasi Obat.
    </p>

    <section class="obat-card">
        <h3>Ringkasan Hari Ini</h3>
        <div class="obat-ringkasan">
            <div class="obat-stat"><div class="obat-stat-label">Transaksi hari ini</div><div class="obat-angka">{{ $transaksiHariIni }}</div></div>
            <div class="obat-stat"><div class="obat-stat-label">Total penjualan hari ini</div><div class="obat-angka">Rp {{ number_format($omzetHariIni, 0, ',', '.') }}</div></div>
        </div>
    </section>

    <section class="obat-card">
        <h3>Transaksi Penjualan Baru</h3>
        <form method="POST" action="{{ route('penjualan.store') }}" id="formPenjualan">
            @csrf
            <div class="obat-grid">
                <div>
                    <label for="pasien_id">Pasien (dari data pendaftaran)</label>
                    <select name="pasien_id" id="pasien_id">
                        <option value="">Pembeli umum / bukan pasien terdaftar</option>
                        @foreach ($daftarPasien as $p)
                            <option value="{{ $p->id }}" @selected(old('pasien_id') == $p->id)>{{ $p->kode_pasien }} - {{ $p->nama }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="nama_pembeli">Nama pembeli (jika bukan pasien)</label>
                    <input name="nama_pembeli" id="nama_pembeli" maxlength="100" value="{{ old('nama_pembeli') }}" placeholder="Contoh: Budi Santoso">
                </div>
                <div>
                    <label for="jenis">Jenis penjualan</label>
                    <select name="jenis" id="jenis">
                        <option value="non_resep">Tanpa resep (obat bebas)</option>
                        <option value="resep" @selected(old('jenis') === 'resep')>Dengan resep dokter</option>
                    </select>
                </div>
                <div>
                    <label for="nomor_resep">Nomor resep</label>
                    <input name="nomor_resep" id="nomor_resep" maxlength="50" value="{{ old('nomor_resep') }}" placeholder="Contoh: RSP-2026-0012">
                </div>
            </div>

            <div class="obat-scroll" style="margin-top:16px">
                <table class="obat-table">
                    <thead>
                        <tr><th style="width:42%">Obat</th><th>Stok</th><th>Harga</th><th style="width:110px">Jumlah</th><th>Subtotal</th><th></th></tr>
                    </thead>
                    <tbody id="barisItem"></tbody>
                </table>
            </div>

            <p style="margin-top:12px"><button type="button" class="btn-garis" id="tambahBaris">+ Tambah obat</button></p>
            <div class="obat-total">Total: <span id="totalBayar">Rp 0</span></div>
            <button type="submit">Simpan penjualan dan cetak struk</button>
        </form>
    </section>

    <section class="obat-card">
        <h3>Riwayat Penjualan Terakhir</h3>
        <div class="obat-scroll">
            <table class="obat-table">
                <thead><tr><th>Waktu</th><th>Nomor</th><th>Pembeli</th><th>Jenis</th><th>Total</th><th></th></tr></thead>
                <tbody>
                @forelse ($riwayat as $r)
                    <tr>
                        <td>{{ $r->created_at->format('d/m/Y H:i') }}</td>
                        <td>{{ $r->nomor_penjualan }}</td>
                        <td>{{ $r->nama_pembeli }}</td>
                        <td>
                            @if ($r->jenis === 'resep')
                                <span class="badge badge-biru">Resep</span>
                            @else
                                <span class="badge badge-abu">Tanpa resep</span>
                            @endif
                        </td>
                        <td>Rp {{ number_format($r->total, 0, ',', '.') }}</td>
                        <td><a class="btn-garis btn-kecil" href="{{ route('penjualan.struk', $r) }}">Struk</a></td>
                    </tr>
                @empty
                    <tr><td colspan="6">Belum ada penjualan.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <template id="templateBaris">
        <tr>
            <td>
                <select name="obat_id[]" class="pilihObat" required>
                    <option value="">Pilih obat</option>
                    @foreach ($daftarObat as $o)
                        <option value="{{ $o->id }}" data-stok="{{ $o->stok }}" data-harga="{{ (float) $o->harga_jual }}" data-satuan="{{ $o->satuan }}" @disabled($o->stok === 0)>
                            {{ $o->nama_obat }}{{ $o->stok === 0 ? ' (stok habis)' : '' }}
                        </option>
                    @endforeach
                </select>
            </td>
            <td class="sel-stok">-</td>
            <td class="sel-harga">-</td>
            <td><input type="number" name="jumlah[]" class="isiJumlah" min="1" value="1" required></td>
            <td class="sel-subtotal">Rp 0</td>
            <td><button type="button" class="btn-hapus hapusBaris" aria-label="Hapus baris">Hapus</button></td>
        </tr>
    </template>
@endsection

@push('scripts')
<script>
const rupiah = n => 'Rp ' + Math.round(n).toLocaleString('id-ID');
const tbody = document.getElementById('barisItem');

function hitung() {
    let total = 0;
    tbody.querySelectorAll('tr').forEach(tr => {
        const opt = tr.querySelector('.pilihObat').selectedOptions[0];
        const jumlahInput = tr.querySelector('.isiJumlah');
        if (!opt || !opt.value) {
            tr.querySelector('.sel-stok').textContent = '-';
            tr.querySelector('.sel-harga').textContent = '-';
            tr.querySelector('.sel-subtotal').textContent = 'Rp 0';
            return;
        }
        const stok = Number(opt.dataset.stok), harga = Number(opt.dataset.harga);
        jumlahInput.max = stok;
        const sub = harga * (Number(jumlahInput.value) || 0);
        total += sub;
        tr.querySelector('.sel-stok').textContent = stok + ' ' + opt.dataset.satuan;
        tr.querySelector('.sel-harga').textContent = rupiah(harga);
        tr.querySelector('.sel-subtotal').textContent = rupiah(sub);
    });
    document.getElementById('totalBayar').textContent = rupiah(total);
}

function tambahBaris() {
    tbody.appendChild(document.getElementById('templateBaris').content.cloneNode(true));
    hitung();
}

document.getElementById('tambahBaris').addEventListener('click', tambahBaris);
tbody.addEventListener('change', hitung);
tbody.addEventListener('input', hitung);
tbody.addEventListener('click', ev => {
    if (ev.target.classList.contains('hapusBaris') && tbody.children.length > 1) {
        ev.target.closest('tr').remove();
        hitung();
    }
});

const jenis = document.getElementById('jenis');
const resep = document.getElementById('nomor_resep');
function aturResep() {
    resep.disabled = jenis.value !== 'resep';
    resep.required = jenis.value === 'resep';
}
jenis.addEventListener('change', aturResep);
aturResep();
tambahBaris();
</script>
@endpush

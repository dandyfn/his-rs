@extends('layouts.app')
@section('title', 'Manajemen Stok Obat')

@section('content')
    <h2>Manajemen Stok Obat</h2>
    <p class="teks-bantu">
        Kelola data obat dan pantau stok. Stok dicatat per batch dan bertambah saat barang
        dari supplier diterima di menu <a href="{{ route('pembelian.index') }}">Pesanan &amp; Penerimaan</a>.
    </p>

    {{-- RINGKASAN STOK --}}
    <section class="obat-card">
        <h3>Ringkasan Stok</h3>
        <div class="obat-ringkasan">
            <div class="obat-stat">
                <div class="obat-stat-label">Jenis obat aktif</div>
                <div class="obat-angka">{{ $ringkasan['jumlah_jenis'] }}</div>
            </div>
            <div class="obat-stat">
                <div class="obat-stat-label">Total stok seluruh obat</div>
                <div class="obat-angka">{{ number_format($ringkasan['total_stok'], 0, ',', '.') }}</div>
            </div>
            <div class="obat-stat">
                <div class="obat-stat-label">Di bawah stok minimum</div>
                <div class="obat-angka angka-merah">{{ $ringkasan['perlu_pengadaan'] }}</div>
            </div>
            <div class="obat-stat">
                <div class="obat-stat-label">Batch kedaluwarsa / ≤ {{ $batas }} hari</div>
                <div class="obat-angka angka-kuning">{{ $ringkasan['mendekati_kedaluwarsa'] }}</div>
            </div>
        </div>
        @if ($ringkasan['mendekati_kedaluwarsa'] > 0)
            <p class="teks-bantu">⚠ Ada batch yang sudah atau hampir kedaluwarsa. Lihat <a href="{{ route('batch.index') }}">Batch &amp; Kedaluwarsa</a>.</p>
        @endif
    </section>

    @can('obat.kelola')
        {{-- TAMBAH / EDIT DATA OBAT (FR-01) --}}
        <section class="obat-card" id="form-obat">
            <h3>{{ $edit ? 'Edit Data Obat: ' . $edit->nama_obat : 'Tambah Data Obat' }}</h3>
            <form method="POST" action="{{ $edit ? route('obat.update', $edit) : route('obat.store') }}">
                @csrf
                @if ($edit) @method('PUT') @endif
                <div class="obat-grid">
                    <div>
                        <label for="kode_obat">Kode obat *</label>
                        <input id="kode_obat" name="kode_obat" value="{{ old('kode_obat', $edit?->kode_obat) }}" required maxlength="30" pattern="[A-Za-z0-9-]{3,30}" placeholder="OBT-001">
                    </div>
                    <div>
                        <label for="nama_obat">Nama obat *</label>
                        <input id="nama_obat" name="nama_obat" value="{{ old('nama_obat', $edit?->nama_obat) }}" required maxlength="150" placeholder="Contoh: Parasetamol 500 mg">
                    </div>
                    <div>
                        <label for="satuan">Satuan *</label>
                        <input id="satuan" name="satuan" value="{{ old('satuan', $edit?->satuan ?? 'strip') }}" required maxlength="30">
                    </div>
                    <div>
                        <label for="harga_jual">Harga jual per satuan (Rp) *</label>
                        <input id="harga_jual" type="number" name="harga_jual" min="0" step="100" required value="{{ old('harga_jual', $edit ? (float) $edit->harga_jual : 0) }}">
                    </div>
                    <div>
                        <label for="stok_minimum">Stok minimum *</label>
                        <input id="stok_minimum" type="number" name="stok_minimum" min="0" required value="{{ old('stok_minimum', $edit?->stok_minimum ?? 10) }}">
                    </div>
                    <div>
                        <label for="lead_time_hari">Lama kirim supplier (hari) *</label>
                        <input id="lead_time_hari" type="number" name="lead_time_hari" min="1" max="180" required value="{{ old('lead_time_hari', $edit?->lead_time_hari ?? 7) }}">
                    </div>
                    <div>
                        <label for="stok_pengaman_hari">Stok pengaman (hari) *</label>
                        <input id="stok_pengaman_hari" type="number" name="stok_pengaman_hari" min="0" max="180" required value="{{ old('stok_pengaman_hari', $edit?->stok_pengaman_hari ?? 3) }}">
                    </div>
                    <div>
                        <label for="interval_pesan_hari">Jarak antar pesanan (hari) *</label>
                        <input id="interval_pesan_hari" type="number" name="interval_pesan_hari" min="1" max="365" required value="{{ old('interval_pesan_hari', $edit?->interval_pesan_hari ?? 14) }}">
                    </div>
                </div>
                <p class="teks-bantu">
                    Nama obat tidak boleh kembar. Tiga isian terakhir dipakai oleh rumus rekomendasi pengadaan
                    (L, stok pengaman, dan T). Perubahan harga tercatat otomatis di riwayat harga.
                </p>
                <button type="submit">{{ $edit ? 'Simpan Perubahan' : 'Simpan Obat' }}</button>
                @if ($edit)
                    <a class="btn-garis" href="{{ route('obat.index') }}">Batal</a>
                @endif
            </form>
        </section>
    @endcan

    {{-- DAFTAR OBAT --}}
    <section class="obat-card">
        <h3>Daftar Obat</h3>
        <div class="obat-scroll">
            <table class="obat-table">
                <thead>
                    <tr>
                        <th>Kode</th><th>Nama obat</th><th>Stok total</th><th>Layak jual</th><th>Stok minimum</th>
                        <th>Harga jual</th><th>Status</th>
                        @can('obat.kelola')<th>Aksi</th>@endcan
                    </tr>
                </thead>
                <tbody>
                @forelse ($daftarObat as $o)
                    <tr @class(['baris-nonaktif' => ! $o->aktif])>
                        <td>{{ $o->kode_obat }}</td>
                        <td>{{ $o->nama_obat }}<br><small>{{ $o->satuan }}</small></td>
                        <td>{{ $o->stok }}</td>
                        <td>
                            {{ (int) $o->stok_layak }}
                            @if ($o->stok > (int) $o->stok_layak)
                                <br><small class="teks-peringatan">{{ $o->stok - (int) $o->stok_layak }} kedaluwarsa</small>
                            @endif
                        </td>
                        <td>{{ $o->stok_minimum }}</td>
                        <td>Rp {{ number_format($o->harga_jual, 0, ',', '.') }}</td>
                        <td>
                            @if (! $o->aktif)
                                <span class="badge badge-abu">Nonaktif</span>
                            @elseif ($o->perluDitinjau())
                                <span class="stok-rendah">Di bawah minimum</span>
                            @else
                                <span class="stok-cukup">Cukup</span>
                            @endif
                        </td>
                        @can('obat.kelola')
                            <td>
                                <div class="aksi-baris">
                                    <a class="btn-garis btn-kecil" href="{{ route('obat.index', ['edit' => $o->id]) }}#form-obat">Edit</a>
                                    <form method="POST" action="{{ route('obat.toggle', $o) }}">
                                        @csrf @method('PATCH')
                                        <button type="submit" @class(['btn-kecil', 'btn-merah' => $o->aktif])>{{ $o->aktif ? 'Nonaktifkan' : 'Aktifkan' }}</button>
                                    </form>
                                </div>
                            </td>
                        @endcan
                    </tr>
                @empty
                    <tr><td colspan="8">Belum ada data obat.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <p class="teks-bantu">
            <strong>Layak jual</strong> = stok dari batch yang belum kedaluwarsa. Status hanya membandingkan stok dengan
            batas minimum; saran waktu dan jumlah pemesanan ada di halaman Rekomendasi Pengadaan.
        </p>
    </section>

    @can('obat.kelola')
        {{-- PENYESUAIAN STOK (stock opname) --}}
        <section class="obat-card">
            <h3>Penyesuaian Stok (Stock Opname)</h3>
            <p class="teks-bantu">
                Dipakai jika stok fisik berbeda dengan sistem. Penerimaan dari supplier dicatat di menu
                Pesanan &amp; Penerimaan, dan obat rusak/kedaluwarsa dicatat di menu Retur Obat.
            </p>
            <form method="POST" action="{{ route('obat.transaksi') }}" id="formPenyesuaian">
                @csrf
                <div class="obat-grid">
                    <div>
                        <label for="adj_obat">Obat *</label>
                        <select id="adj_obat" name="obat_id" required>
                            <option value="">-- Pilih obat --</option>
                            @foreach ($daftarObat as $o)
                                <option value="{{ $o->id }}" @selected(old('obat_id') == $o->id)>{{ $o->kode_obat }} — {{ $o->nama_obat }} (stok: {{ $o->stok }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="adj_jenis">Jenis *</label>
                        <select id="adj_jenis" name="jenis" required>
                            <option value="masuk">Tambah stok</option>
                            <option value="keluar" @selected(old('jenis') === 'keluar')>Kurangi stok</option>
                        </select>
                    </div>
                    <div>
                        <label for="adj_jumlah">Jumlah *</label>
                        <input id="adj_jumlah" type="number" name="jumlah" min="1" required value="{{ old('jumlah') }}">
                    </div>
                    <div class="isi-masuk">
                        <label for="adj_batch">Nomor batch *</label>
                        <input id="adj_batch" name="nomor_batch" maxlength="50" value="{{ old('nomor_batch') }}" placeholder="Contoh: B2611A">
                    </div>
                    <div class="isi-masuk">
                        <label for="adj_ed">Tanggal kedaluwarsa *</label>
                        <input id="adj_ed" type="date" name="tanggal_kedaluwarsa" min="{{ today()->addDay()->toDateString() }}" value="{{ old('tanggal_kedaluwarsa') }}">
                    </div>
                    <div class="isi-keluar">
                        <label for="adj_batch_id">Batch yang dikurangi *</label>
                        <select id="adj_batch_id" name="batch_id">
                            <option value="">-- Pilih batch --</option>
                            @foreach ($daftarBatch as $b)
                                <option value="{{ $b->id }}" data-obat="{{ $b->obat_id }}">{{ $b->obat->nama_obat }} · {{ $b->nomor_batch }} · ED {{ $b->tanggal_kedaluwarsa->format('d/m/Y') }} (sisa {{ $b->sisa }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="adj_ket">Alasan *</label>
                        <input id="adj_ket" name="keterangan" maxlength="255" required value="{{ old('keterangan') }}" placeholder="Contoh: hasil stock opname 15/11">
                    </div>
                </div>
                <button type="submit">Simpan Penyesuaian</button>
            </form>
        </section>
    @endcan

    {{-- RIWAYAT TRANSAKSI STOK (NFR-09) --}}
    <section class="obat-card">
        <h3>20 Transaksi Stok Terakhir</h3>
        <div class="obat-scroll">
            <table class="obat-table">
                <thead>
                    <tr><th>Waktu</th><th>Obat</th><th>Batch</th><th>Jenis</th><th>Jumlah</th><th>Sumber</th><th>Keterangan</th><th>Petugas</th></tr>
                </thead>
                <tbody>
                @forelse ($riwayat as $t)
                    <tr>
                        <td>{{ $t->created_at->format('d/m/Y H:i') }}</td>
                        <td>{{ $t->obat->nama_obat }}</td>
                        <td>{{ $t->batch?->nomor_batch ?? '-' }}</td>
                        <td><span class="badge {{ $t->jenis === 'masuk' ? 'badge-hijau' : 'badge-kuning' }}">{{ $t->jenis }}</span></td>
                        <td>{{ $t->jumlah }} {{ $t->obat->satuan }}</td>
                        <td>{{ $t->sumber }}</td>
                        <td>{{ $t->keterangan ?? '-' }}</td>
                        <td>{{ $t->petugas->name ?? 'Sistem' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="8">Belum ada transaksi stok.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </section>

    {{-- RIWAYAT HARGA (NFR-09) --}}
    <section class="obat-card">
        <h3>Riwayat Perubahan Harga</h3>
        <div class="obat-scroll">
            <table class="obat-table">
                <thead><tr><th>Waktu</th><th>Obat</th><th>Harga lama</th><th>Harga baru</th><th>Diubah oleh</th></tr></thead>
                <tbody>
                @forelse ($riwayatHarga as $h)
                    <tr>
                        <td>{{ $h->created_at->format('d/m/Y H:i') }}</td>
                        <td>{{ $h->obat->nama_obat }}</td>
                        <td>Rp {{ number_format($h->harga_lama, 0, ',', '.') }}</td>
                        <td>Rp {{ number_format($h->harga_baru, 0, ',', '.') }}</td>
                        <td>{{ $h->petugas->name ?? '-' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5">Belum ada perubahan harga.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection

@push('scripts')
<script>
(() => {
    const form = document.getElementById('formPenyesuaian');
    if (!form) return;
    const jenis = form.querySelector('[name=jenis]');
    const obat = form.querySelector('[name=obat_id]');
    const pilihBatch = form.querySelector('[name=batch_id]');
    function atur() {
        const masuk = jenis.value === 'masuk';
        form.querySelectorAll('.isi-masuk').forEach(el => el.hidden = !masuk);
        form.querySelectorAll('.isi-keluar').forEach(el => el.hidden = masuk);
        form.querySelector('[name=nomor_batch]').required = masuk;
        form.querySelector('[name=tanggal_kedaluwarsa]').required = masuk;
        pilihBatch.required = !masuk;
        // hanya tampilkan batch milik obat yang dipilih
        [...pilihBatch.options].forEach(o => { if (o.value) o.hidden = obat.value && o.dataset.obat !== obat.value; });
        if (pilihBatch.selectedOptions[0]?.hidden) pilihBatch.value = '';
    }
    jenis.addEventListener('change', atur);
    obat.addEventListener('change', atur);
    atur();
})();
</script>
@endpush

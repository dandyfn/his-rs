@extends('layouts.app')
@section('title', 'Manajemen Stok Obat')

@section('content')
    <h2>Manajemen Stok Obat</h2>
    <p class="teks-bantu">
        Kelola data obat, catat pergerakan stok, dan identifikasi obat
        yang perlu dipertimbangkan untuk pengadaan.
    </p>

    {{-- RINGKASAN STOK --}}
    <section class="obat-card">
        <h3>Ringkasan Stok</h3>
        <div class="obat-ringkasan">
            <div class="obat-stat">
                <div class="obat-stat-label">Jenis obat</div>
                <div class="obat-angka">{{ $ringkasan['jumlah_jenis'] }}</div>
            </div>
            <div class="obat-stat">
                <div class="obat-stat-label">Total stok seluruh obat</div>
                <div class="obat-angka">{{ $ringkasan['total_stok'] }}</div>
            </div>
            <div class="obat-stat">
                <div class="obat-stat-label">Obat perlu ditinjau</div>
                <div class="obat-angka">{{ $ringkasan['perlu_pengadaan'] }}</div>
            </div>
        </div>
    </section>

    {{-- TAMBAH DATA OBAT --}}
    <section class="obat-card">
        <h3>Tambah Data Obat</h3>
        <form method="POST" action="{{ route('obat.store') }}">
            @csrf
            <div class="obat-grid">
                <div>
                    <label>Kode obat *</label>
                    <input name="kode_obat" value="{{ old('kode_obat') }}" required maxlength="30" pattern="[A-Za-z0-9-]{3,30}" placeholder="OBT-001">
                </div>
                <div>
                    <label>Nama obat *</label>
                    <input name="nama_obat" value="{{ old('nama_obat') }}" required maxlength="150" placeholder="Contoh: Parasetamol">
                </div>
                <div>
                    <label>Satuan *</label>
                    <input name="satuan" value="{{ old('satuan', 'tablet') }}" required maxlength="30">
                </div>
                <div>
                    <label>Stok minimum *</label>
                    <input type="number" name="stok_minimum" min="0" required value="{{ old('stok_minimum', 10) }}">
                </div>
                <div>
                    <label>Harga jual per satuan (Rp) *</label>
                    <input type="number" name="harga_jual" min="0" step="100" required value="{{ old('harga_jual', 0) }}">
                </div>
            </div>
            <p class="teks-bantu">
                Stok awal dimulai dari nol. Gunakan formulir transaksi
                stok masuk untuk mencatat stok awal yang benar.
            </p>
            <button type="submit">Simpan Obat</button>
        </form>
    </section>

    {{-- TRANSAKSI STOK --}}
    <section class="obat-card">
        <h3>Transaksi Stok Masuk / Keluar</h3>
        <form method="POST" action="{{ route('obat.transaksi') }}">
            @csrf
            <div class="obat-grid">
                <div>
                    <label>Pilih obat *</label>
                    <select name="obat_id" required>
                        <option value="">-- Pilih obat --</option>
                        @foreach ($daftarObat as $o)
                            <option value="{{ $o->id }}" @selected(old('obat_id') == $o->id)>
                                {{ $o->kode_obat }} — {{ $o->nama_obat }} (stok: {{ $o->stok }} {{ $o->satuan }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label>Jenis transaksi *</label>
                    <select name="jenis" required>
                        <option value="masuk">Stok masuk</option>
                        <option value="keluar" @selected(old('jenis') === 'keluar')>Stok keluar</option>
                    </select>
                </div>
                <div>
                    <label>Jumlah *</label>
                    <input type="number" name="jumlah" min="1" required value="{{ old('jumlah') }}">
                </div>
                <div>
                    <label>Keterangan</label>
                    <input name="keterangan" maxlength="255" value="{{ old('keterangan') }}" placeholder="Contoh: penerimaan dari pemasok">
                </div>
            </div>
            <p class="teks-bantu">
                Stok keluar akan ditolak jika jumlahnya melebihi stok tersedia.
                Isi keterangan untuk membantu penelusuran transaksi.
            </p>
            <button type="submit">Simpan Transaksi</button>
        </form>
    </section>

    {{-- DAFTAR OBAT --}}
    <section class="obat-card">
        <h3>Daftar Obat</h3>
        <div class="obat-scroll">
            <table class="obat-table">
                <thead>
                    <tr>
                        <th>Kode</th>
                        <th>Nama obat</th>
                        <th>Stok</th>
                        <th>Stok minimum</th>
                        <th>Harga jual</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                @forelse ($daftarObat as $o)
                    <tr>
                        <td>{{ $o->kode_obat }}</td>
                        <td>{{ $o->nama_obat }}<br><small>{{ $o->satuan }}</small></td>
                        <td>{{ $o->stok }}</td>
                        <td>{{ $o->stok_minimum }}</td>
                        <td>Rp {{ number_format($o->harga_jual, 0, ',', '.') }}</td>
                        <td>
                            @if ($o->perluDitinjau())
                                <span class="stok-rendah">Perlu ditinjau</span>
                            @else
                                <span class="stok-cukup">Cukup</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6">Belum ada data obat.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <p class="teks-bantu">
            Status di atas hanya membandingkan stok dengan batas minimum. Untuk saran
            waktu dan jumlah pemesanan berdasarkan data pemakaian, buka halaman
            <a href="{{ route('rekomendasi.index') }}">Rekomendasi Obat</a>.
        </p>
    </section>

    {{-- RIWAYAT TRANSAKSI --}}
    <section class="obat-card">
        <h3>20 Transaksi Terakhir</h3>
        <div class="obat-scroll">
            <table class="obat-table">
                <thead>
                    <tr><th>Waktu</th><th>Obat</th><th>Jenis</th><th>Jumlah</th><th>Keterangan</th></tr>
                </thead>
                <tbody>
                @forelse ($riwayat as $t)
                    <tr>
                        <td>{{ $t->created_at->format('d/m/Y H:i') }}</td>
                        <td>{{ $t->obat->kode_obat }} — {{ $t->obat->nama_obat }}</td>
                        <td>{{ $t->jenis }}</td>
                        <td>{{ $t->jumlah }} {{ $t->obat->satuan }}</td>
                        <td>{{ $t->keterangan ?? '-' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5">Belum ada transaksi stok.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection

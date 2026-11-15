@extends('layouts.app')
@section('title', 'Retur Obat')

@section('content')
    <h2>Retur Obat</h2>
    <p class="teks-bantu">
        Catat obat yang rusak, kedaluwarsa, salah kirim, atau ditarik dari peredaran supaya tidak
        tercampur dengan stok yang layak jual. Stok batch berkurang dan alasan retur tercatat.
    </p>

    <section class="obat-card">
        <h3>Catat Retur</h3>
        <form method="POST" action="{{ route('retur.store') }}">
            @csrf
            <div class="obat-grid">
                <div class="lebar-2">
                    <label for="batch_id">Batch obat *</label>
                    <select id="batch_id" name="batch_id" required>
                        <option value="">-- Pilih batch --</option>
                        @foreach ($daftarBatch as $b)
                            <option value="{{ $b->id }}" @selected(old('batch_id', $pilihBatch) == $b->id)>
                                {{ $b->sudahKedaluwarsa() ? '⚠ KEDALUWARSA · ' : '' }}{{ $b->obat->nama_obat }} · batch {{ $b->nomor_batch }} · ED {{ $b->tanggal_kedaluwarsa->format('d/m/Y') }} · sisa {{ $b->sisa }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="jumlah">Jumlah *</label>
                    <input id="jumlah" type="number" name="jumlah" min="1" required value="{{ old('jumlah') }}">
                </div>
                <div>
                    <label for="alasan">Alasan *</label>
                    <select id="alasan" name="alasan" required>
                        @foreach (\App\Models\ReturObat::ALASAN as $k => $label)
                            <option value="{{ $k }}" @selected(old('alasan') === $k)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="tujuan">Tindak lanjut *</label>
                    <select id="tujuan" name="tujuan" required>
                        @foreach (\App\Models\ReturObat::TUJUAN as $k => $label)
                            <option value="{{ $k }}" @selected(old('tujuan') === $k)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="keterangan">Keterangan</label>
                    <input id="keterangan" name="keterangan" maxlength="255" value="{{ old('keterangan') }}" placeholder="Contoh: nomor berita acara">
                </div>
            </div>
            <button type="submit">Simpan Retur</button>
        </form>
    </section>

    <section class="obat-card">
        <h3>Riwayat Retur</h3>
        <div class="obat-scroll">
            <table class="obat-table">
                <thead><tr><th>Waktu</th><th>No. Retur</th><th>Obat</th><th>Batch / ED</th><th>Jumlah</th><th>Alasan</th><th>Tindak lanjut</th><th>Petugas</th></tr></thead>
                <tbody>
                @forelse ($riwayat as $r)
                    <tr>
                        <td>{{ $r->created_at->format('d/m/Y H:i') }}</td>
                        <td>{{ $r->nomor_retur }}@if ($r->keterangan)<br><small>{{ $r->keterangan }}</small>@endif</td>
                        <td>{{ $r->obat->nama_obat }}</td>
                        <td>{{ $r->batch->nomor_batch }}<br><small>ED {{ $r->batch->tanggal_kedaluwarsa->format('d/m/Y') }}</small></td>
                        <td>{{ $r->jumlah }} {{ $r->obat->satuan }}</td>
                        <td>{{ \App\Models\ReturObat::ALASAN[$r->alasan] ?? $r->alasan }}</td>
                        <td>{{ \App\Models\ReturObat::TUJUAN[$r->tujuan] ?? $r->tujuan }}</td>
                        <td>{{ $r->petugas->name ?? '-' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="8">Belum ada retur.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection

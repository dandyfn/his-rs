@extends('layouts.app')
@section('title', 'Antrean Rawat Jalan')

@section('content')
    <h2>Manajemen Antrean Rawat Jalan</h2>
    <p class="teks-bantu">Daftarkan pasien ke antrean poli dan perbarui status pelayanan hari ini ({{ today()->translatedFormat('d F Y') }}).</p>

    <section class="obat-card">
        <h3>Ringkasan Antrean Hari Ini</h3>
        <div class="obat-ringkasan">
            <div class="obat-stat"><div class="obat-stat-label">Total</div><div class="obat-angka">{{ $ringkasan['total'] }}</div></div>
            <div class="obat-stat"><div class="obat-stat-label">Menunggu</div><div class="obat-angka angka-kuning">{{ $ringkasan['menunggu'] }}</div></div>
            <div class="obat-stat"><div class="obat-stat-label">Dipanggil</div><div class="obat-angka">{{ $ringkasan['dipanggil'] }}</div></div>
            <div class="obat-stat"><div class="obat-stat-label">Dilayani</div><div class="obat-angka">{{ $ringkasan['dilayani'] }}</div></div>
            <div class="obat-stat"><div class="obat-stat-label">Selesai</div><div class="obat-angka angka-hijau">{{ $ringkasan['selesai'] }}</div></div>
            <div class="obat-stat"><div class="obat-stat-label">Batal</div><div class="obat-angka angka-merah">{{ $ringkasan['batal'] }}</div></div>
        </div>
    </section>

    @can('antrean.daftar')
    <section class="obat-card">
        <h3>Formulir Pendaftaran Antrean</h3>
        <form method="POST" action="{{ route('antrean.store') }}">
            @csrf
            <div class="obat-grid">
                <div>
                    <label for="pasien_id">Pasien *</label>
                    <select name="pasien_id" id="pasien_id" required>
                        <option value="">-- Pilih pasien --</option>
                        @foreach ($daftarPasien as $p)
                            <option value="{{ $p->id }}" @selected(old('pasien_id') == $p->id)>{{ $p->kode_pasien }} - {{ $p->nama }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="poli_id">Poli *</label>
                    <select name="poli_id" id="poli_id" required>
                        <option value="">-- Pilih poli --</option>
                        @foreach ($daftarPoli as $p)
                            <option value="{{ $p->id }}" @selected(old('poli_id') == $p->id)>{{ $p->nama_poli }} (batas {{ $p->batas_antrean }}/hari)</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <p class="teks-bantu">Pasien belum terdaftar? Tambahkan dulu di menu <a href="{{ route('pasien.index') }}">Data Pasien</a>.</p>
            <button type="submit">Daftarkan Antrean</button>
        </form>
    </section>
    @endcan

    <section class="obat-card">
        <h3>Daftar Antrean Hari Ini</h3>
        <form class="obat-cari" method="GET" action="{{ route('antrean.index') }}">
            <input name="cari" value="{{ $cari }}" placeholder="Cari nomor, kode/nama pasien, atau poli">
            <select name="filter_status" id="filter_status">
                <option value="">Semua status</option>
                @foreach (\App\Models\Antrean::STATUS as $s)
                    <option value="{{ $s }}" @selected($filterStatus === $s)>{{ ucfirst($s) }}</option>
                @endforeach
            </select>
            <button type="submit">Tampilkan</button>
        </form>
        <div class="obat-scroll">
            <table class="obat-table">
                <thead><tr><th>No. Antrean</th><th>Kode Pasien</th><th>Nama Pasien</th><th>Poli</th><th>Status</th><th>Perkiraan tunggu</th>@can('antrean.layani')<th>Tindakan</th>@endcan</tr></thead>
                <tbody>
                @forelse ($daftar as $a)
                    <tr>
                        <td><strong>{{ $a->nomor_antrean }}</strong></td>
                        <td>{{ $a->pasien->kode_pasien }}</td>
                        <td>{{ $a->pasien->nama }}</td>
                        <td>{{ $a->poli->nama_poli }}</td>
                        <td><span class="badge {{ $a->kelasStatus() }}">{{ $a->status }}</span></td>
                        <td>
                            @if ($a->status !== 'menunggu')
                                <span class="teks-bantu">-</span>
                            @elseif (($perkiraan[$a->id] ?? null) === null)
                                <span class="teks-bantu" title="Riwayat pelayanan poli ini belum cukup (minimal 5 pelayanan selesai dalam 4 minggu).">Data belum cukup</span>
                            @elseif ($perkiraan[$a->id] === 0)
                                Segera
                            @else
                                ± {{ $perkiraan[$a->id] }} menit
                            @endif
                        </td>
                        @can('antrean.layani')
                        <td>
                            <div class="aksi-baris">
                                @php
                                    $tombol = ['menunggu' => ['panggil', 'Panggil'], 'dipanggil' => ['mulai', 'Mulai Layani'], 'dilayani' => ['selesai', 'Selesai']];
                                @endphp
                                @isset($tombol[$a->status])
                                    <form method="POST" action="{{ route('antrean.status', $a) }}">
                                        @csrf
                                        <input type="hidden" name="cari" value="{{ $cari }}">
                                        <input type="hidden" name="filter_status" value="{{ $filterStatus }}">
                                        <button name="aksi" value="{{ $tombol[$a->status][0] }}" class="btn-kecil">{{ $tombol[$a->status][1] }}</button>
                                    </form>
                                @endisset
                                @if (in_array($a->status, ['menunggu', 'dipanggil', 'dilayani'], true))
                                    <form method="POST" action="{{ route('antrean.status', $a) }}" onsubmit="return confirm('Batalkan antrean {{ $a->nomor_antrean }}?')">
                                        @csrf
                                        <button name="aksi" value="batal" class="btn-kecil btn-merah">Batal</button>
                                    </form>
                                @endif
                                @if (in_array($a->status, ['selesai', 'batal'], true))
                                    <span class="teks-bantu">Tidak ada tindakan</span>
                                @endif
                            </div>
                        </td>
                        @endcan
                    </tr>
                @empty
                    <tr><td colspan="7">Belum ada antrean hari ini.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <p class="teks-bantu">
            Perkiraan tunggu = (antrean yang sedang dipanggil/dilayani + antrean menunggu di depannya) × lama layanan biasa poli tersebut
            (median 4 minggu terakhir). Angka ini perkiraan, bukan janji waktu.
        </p>
    </section>
@endsection

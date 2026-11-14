@extends('layouts.app')
@section('title', 'Riwayat Status Antrean')

@section('content')
    <h2>Riwayat Perubahan Status Antrean</h2>
    <p class="teks-bantu">Jejak setiap perubahan status antrean beserta petugas yang mengubahnya (200 perubahan terakhir).</p>

    <section class="obat-card">
        <div class="obat-scroll">
            <table class="obat-table">
                <thead><tr><th>No.</th><th>No. Antrean</th><th>Nama Pasien</th><th>Poli</th><th>Status Sebelumnya</th><th>Status Baru</th><th>Petugas</th><th>Waktu Perubahan</th></tr></thead>
                <tbody>
                @forelse ($daftar as $r)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>{{ $r->antrean->nomor_antrean }}</td>
                        <td>{{ $r->antrean->pasien->nama }}</td>
                        <td>{{ $r->antrean->poli->nama_poli }}</td>
                        <td>{{ $r->status_sebelumnya ?? '-' }}</td>
                        <td>{{ $r->status_baru }}</td>
                        <td>{{ $r->petugas->name ?? '-' }}</td>
                        <td>{{ $r->created_at->format('d/m/Y H:i') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="8">Belum ada riwayat perubahan status.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection

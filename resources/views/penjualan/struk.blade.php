<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Struk {{ $jual->nomor_penjualan }}</title>
    <style>
        body { font-family: "Courier New", monospace; background: #f3f5f8; margin: 0; padding: 24px; color: #1b2430; }
        .struk { max-width: 380px; margin: 0 auto; background: #fff; padding: 22px; border: 1px solid #dce4ee; border-radius: 8px; }
        h1 { font-size: 18px; text-align: center; margin: 0; }
        .tengah { text-align: center; font-size: 12px; margin: 4px 0 12px; }
        .garis { border-top: 1px dashed #888; margin: 10px 0; }
        table { width: 100%; font-size: 13px; border-collapse: collapse; }
        td { padding: 3px 0; vertical-align: top; }
        .kanan { text-align: right; }
        .total td { font-weight: bold; font-size: 15px; }
        .info { font-size: 12px; }
        .aksi { max-width: 380px; margin: 16px auto; display: flex; gap: 10px; justify-content: center; font-family: Arial, sans-serif; }
        .aksi a, .aksi button { padding: 8px 14px; border-radius: 8px; border: 1px solid #155eef; background: #fff; color: #155eef; text-decoration: none; font-size: 14px; cursor: pointer; }
        .aksi button { background: #155eef; color: #fff; }
        .berhasil { max-width: 380px; margin: 0 auto 14px; padding: 10px; background: #e2f5e7; color: #166534; border-radius: 8px; font-family: Arial, sans-serif; font-size: 14px; text-align: center; }
        @media print { .aksi, .berhasil { display: none; } body { background: #fff; padding: 0; } .struk { border: none; } }
    </style>
</head>
<body>

@if (request('baru'))
    <div class="berhasil">Penjualan tersimpan. Stok obat sudah diperbarui.</div>
@endif

<div class="struk">
    <h1>INSTALASI FARMASI</h1>
    <p class="tengah">{{ config('app.name') }} - MediStock RS</p>

    <div class="info">
        No. : {{ $jual->nomor_penjualan }}<br>
        Tgl : {{ $jual->created_at->format('d/m/Y H:i') }}<br>
        Pembeli : {{ $jual->nama_pembeli }}<br>
        Jenis : {{ $jual->jenis === 'resep' ? 'Resep (' . $jual->nomor_resep . ')' : 'Tanpa resep' }}<br>
        Petugas : {{ $jual->petugas->name ?? '-' }}
    </div>

    <div class="garis"></div>
    <table>
        @foreach ($jual->detail as $d)
            <tr><td colspan="2">{{ $d->obat->nama_obat }}@if ($d->batch)<br><small>&nbsp;&nbsp;Batch {{ $d->batch->nomor_batch }} · ED {{ $d->batch->tanggal_kedaluwarsa->format('d/m/Y') }}</small>@endif</td></tr>
            <tr>
                <td>&nbsp;&nbsp;{{ $d->jumlah }} {{ $d->obat->satuan }} x Rp {{ number_format($d->harga_satuan, 0, ',', '.') }}</td>
                <td class="kanan">Rp {{ number_format($d->subtotal, 0, ',', '.') }}</td>
            </tr>
        @endforeach
    </table>
    <div class="garis"></div>
    <table>
        <tr class="total"><td>TOTAL</td><td class="kanan">Rp {{ number_format($jual->total, 0, ',', '.') }}</td></tr>
    </table>
    <div class="garis"></div>
    <p class="tengah">Semoga lekas sembuh</p>
</div>

<div class="aksi">
    <a href="{{ route('penjualan.index') }}">Kembali</a>
    <button type="button" onclick="window.print()">Cetak struk</button>
</div>

</body>
</html>

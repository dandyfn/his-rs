@extends('errors.halaman')

@section('kode', '429')
@section('warna', '#b54708')
@section('judul', 'Terlalu banyak percobaan')

@section('ilustrasi')
      <svg width="120" height="140" viewBox="0 0 120 140" role="img" aria-label="Ilustrasi jam pasir, harap menunggu">
        <g style="transform-origin: 60px 70px; animation: balik 3.2s ease-in-out infinite">
          <path d="M28 14h64M28 126h64" stroke="#12345a" stroke-width="8" stroke-linecap="round"></path>
          <path d="M36 18c0 30 48 34 48 52S36 92 36 122h48c0-30-48-34-48-52s48-22 48-52z" fill="#eef4fb" stroke="#12345a" stroke-width="5" stroke-linejoin="round"></path>
          <path d="M44 30h32c-4 14-16 20-16 26-1-6-12-12-16-26z" fill="#2fb0d6"></path>
          <path d="M42 116c4-12 18-16 18-24 1 8 14 12 18 24z" fill="#2fb0d6"></path>
        </g>
      </svg>
@endsection

@section('pesan')
Terlalu banyak permintaan dalam waktu singkat, misalnya salah memasukkan password berulang kali. Tunggu sekitar satu menit, lalu coba lagi.
@endsection

@section('tombol')
<button type="button" class="err-btn" onclick="location.reload()"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 12a9 9 0 1 1-3-6.7L21 8"/><path d="M21 3v5h-5"/></svg> Coba lagi</button>
@endsection

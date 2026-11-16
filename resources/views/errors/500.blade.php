@extends('errors.halaman')

@section('kode', '500')
@section('warna', '#f04438')
@section('judul', 'Terjadi kesalahan di server')

@section('ilustrasi')
      <svg width="240" height="140" viewBox="0 0 240 140" role="img" aria-label="Ilustrasi stetoskop memeriksa server">
        <g style="animation: melayang 2.6s ease-in-out infinite">
          <rect x="120" y="22" width="96" height="100" rx="12" fill="#12345a"></rect>
          <rect x="134" y="38" width="68" height="12" rx="4" fill="#2a4f7c"></rect>
          <rect x="134" y="60" width="68" height="12" rx="4" fill="#2a4f7c"></rect>
          <rect x="134" y="82" width="68" height="12" rx="4" fill="#2a4f7c"></rect>
          <circle cx="194" cy="44" r="3.5" fill="#f04438" style="animation: kedip .8s infinite"></circle>
          <circle cx="194" cy="66" r="3.5" fill="#f04438" style="animation: kedip .8s .3s infinite"></circle>
          <circle cx="194" cy="88" r="3.5" fill="#12b76a"></circle>
        </g>
        <path d="M30 18v26a22 22 0 0 0 44 0V18" fill="none" stroke="#155eef" stroke-width="6" stroke-linecap="round"></path>
        <path d="M52 66v20c0 22 28 26 44 12l18-12" fill="none" stroke="#155eef" stroke-width="6" stroke-linecap="round"></path>
        <circle cx="116" cy="86" r="11" fill="#e8337a" style="animation: kedip 1.4s infinite"></circle>
      </svg>
@endsection

@section('pesan')
Sistem sedang mengalami gangguan. Kesalahan ini sudah tercatat. Silakan coba lagi beberapa saat lagi.
@endsection

@section('tombol')
<button type="button" class="err-btn" onclick="location.reload()"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 12a9 9 0 1 1-3-6.7L21 8"/><path d="M21 3v5h-5"/></svg> Coba lagi</button>
<a class="err-btn garis" href="{{ url('/') }}"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 10.5 12 3l9 7.5V20a1 1 0 0 1-1 1h-5v-6H9v6H4a1 1 0 0 1-1-1z"/></svg> Kembali ke Dashboard</a>
@endsection

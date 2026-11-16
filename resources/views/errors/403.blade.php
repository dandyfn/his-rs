@extends('errors.halaman')

@section('kode', '403')
@section('warna', '#e8337a')
@section('judul', 'Akses ditolak')

@section('ilustrasi')
      <div style="position: relative; width: 200px; height: 170px; display: grid; place-items: center">
        <span aria-hidden="true" style="position: absolute; left: 40px; top: 18px; width: 120px; height: 120px; border-radius: 50%; background: #fde7ef; animation: denyut 2.2s ease-out infinite"></span>
        <svg width="190" height="170" viewBox="0 0 190 170" role="img" aria-label="Ilustrasi dokter bermasker mengangkat telapak tangan, tanda berhenti" style="position: relative">
          <!-- badan: jas putih -->
          <path d="M40 170c0-38 20-60 55-60s55 22 55 60z" fill="#ffffff" stroke="#c9d5e4" stroke-width="3"></path>
          <path d="M95 112v58" stroke="#c9d5e4" stroke-width="3"></path>
          <path d="M80 112l15 22 15-22" fill="#dbeafe" stroke="#c9d5e4" stroke-width="3" stroke-linejoin="round"></path>
          <!-- stetoskop -->
          <path d="M74 120c-6 18 0 30 12 32" fill="none" stroke="#12345a" stroke-width="4" stroke-linecap="round"></path>
          <circle cx="88" cy="152" r="5" fill="#12345a"></circle>
          <!-- leher & kepala -->
          <rect x="86" y="96" width="18" height="18" rx="6" fill="#f1c7a5"></rect>
          <circle cx="95" cy="70" r="30" fill="#f1c7a5"></circle>
          <!-- penutup kepala bedah -->
          <path d="M65 66a30 30 0 0 1 60 0c0-4-6-10-30-10s-30 6-30 10z" fill="#2fb0d6"></path>
          <path d="M66 62a29 26 0 0 1 58 0" fill="#2fb0d6"></path>
          <!-- mata -->
          <circle cx="84" cy="70" r="3.2" fill="#12345a"></circle>
          <circle cx="106" cy="70" r="3.2" fill="#12345a"></circle>
          <path d="M79 63l9 2M111 63l-9 2" stroke="#12345a" stroke-width="2.5" stroke-linecap="round"></path>
          <!-- masker -->
          <path d="M70 78h50v10c0 10-11 17-25 17s-25-7-25-17z" fill="#bfe6f3" stroke="#7cc4dd" stroke-width="2"></path>
          <path d="M74 86h42M76 93h38" stroke="#7cc4dd" stroke-width="2" stroke-linecap="round"></path>
          <path d="M70 80l-6-4M120 80l6-4" stroke="#7cc4dd" stroke-width="2" stroke-linecap="round"></path>
          <!-- lengan dan telapak tangan terangkat: STOP -->
          <g style="transform-origin: 150px 150px; animation: ayun 1.6s ease-in-out infinite">
            <path d="M136 168l10-60" stroke="#c9d5e4" stroke-width="23" stroke-linecap="round"></path>
            <path d="M136 168l10-60" stroke="#ffffff" stroke-width="18" stroke-linecap="round"></path>
            <path d="M136 82c0-6 8-6 8 0v-20c0-6 8-6 8 0v-4c0-6 8-6 8 0v4c0-6 8-6 8 0v34c0 14-8 22-20 22h-4c-8 0-12-6-14-12l-8-16c-3-6 4-10 8-5l6 7z" fill="#f1c7a5" stroke="#d9a982" stroke-width="2" stroke-linejoin="round"></path>
          </g>
        </svg>
      </div>
@endsection

@section('pesan')
@auth
    Peran Anda (<strong>{{ auth()->user()->labelPeran() }}</strong>) tidak memiliki izin untuk membuka halaman ini.
@else
    Anda tidak memiliki izin untuk membuka halaman ini.
@endauth
Hubungi Administrator Sistem jika Anda memerlukan akses.
@endsection

@section('tombol')
<a class="err-btn" href="{{ url('/') }}"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 10.5 12 3l9 7.5V20a1 1 0 0 1-1 1h-5v-6H9v6H4a1 1 0 0 1-1-1z"/></svg> Kembali ke Dashboard</a>
@endsection

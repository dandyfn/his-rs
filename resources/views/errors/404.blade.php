@extends('errors.halaman')

@section('kode', '404')
@section('warna', '#e8337a')
@section('judul', 'Halaman tidak ditemukan')

@section('ilustrasi')
      <svg width="260" height="130" viewBox="0 0 260 130" role="img" aria-label="Ilustrasi garis detak jantung yang terputus dan kaca pembesar">
        <polyline points="0,80 50,80 62,80 72,52 84,106 96,30 108,92 118,80 150,80" fill="none" stroke="#155eef" stroke-width="4" stroke-linecap="round" stroke-linejoin="round" stroke-dasharray="520" style="animation: garis 2.4s ease-in-out infinite alternate"></polyline>
        <circle cx="156" cy="80" r="4" fill="#e8337a" style="animation: kedip 1.2s infinite"></circle>
        <g style="transform-origin: 205px 62px; animation: ayun 2.2s ease-in-out infinite">
          <circle cx="200" cy="58" r="26" fill="#eef4fb" stroke="#12345a" stroke-width="5"></circle>
          <path d="M219 77l20 20" stroke="#12345a" stroke-width="7" stroke-linecap="round"></path>
          <text x="200" y="68" text-anchor="middle" font-size="30" font-weight="700" fill="#e8337a" font-family="Segoe UI, Arial">?</text>
        </g>
      </svg>
@endsection

@section('pesan')
Alamat yang Anda buka tidak ada atau sudah dipindahkan. Periksa kembali alamatnya, atau kembali ke Dashboard.
@endsection

@section('tombol')
<a class="err-btn" href="{{ url('/') }}"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 10.5 12 3l9 7.5V20a1 1 0 0 1-1 1h-5v-6H9v6H4a1 1 0 0 1-1-1z"/></svg> Kembali ke Dashboard</a>
<button type="button" class="err-btn garis" onclick="history.length > 1 ? history.back() : location.assign('{{ url('/') }}')"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 12H5M11 18l-6-6 6-6"/></svg> Halaman sebelumnya</button>
@endsection

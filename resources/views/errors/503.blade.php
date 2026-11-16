@extends('errors.halaman')

@section('kode', '503')
@section('warna', '#155eef')
@section('judul', 'Sedang dalam perawatan')

@section('ilustrasi')
      <svg width="260" height="170" viewBox="0 0 260 170" role="img" aria-label="Ilustrasi pasien yang sedang dirawat, terbaring di ranjang rumah sakit dengan infus">
        <!-- tiang infus -->
        <path d="M222 22v128M206 150h32" stroke="#9aa9bd" stroke-width="4" stroke-linecap="round"></path>
        <path d="M212 22h20" stroke="#9aa9bd" stroke-width="4" stroke-linecap="round"></path>
        <rect x="211" y="26" width="22" height="30" rx="6" fill="#dbeafe" stroke="#7cc4dd" stroke-width="2"></rect>
        <rect x="214" y="38" width="16" height="15" rx="4" fill="#9fd8ec"></rect>
        <circle cx="222" cy="64" r="2.6" fill="#7cc4dd" style="animation: tetes 1.6s ease-in infinite"></circle>
        <path d="M222 58v8c0 14-30 10-58 28" fill="none" stroke="#9fd8ec" stroke-width="2"></path>
        <!-- ranjang -->
        <path d="M22 70v80M22 112h188v38" stroke="#12345a" stroke-width="6" stroke-linecap="round" stroke-linejoin="round" fill="none"></path>
        <rect x="16" y="62" width="14" height="48" rx="5" fill="#12345a"></rect>
        <path d="M19 78h8M23 74v8" stroke="#ffffff" stroke-width="2.5" stroke-linecap="round"></path>
        <circle cx="34" cy="156" r="7" fill="#9aa9bd"></circle>
        <circle cx="198" cy="156" r="7" fill="#9aa9bd"></circle>
        <rect x="30" y="98" width="180" height="16" rx="6" fill="#dbeafe"></rect>
        <!-- bantal & kepala pasien -->
        <rect x="34" y="78" width="46" height="22" rx="10" fill="#ffffff" stroke="#c9d5e4" stroke-width="2"></rect>
        <circle cx="64" cy="82" r="15" fill="#f1c7a5"></circle>
        <path d="M50 78a15 15 0 0 1 28-6c-6 1-16-1-28 6z" fill="#3a2a20"></path>
        <path d="M58 84q3 3 6 0M68 84q3 3 6 0" stroke="#3a2a20" stroke-width="2" stroke-linecap="round" fill="none"></path>
        <!-- selimut -->
        <path d="M74 92c20-10 60-8 130-4v14H74z" fill="#2fb0d6"></path>
        <path d="M74 92c20-10 60-8 130-4" fill="none" stroke="#1a8bb0" stroke-width="2"></path>
        <path d="M104 89l-4 13M140 87l-3 15M176 88l-2 14" stroke="#1a8bb0" stroke-width="1.6" opacity=".6"></path>
        <!-- Zzz -->
        <text x="86" y="62" font-size="15" font-weight="700" fill="#155eef" font-family="Segoe UI, Arial" style="animation: zzz 2.6s ease-out infinite">z</text>
        <text x="98" y="48" font-size="19" font-weight="700" fill="#155eef" font-family="Segoe UI, Arial" style="animation: zzz 2.6s .5s ease-out infinite">z</text>
        <text x="113" y="32" font-size="23" font-weight="700" fill="#155eef" font-family="Segoe UI, Arial" style="animation: zzz 2.6s 1s ease-out infinite">Z</text>
      </svg>
@endsection

@section('pesan')
Sistem {{ config('app.name') }} sedang diperbarui agar lebih baik. Layanan akan kembali dalam beberapa menit.
@endsection

@section('tombol')
<button type="button" class="err-btn" onclick="location.reload()"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 12a9 9 0 1 1-3-6.7L21 8"/><path d="M21 3v5h-5"/></svg> Muat ulang</button>
@endsection

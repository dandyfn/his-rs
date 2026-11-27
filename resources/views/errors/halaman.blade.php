{{-- Kerangka bersama halaman error. Sengaja tanpa menu dan tanpa query database,
     supaya tetap tampil walau pengguna belum login atau sistem sedang bermasalah. --}}
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>@yield('kode') {{ strip_tags(View::yieldContent('judul')) }} - {{ config('app.name') }}</title>
    <link rel="icon" type="image/png" href="{{ asset('images/favicon.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@400;500;600&display=swap">
    <link rel="stylesheet" href="{{ asset('css/halaman-error.css') }}">
</head>
<body>
    <a class="err-atas" href="{{ url('/') }}">
        <img src="{{ asset('images/logo-irs.png') }}" alt="" width="30" height="27">
        <span>{{ config('app.name') }}</span>
    </a>
    <main class="err-isi">
        <div class="err-kartu">
            @yield('ilustrasi')
            <span class="err-kode" style="color: @yield('warna', '#e8337a')">ERROR @yield('kode')</span>
            <h1>@yield('judul')</h1>
            <p>@yield('pesan')</p>
            <div class="err-tombol">@yield('tombol')</div>
        </div>
    </main>
</body>
</html>

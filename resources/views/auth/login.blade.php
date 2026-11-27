<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Masuk - {{ config('app.name') }}</title>
    <link rel="icon" type="image/png" href="{{ asset('images/favicon.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@400;500;600&display=swap">
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    <link rel="stylesheet" href="{{ asset('css/medistock.css') }}">
    <link rel="stylesheet" href="{{ asset('css/tema.css') }}">
</head>
<body class="halaman-masuk">

@include('partials.status-koneksi')

<main class="masuk-wrap">
    <div class="masuk-kartu">

        <section class="masuk-info" aria-label="Tentang sistem">
            <div class="masuk-merek">
                <span><img src="{{ asset('images/logo-irs.png') }}" alt="" width="32" height="29"></span>
                <span>{{ config('app.name') }}</span>
            </div>
            <div>
                <h1>Sistem Informasi Rumah Sakit</h1>
                <p>Satu akun untuk pekerjaan sesuai peran Anda.</p>
            </div>
            <ul class="masuk-modul">
                <li><b>Pendaftaran pasien</b><small>Identitas dan verifikasi</small></li>
                <li><b>Antrean rawat jalan</b><small>Nomor dan status per poli</small></li>
                <li><b>Stok obat</b><small>Batch, penjualan, dan pengadaan</small></li>
            </ul>
        </section>

        <section class="masuk-form" aria-labelledby="judul-masuk">
            <div>
                <h2 id="judul-masuk">Masuk</h2>
                <p class="sub">Gunakan username dan kata sandi dari admin.</p>
            </div>

            @if ($errors->any())
                <div class="obat-alert error" role="alert">{{ $errors->first() }}</div>
            @endif

            <form method="POST" action="{{ route('login') }}">
                @csrf
                <label for="username">Username</label>
                <input id="username" type="text" name="username" value="{{ old('username') }}" required autofocus autocomplete="username">

                <label for="password">Kata sandi</label>
                <input id="password" type="password" name="password" required autocomplete="current-password">

                <label class="masuk-ingat"><input type="checkbox" name="ingat" value="1"> Ingat saya di perangkat ini</label>

                <button type="submit">Masuk</button>
            </form>

            <p class="masuk-catatan">Lupa kata sandi? Hubungi admin sistem.</p>
        </section>

    </div>
</main>

<footer class="masuk-kaki">Hospital Information System · Pendaftaran · Antrean Rawat Jalan · MediStock</footer>
</body>
</html>

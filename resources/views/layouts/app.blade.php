<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') - {{ config('app.name') }}</title>
    <link rel="icon" type="image/png" href="{{ asset('images/favicon.png') }}">
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    <link rel="stylesheet" href="{{ asset('css/medistock.css') }}">
    @stack('styles')
</head>
<body class="halaman-app">

@php
    // Menu per subsistem: [route => [label, izin, ikon]]. Hanya menu yang diizinkan untuk peran pengguna yang tampil (US-11).
    $ikonGrup = ['Pendaftaran' => 'pendaftaran', 'Antrean' => 'antrean', 'Obat' => 'obat'];
    $menu = [
        'Pendaftaran' => [
            'pasien.index' => ['Data Pasien', 'pasien.lihat', 'pasien'],
            'identitas.index' => ['Identitas & Verifikasi', 'identitas.lihat', 'identitas'],
        ],
        'Antrean' => [
            'antrean.index' => ['Antrean Hari Ini', 'antrean.lihat', 'daftar'],
            'poli.index' => ['Data Poli', 'poli.lihat', 'poli'],
            'riwayat-antrean.index' => ['Riwayat Status', 'antrean.analisis', 'riwayat'],
            'rekomendasi-antrean.index' => ['Rekomendasi Antrean', 'antrean.analisis', 'rekomendasi'],
            'laporan-antrean.index' => ['Laporan Antrean', 'antrean.analisis', 'laporan'],
        ],
        'Obat' => [
            'obat.index' => ['Stok Obat', 'obat.lihat', 'stok'],
            'batch.index' => ['Batch & Kedaluwarsa', 'obat.lihat', 'batch'],
            'supplier.index' => ['Supplier', 'supplier.lihat', 'supplier'],
            'pembelian.index' => ['Pesanan & Penerimaan', 'pengadaan.lihat', 'pesanan'],
            'penjualan.index' => ['Penjualan Obat', 'penjualan.lihat', 'penjualan'],
            'retur.index' => ['Retur Obat', 'retur', 'retur'],
            'analisis.index' => ['Analisis Pemakaian', 'analisis.obat', 'analisis'],
            'rekomendasi.index' => ['Rekomendasi Pengadaan', 'analisis.obat', 'rekomendasi'],
            'laporan-obat.index' => ['Laporan Obat', 'laporan.obat', 'laporan-unduh'],
        ],
    ];
    $menu = collect($menu)->map(fn ($item) => collect($item)->filter(fn ($m) => auth()->user()->can($m[1])))
        ->filter(fn ($item) => $item->isNotEmpty());
    $aktif = fn ($rute) => request()->routeIs(Str::before($rute, '.') . '.*');
@endphp

<header class="main-header">
    <div class="header-container">
        <a href="{{ route('dashboard') }}" class="brand">
            <img src="{{ asset('images/logo-irs.png') }}" alt="Logo {{ config('app.name') }}" class="brand-logo" width="60" height="54">
            <span class="brand-teks">
                <span class="brand-nama">{{ config('app.name') }}</span>
                <span class="brand-tagline">{{ config('app.tagline') }}</span>
            </span>
        </a>

        <nav class="main-nav" aria-label="Menu utama">
            <a href="{{ route('dashboard') }}" @class(['active' => request()->routeIs('dashboard')])>
                <x-ikon nama="dashboard" /> Dashboard
            </a>

            @foreach ($menu as $grup => $item)
                <div class="nav-grup">
                    <button type="button" @class(['nav-grup-judul', 'active' => $item->keys()->contains($aktif)]) aria-haspopup="true">
                        <x-ikon :nama="$ikonGrup[$grup]" /> {{ $grup }} <x-ikon nama="panah-bawah" :ukuran="12" class="ikon-panah" />
                    </button>
                    <div class="nav-dropdown">
                        @foreach ($item as $rute => [$label, $izin, $ikon])
                            <a href="{{ route($rute) }}" @class(['active' => $aktif($rute)])>
                                <span class="ikon-kotak"><x-ikon :nama="$ikon" :ukuran="16" /></span>
                                {{ $label }}
                            </a>
                        @endforeach
                    </div>
                </div>
            @endforeach

            @can('pengguna.kelola')
                <a href="{{ route('pengguna.index') }}" @class(['active' => request()->routeIs('pengguna.*')])>
                    <x-ikon nama="pengguna" /> Pengguna
                </a>
            @endcan

            <span class="nav-akun" title="{{ auth()->user()->labelPeran() }}">
                <x-ikon nama="akun" :ukuran="26" />
                <span class="nav-akun-teks">{{ auth()->user()->name }}<small>{{ auth()->user()->labelPeran() }}</small></span>
            </span>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="nav-logout"><x-ikon nama="keluar" /> Logout</button>
            </form>
        </nav>
    </div>
</header>

<main class="obat-wrap">
    @if (session('pesan'))
        <div class="obat-alert">{{ session('pesan') }}</div>
    @endif

    @if ($errors->any())
        <div class="obat-alert error">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @yield('content')
</main>

@stack('scripts')
</body>
</html>

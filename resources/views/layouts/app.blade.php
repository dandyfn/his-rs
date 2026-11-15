<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') - {{ config('app.name') }}</title>
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    <link rel="stylesheet" href="{{ asset('css/medistock.css') }}">
    @stack('styles')
</head>
<body>

@php
    // Menu per subsistem: [route => [label, izin]]. Hanya menu yang diizinkan untuk peran pengguna yang tampil (US-11).
    $menu = [
        'Pendaftaran' => [
            'pasien.index' => ['Data Pasien', 'pasien.lihat'],
            'identitas.index' => ['Identitas & Verifikasi', 'identitas.lihat'],
        ],
        'Antrean' => [
            'antrean.index' => ['Antrean Hari Ini', 'antrean.lihat'],
            'poli.index' => ['Data Poli', 'poli.lihat'],
            'riwayat-antrean.index' => ['Riwayat Status', 'antrean.analisis'],
            'rekomendasi-antrean.index' => ['Rekomendasi Antrean', 'antrean.analisis'],
            'laporan-antrean.index' => ['Laporan Antrean', 'antrean.analisis'],
        ],
        'Obat' => [
            'obat.index' => ['Stok Obat', 'obat.lihat'],
            'batch.index' => ['Batch & Kedaluwarsa', 'obat.lihat'],
            'supplier.index' => ['Supplier', 'supplier.lihat'],
            'pembelian.index' => ['Pesanan & Penerimaan', 'pengadaan.lihat'],
            'penjualan.index' => ['Penjualan Obat', 'penjualan.lihat'],
            'retur.index' => ['Retur Obat', 'retur'],
            'analisis.index' => ['Analisis Pemakaian', 'analisis.obat'],
            'rekomendasi.index' => ['Rekomendasi Pengadaan', 'analisis.obat'],
            'laporan-obat.index' => ['Laporan Obat', 'laporan.obat'],
        ],
    ];
    $menu = collect($menu)->map(fn ($item) => collect($item)->filter(fn ($m) => auth()->user()->can($m[1])))
        ->filter(fn ($item) => $item->isNotEmpty());
    $aktif = fn ($rute) => request()->routeIs(Str::before($rute, '.') . '.*');
@endphp

<header class="main-header">
    <div class="header-container">
        <div class="brand">
            <h1>{{ config('app.name') }}</h1>
            <p>Hospital Information System</p>
        </div>

        <nav class="main-nav">
            <a href="{{ route('dashboard') }}" @class(['active' => request()->routeIs('dashboard')])>Dashboard</a>

            @foreach ($menu as $grup => $item)
                <div class="nav-grup">
                    <button type="button" @class(['nav-grup-judul', 'active' => $item->keys()->contains($aktif)])>{{ $grup }} ▾</button>
                    <div class="nav-dropdown">
                        @foreach ($item as $rute => [$label])
                            <a href="{{ route($rute) }}" @class(['active' => $aktif($rute)])>{{ $label }}</a>
                        @endforeach
                    </div>
                </div>
            @endforeach

            @can('pengguna.kelola')
                <a href="{{ route('pengguna.index') }}" @class(['active' => request()->routeIs('pengguna.*')])>Pengguna</a>
            @endcan

            <span class="nav-akun" title="{{ auth()->user()->labelPeran() }}">{{ auth()->user()->name }}<small>{{ auth()->user()->labelPeran() }}</small></span>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="nav-logout">Logout</button>
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

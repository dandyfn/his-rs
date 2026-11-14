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
    $menu = [
        'Pendaftaran' => [
            'pasien.index' => 'Data Pasien',
            'identitas.index' => 'Identitas & Verifikasi',
        ],
        'Antrean' => [
            'antrean.index' => 'Antrean Hari Ini',
            'poli.index' => 'Data Poli',
            'riwayat-antrean.index' => 'Riwayat Status',
            'rekomendasi-antrean.index' => 'Rekomendasi Antrean',
            'laporan-antrean.index' => 'Laporan Antrean',
        ],
        'Obat' => [
            'obat.index' => 'Stok Obat',
            'supplier.index' => 'Supplier',
            'pembelian.index' => 'Pembelian Obat',
            'penjualan.index' => 'Penjualan Obat',
            'rekomendasi.index' => 'Rekomendasi Pengadaan',
        ],
    ];
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
                    <button type="button" @class(['nav-grup-judul', 'active' => collect($item)->keys()->contains($aktif)])>{{ $grup }} ▾</button>
                    <div class="nav-dropdown">
                        @foreach ($item as $rute => $label)
                            <a href="{{ route($rute) }}" @class(['active' => $aktif($rute)])>{{ $label }}</a>
                        @endforeach
                    </div>
                </div>
            @endforeach

            @can('admin')
                <a href="{{ route('pengguna.index') }}" @class(['active' => request()->routeIs('pengguna.*')])>Pengguna</a>
            @endcan

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

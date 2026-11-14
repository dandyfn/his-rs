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

<header class="main-header">
    <div class="header-container">
        <div class="brand">
            <h1>{{ config('app.name') }}</h1>
            <p>Hospital Information System</p>
        </div>

        <nav class="main-nav">
            @php
                $menu = [
                    'dashboard' => 'Dashboard',
                    'obat.index' => 'Stok Obat',
                    'supplier.index' => 'Supplier',
                    'pembelian.index' => 'Pembelian Obat',
                    'penjualan.index' => 'Penjualan Obat',
                    'rekomendasi.index' => 'Rekomendasi Obat',
                ];
            @endphp
            @foreach ($menu as $rute => $label)
                <a href="{{ route($rute) }}" @class(['active' => request()->routeIs(Str::before($rute, '.') . '*')])>{{ $label }}</a>
            @endforeach
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

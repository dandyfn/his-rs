<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login - {{ config('app.name') }}</title>
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    <link rel="stylesheet" href="{{ asset('css/medistock.css') }}">
</head>
<body>
<div class="login-wrap">
    <section class="obat-card login-card">
        <h1>{{ config('app.name') }}</h1>
        <p class="teks-bantu">Hospital Information System. Masuk dengan akun petugas.</p>

        @if ($errors->any())
            <div class="obat-alert error">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('login') }}">
            @csrf
            <div class="obat-grid">
                <div>
                    <label for="username">Username</label>
                    <input id="username" name="username" value="{{ old('username') }}" required autofocus autocomplete="username">
                </div>
                <div>
                    <label for="password">Password</label>
                    <input id="password" type="password" name="password" required autocomplete="current-password">
                </div>
            </div>
            <p class="teks-bantu"><label style="font-weight:400"><input type="checkbox" name="ingat" value="1" style="width:auto"> Ingat saya</label></p>
            <button type="submit">Masuk</button>
        </form>
    </section>
</div>
</body>
</html>

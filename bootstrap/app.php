<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Render (dan hosting sejenis) meneruskan request lewat proxy HTTPS.
        // Tanpa ini Laravel mengira situsnya http://, sehingga CSS/gambar diblokir browser.
        $middleware->trustProxies(at: '*');

        // Halaman tidak disimpan di cache browser, supaya tombol mundur tidak
        // menampilkan halaman login lama (setelah login) atau data pasien (setelah logout).
        $middleware->web(append: [\App\Http\Middleware\JanganSimpanCache::class]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();

<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Melarang browser menyimpan halaman di cache / riwayat (back-forward cache).
 *
 * Tanpa ini, tombol "mundur" setelah login menampilkan salinan lama halaman login
 * (seolah-olah sudah logout), dan setelah logout tombol mundur bisa menampilkan
 * salinan halaman berisi data pasien. Dengan no-store, browser selalu meminta
 * halaman baru ke server: yang sudah login diarahkan ke Dashboard, yang sudah
 * logout diarahkan ke halaman login.
 */
class JanganSimpanCache
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0, private');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', '0');

        return $response;
    }
}

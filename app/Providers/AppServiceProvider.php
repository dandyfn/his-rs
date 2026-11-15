<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Administrator sistem: semua akses.
        Gate::define('admin', fn (User $user) => $user->role === 'admin');

        // Hak akses per peran (config/hak_akses.php) -> Gate, dipakai di route, menu, dan view.
        foreach (config('hak_akses.izin') as $izin => $peran) {
            Gate::define($izin, fn (User $user) => $user->role === 'admin' || in_array($user->role, $peran, true));
        }

        // Nama hari dan bulan dalam bahasa Indonesia
        Carbon::setLocale('id');
    }
}

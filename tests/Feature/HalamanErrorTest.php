<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Halaman error beranimasi, halaman offline, dan Service Worker. */
class HalamanErrorTest extends TestCase
{
    use RefreshDatabase;

    public function test_404_tampil_halaman_kustom(): void
    {
        $this->get('/halaman-tidak-ada')->assertNotFound()
            ->assertSee('Halaman tidak ditemukan')->assertSee('halaman-error.css');
    }

    public function test_403_menyebut_peran_pengguna(): void
    {
        $this->seed(DatabaseSeeder::class);
        $apotek = User::where('username', 'apotek')->first();
        $this->actingAs($apotek)->get('/rekomendasi-obat')->assertForbidden()
            ->assertSee('Akses ditolak')->assertSee('Petugas Apotek');
    }

    public function test_offline_dan_service_worker_tersedia(): void
    {
        $this->assertFileExists(public_path('offline.html'));
        $this->assertStringContainsString('Tidak ada koneksi internet', file_get_contents(public_path('offline.html')));
        $this->assertStringContainsString("'/offline.html'", file_get_contents(public_path('sw.js')));
    }

    public function test_halaman_login_memuat_status_koneksi(): void
    {
        $this->get('/login')->assertOk()->assertSee('koneksi-putus', false)->assertSee('sw.js');
    }
}

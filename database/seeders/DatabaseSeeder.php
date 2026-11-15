<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Akun demo sesuai tabel aktor HIS (satu akun per peran).
     * Password demo = username + "123". GANTI sebelum dipakai sungguhan.
     */
    public const AKUN_DEMO = [
        // username => [nama, peran]
        'admin' => ['Administrator Sistem', 'admin'],
        'manajemen' => ['Manajemen RS (demo)', 'manajemen'],
        'pendaftaran' => ['Petugas Pendaftaran (demo)', 'pendaftaran'],
        'antrean' => ['Petugas Antrean Poli (demo)', 'antrean'],
        'dokter' => ['dr. Tenaga Medis (demo)', 'dokter'],
        'apoteker' => ['Apoteker (demo)', 'apoteker'],
        'apotek' => ['Petugas Apotek (demo)', 'apotek'],
        'gudang' => ['Staf Gudang (demo)', 'gudang'],
    ];

    public function run(): void
    {
        foreach (self::AKUN_DEMO as $username => [$nama, $peran]) {
            User::updateOrCreate(['username' => $username], [
                'name' => $nama,
                'email' => $username . '@his-rs.test',
                'password' => $username === 'admin' ? env('ADMIN_PASSWORD', 'admin123') : $username . '123',
                'role' => $peran,
                'aktif' => true,
            ]);
        }

        $this->call([
            PendaftaranAntreanDemoSeeder::class,
            MediStockDemoSeeder::class,
        ]);
    }
}

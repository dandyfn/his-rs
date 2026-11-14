<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * php artisan db:seed  -> akun admin & petugas + data demo ketiga subsistem
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['username' => 'admin'],
            [
                'name' => 'Apoteker Demo',
                'email' => 'admin@his-rs.test',
                'password' => env('ADMIN_PASSWORD', 'admin123'),
                'role' => 'admin',
                'aktif' => true,
            ]
        );

        User::updateOrCreate(
            ['username' => 'petugas'],
            [
                'name' => 'Petugas Pendaftaran',
                'email' => 'petugas@his-rs.test',
                'password' => env('PETUGAS_PASSWORD', 'petugas123'),
                'role' => 'petugas',
                'aktif' => true,
            ]
        );

        $this->call([
            PendaftaranAntreanDemoSeeder::class,
            MediStockDemoSeeder::class,
        ]);
    }
}

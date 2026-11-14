<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * php artisan db:seed  -> akun admin + data demo subsistem obat
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

        $this->call(MediStockDemoSeeder::class);
    }
}

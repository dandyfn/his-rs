<?php

namespace Database\Seeders;

use App\Models\Obat;
use App\Models\Pasien;
use App\Models\Supplier;
use App\Models\TransaksiStok;
use Illuminate\Database\Seeder;

/**
 * Data contoh untuk demo fitur Rekomendasi Obat (DDDM):
 * 4 obat dengan pola pemakaian berbeda selama 89 hari terakhir.
 * Aman dijalankan berulang (tidak menggandakan data).
 */
class MediStockDemoSeeder extends Seeder
{
    public function run(): void
    {
        Supplier::firstOrCreate(['kode_supplier' => 'SUP-DEMO-01'], [
            'nama_supplier' => 'PT Sehat Farma (contoh)', 'kontak' => '0341-000111', 'alamat' => 'Malang',
        ]);

        Pasien::firstOrCreate(['kode_pasien' => 'P-0001'], ['nama' => 'Siti Aminah']);
        Pasien::firstOrCreate(['kode_pasien' => 'P-0002'], ['nama' => 'Budi Santoso']);

        // [kode, nama, satuan, stok, harga, pola pemakaian harian]
        $daftar = [
            ['OBT-DEMO-01', 'Paracetamol 500 mg', 'strip', 80, 5000, fn ($n) => 8 + ($n % 5)],
            ['OBT-DEMO-02', 'Amoxicillin 500 mg', 'strip', 45, 12000, fn ($n) => 4 + ($n % 5)],
            ['OBT-DEMO-03', 'Antasida tablet', 'strip', 70, 6000, fn ($n) => 3 + ($n % 5)],
            ['OBT-DEMO-04', 'Salep kulit 10 g', 'tube', 15, 18000, fn ($n) => $n % 10 === 0 ? 1 : 0],
        ];

        foreach ($daftar as [$kode, $nama, $satuan, $stok, $harga, $pola]) {
            $obat = Obat::firstOrCreate(['kode_obat' => $kode], [
                'nama_obat' => $nama, 'satuan' => $satuan, 'stok' => $stok, 'harga_jual' => $harga,
            ]);

            if ($obat->transaksiStok()->where('sumber', 'demo')->exists()) {
                continue;
            }

            $baris = [];
            for ($n = 1; $n <= 89; $n++) {
                $jumlah = $pola($n);
                if ($jumlah > 0) {
                    $waktu = now()->subDays($n);
                    $baris[] = [
                        'obat_id' => $obat->id, 'jenis' => 'keluar', 'jumlah' => $jumlah,
                        'keterangan' => 'Data contoh demo DDDM', 'sumber' => 'demo',
                        'created_at' => $waktu, 'updated_at' => $waktu,
                    ];
                }
            }
            TransaksiStok::insert($baris);
        }
    }
}

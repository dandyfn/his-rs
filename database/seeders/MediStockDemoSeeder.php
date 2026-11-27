<?php

namespace Database\Seeders;

use App\Models\BatchObat;
use App\Models\Obat;
use App\Models\Pasien;
use App\Models\Pembelian;
use App\Models\Penerimaan;
use App\Models\Supplier;
use App\Models\TransaksiStok;
use App\Models\User;
use App\Services\StokObatService;
use Illuminate\Database\Seeder;

/**
 * Data contoh MediStock RS untuk demo:
 * - obat dengan stok per batch, termasuk batch kedaluwarsa dan hampir kedaluwarsa (FR-05, FR-07)
 * - pemakaian ±6 bulan untuk grafik (FR-13) dan rekomendasi pengadaan (FR-14)
 * - pesanan pengadaan: diterima lengkap, diterima sebagian, dan menunggu barang (UC-04, UC-06)
 * - satu obat dengan data pemakaian < 1 bulan (UC-11 alur alternatif)
 * Aman dijalankan berulang (tidak menggandakan data).
 */
class MediStockDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (Obat::where('kode_obat', 'OBT-DEMO-01')->exists()) {
            return;
        }

        $supplier = Supplier::firstOrCreate(['kode_supplier' => 'SUP-DEMO-01'], [
            'nama_supplier' => 'PT Sehat Farma (contoh)', 'kontak' => '0341-000111', 'alamat' => 'Malang',
        ]);
        Supplier::firstOrCreate(['kode_supplier' => 'SUP-DEMO-02'], [
            'nama_supplier' => 'CV Medika Jaya (contoh)', 'kontak' => '0341-000222', 'alamat' => 'Surabaya',
        ]);
        $apoteker = User::where('username', 'apoteker')->value('id');
        $gudang = User::where('username', 'gudang')->value('id');

        foreach (self::daftarObat() as [$kode, $nama, $satuan, $harga, $pola, $batch]) {
            $obat = Obat::create([
                'kode_obat' => $kode, 'nama_obat' => $nama, 'satuan' => $satuan, 'harga_jual' => $harga,
                'stok' => collect($batch)->sum(1), 'aktif' => true,
            ]);
            foreach ($batch as [$nomor, $sisa, $ed]) {
                BatchObat::create([
                    'obat_id' => $obat->id, 'nomor_batch' => $nomor, 'tanggal_kedaluwarsa' => today()->addDays($ed),
                    'jumlah_awal' => $sisa, 'sisa' => $sisa,
                ]);
            }

            self::isiRiwayatPemakaian($obat->id, $pola);
        }

        // Obat baru dengan data pemakaian < 1 bulan -> rekomendasi belum dapat dihitung (UC-11).
        $vitamin = Obat::create([
            'kode_obat' => 'OBT-DEMO-05', 'nama_obat' => 'Vitamin C 500 mg', 'satuan' => 'strip',
            'harga_jual' => 4000, 'stok' => 0, 'aktif' => true,
        ]);

        $stok = app(StokObatService::class);

        // Pesanan 1: Vitamin C diterima lengkap dari supplier.
        $po = $stok->buatPesanan(['supplier_id' => $supplier->id, 'obat_id' => $vitamin->id, 'jumlah' => 60,
            'harga_satuan' => 2800, 'tanggal_pembelian' => today()->subDays(14)->toDateString(),
            'keterangan' => 'Obat baru (contoh)'], $apoteker);
        $stok->terimaBarang($po, ['jumlah' => 60, 'nomor_batch' => 'B-VTC-2611', 'tanggal_kedaluwarsa' => today()->addDays(540)->toDateString(),
            'tanggal_terima' => today()->subDays(10)->toDateString(), 'keterangan' => 'Surat jalan SJ-001'], $gudang);
        self::isiPemakaianObatBaru($vitamin->id);

        // Pesanan 2: Salep dipesan 30, baru diterima 15 (diterima sebagian).
        $salep = Obat::where('kode_obat', 'OBT-DEMO-04')->first();
        $po2 = $stok->buatPesanan(['supplier_id' => $supplier->id, 'obat_id' => $salep->id, 'jumlah' => 30,
            'harga_satuan' => 12500, 'tanggal_pembelian' => today()->subDays(5)->toDateString()], $apoteker);
        $stok->terimaBarang($po2, ['jumlah' => 15, 'nomor_batch' => 'B-SLP-2611', 'tanggal_kedaluwarsa' => today()->addDays(700)->toDateString(),
            'tanggal_terima' => today()->subDays(2)->toDateString()], $gudang);

        // Pesanan 3: Amoxicillin menunggu barang (untuk demo UC-06 "Terima barang").
        $stok->buatPesanan(['supplier_id' => $supplier->id, 'obat_id' => Obat::where('kode_obat', 'OBT-DEMO-02')->value('id'),
            'jumlah' => 100, 'harga_satuan' => 8500, 'tanggal_pembelian' => today()->subDay()->toDateString(),
            'keterangan' => 'Berdasarkan rekomendasi pengadaan (contoh)'], $apoteker);

        // Contoh penjualan untuk riwayat pembelian per pasien (FR-12), memakai stok Vitamin C.
        $apotek = User::where('username', 'apotek')->value('id');
        foreach (Pasien::orderBy('id')->take(2)->get() as $i => $pasien) {
            $stok->prosesPenjualan(['pasien_id' => $pasien->id, 'nama_pembeli' => $pasien->nama,
                'jenis' => 'resep', 'nomor_resep' => 'RSP-DEMO-' . ($i + 1)], [$vitamin->id => 2 + $i], $apotek);
        }
    }

    /** Penanda riwayat pemakaian simulasi di kolom transaksi_stok_obat.sumber. */
    public const SUMBER = 'demo';

    /** Batch hasil penerimaan barang demo: [nomor batch => ED (hari dari sekarang)]. */
    public const BATCH_PENERIMAAN = ['B-VTC-2611' => 540, 'B-SLP-2611' => 700];

    /**
     * Obat demo: [kode, nama, satuan, harga, pola pemakaian harian, batch: [nomor, sisa, ED (hari dari sekarang)]].
     * Dipakai seeder dan perintah demo:segarkan.
     */
    public static function daftarObat(): array
    {
        return [
            ['OBT-DEMO-01', 'Paracetamol 500 mg', 'strip', 5000, fn ($n) => 8 + ($n % 5),
                [['B-PCT-2501', 30, 45], ['B-PCT-2602', 50, 400]]],
            ['OBT-DEMO-02', 'Amoxicillin 500 mg', 'strip', 12000, fn ($n) => 4 + ($n % 5),
                [['B-AMX-2601', 45, 75]]],
            ['OBT-DEMO-03', 'Antasida tablet', 'strip', 6000, fn ($n) => 3 + ($n % 5),
                [['B-ANT-2409', 10, -5], ['B-ANT-2603', 60, 300]]],
            ['OBT-DEMO-04', 'Salep kulit 10 g', 'tube', 18000, fn ($n) => $n % 10 === 0 ? 1 : 0,
                [['B-SLP-2604', 15, 200]]],
        ];
    }

    /**
     * Riwayat pemakaian ±6 bulan (hari ke-90 dilewati supaya jendela 90 hari tetap tepat).
     * Bulan-bulan lama sedikit lebih rendah agar grafik memperlihatkan tren naik.
     */
    public static function isiRiwayatPemakaian(int $obatId, callable $pola): void
    {
        $baris = [];
        for ($n = 1; $n <= 179; $n++) {
            if ($n === 90) {
                continue;
            }
            $jumlah = $n < 90 ? $pola($n) : (int) floor($pola($n) * 0.8);
            if ($jumlah > 0) {
                $waktu = now()->subDays($n)->setTime(10, 0);
                $baris[] = [
                    'obat_id' => $obatId, 'jenis' => 'keluar', 'jumlah' => $jumlah,
                    'keterangan' => 'Data contoh pemakaian (demo DDDM)', 'sumber' => self::SUMBER,
                    'created_at' => $waktu, 'updated_at' => $waktu,
                ];
            }
        }
        foreach (array_chunk($baris, 100) as $potong) {
            TransaksiStok::insert($potong);
        }
    }

    /** Obat baru dengan data pemakaian < 1 bulan -> rekomendasi belum dapat dihitung (UC-11). */
    public static function isiPemakaianObatBaru(int $obatId): void
    {
        for ($n = 1; $n <= 9; $n++) {
            TransaksiStok::insert(['obat_id' => $obatId, 'jenis' => 'keluar', 'jumlah' => 2,
                'keterangan' => 'Data contoh pemakaian (demo DDDM)', 'sumber' => self::SUMBER,
                'created_at' => now()->subDays($n), 'updated_at' => now()->subDays($n)]);
        }
    }
}

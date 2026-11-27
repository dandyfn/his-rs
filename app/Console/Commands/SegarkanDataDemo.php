<?php

namespace App\Console\Commands;

use App\Models\Antrean;
use App\Models\BatchObat;
use App\Models\Obat;
use App\Models\TransaksiStok;
use Database\Seeders\MediStockDemoSeeder;
use Database\Seeders\PendaftaranAntreanDemoSeeder;
use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;
use Illuminate\Support\Facades\DB;

/**
 * Menyegarkan data demo supaya tanggalnya kembali relatif ke HARI INI.
 *
 * Masalah yang diselesaikan: data contoh dibuat relatif terhadap hari seeder dijalankan
 * ("hari ini", "5 hari lalu"). Makin lama data itu makin tua: antrean hari ini kosong,
 * batch "hampir kedaluwarsa" sudah lewat, riwayat pemakaian keluar dari jendela 90 hari.
 *
 * Yang dilakukan (hanya pada data bertanda demo):
 *  - antrean bertanda sumber = 'demo' dihapus lalu dibuat ulang (riwayat 4 minggu + hari ini);
 *  - riwayat pemakaian obat bertanda sumber = 'demo' dihapus lalu dibuat ulang;
 *  - tanggal kedaluwarsa batch demo dikembalikan ke skenario semula.
 * Data yang diinput petugas lewat aplikasi TIDAK disentuh.
 *
 * Pemakaian:  php artisan demo:segarkan
 *             php artisan demo:segarkan --force     (tanpa pertanyaan, mis. di server)
 */
class SegarkanDataDemo extends Command
{
    use ConfirmableTrait;

    protected $signature = 'demo:segarkan
        {--force : Jalankan tanpa konfirmasi (juga di environment production)}';

    protected $description = 'Menyegarkan data demo (antrean, riwayat pemakaian obat, batch demo) agar relatif ke hari ini';

    public function handle(): int
    {
        if (Obat::where('kode_obat', 'OBT-DEMO-01')->doesntExist()) {
            $this->error('Data demo belum ada. Jalankan dulu: php artisan db:seed');

            return self::FAILURE;
        }

        if (! $this->confirmToProceed('Data demo akan dibuat ulang (data input petugas tidak disentuh).', fn () => true)) {
            return self::FAILURE;
        }

        DB::transaction(function () {
            // 1) Antrean: hanya baris bertanda demo; riwayat_status-nya ikut terhapus (cascade)
            $jumlahHapus = Antrean::where('sumber', PendaftaranAntreanDemoSeeder::SUMBER)->delete();
            PendaftaranAntreanDemoSeeder::buatAntreanDemo();
            $this->info("Antrean: {$jumlahHapus} baris lama dihapus, " . Antrean::where('sumber', PendaftaranAntreanDemoSeeder::SUMBER)->count() . ' baris demo dibuat ulang.');

            // 2) Riwayat pemakaian obat
            TransaksiStok::where('sumber', MediStockDemoSeeder::SUMBER)->delete();
            foreach (MediStockDemoSeeder::daftarObat() as [$kode, , , , $pola]) {
                if ($id = Obat::where('kode_obat', $kode)->value('id')) {
                    MediStockDemoSeeder::isiRiwayatPemakaian($id, $pola);
                }
            }
            if ($id = Obat::where('kode_obat', 'OBT-DEMO-05')->value('id')) {
                MediStockDemoSeeder::isiPemakaianObatBaru($id);
            }
            $this->info('Riwayat pemakaian obat demo dibuat ulang.');

            // 3) Tanggal kedaluwarsa batch demo kembali ke skenario semula
            $ed = MediStockDemoSeeder::BATCH_PENERIMAAN;
            foreach (MediStockDemoSeeder::daftarObat() as [, , , , , $batch]) {
                foreach ($batch as [$nomor, , $hari]) {
                    $ed[$nomor] = $hari;
                }
            }
            $diubah = 0;
            foreach ($ed as $nomor => $hari) {
                $diubah += BatchObat::where('nomor_batch', $nomor)
                    ->whereIn('obat_id', Obat::where('kode_obat', 'like', 'OBT-DEMO-%')->select('id'))
                    ->update(['tanggal_kedaluwarsa' => today()->addDays($hari)->toDateString()]);
            }
            $this->info("Tanggal kedaluwarsa {$diubah} batch demo disesuaikan.");
        });

        if ($dariPetugas = Antrean::whereNull('sumber')->count()) {
            $this->line("{$dariPetugas} antrean yang didaftarkan petugas tidak disentuh.");
        }
        $this->info('Selesai. Data demo sekarang relatif ke ' . today()->translatedFormat('l, d F Y') . '.');

        return self::SUCCESS;
    }
}

<?php

namespace Tests\Feature;

use App\Models\Antrean;
use App\Models\BatchObat;
use App\Models\Pasien;
use App\Models\Poli;
use App\Models\TransaksiStok;
use App\Models\User;
use App\Services\PrediksiAntreanService;
use App\Services\RekomendasiAntreanService;
use Carbon\Carbon;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** DDDM Antrean: prediksi beban dan waktu tunggu dari riwayat 4 minggu, serta perintah demo:segarkan. */
class PrediksiAntreanTest extends TestCase
{
    use RefreshDatabase;

    private Poli $poli;

    private Pasien $pasien;

    protected function setUp(): void
    {
        parent::setUp();
        // Rabu, 25 November 2026 pukul 12.00 (hari pembanding: 3 Rabu sebelumnya)
        $this->travelTo(Carbon::create(2026, 11, 25, 12, 0));
        $this->poli = Poli::create(['nama_poli' => 'Poli Uji', 'batas_antrean' => 30, 'aktif' => true]);
        $this->pasien = Pasien::create(['kode_pasien' => 'P-9001', 'nama' => 'Pasien Uji', 'nik' => '3573009999999991',
            'jenis_kelamin' => 'L', 'tanggal_lahir' => '1990-01-01', 'alamat' => 'Malang', 'no_hp' => '0812-0000-0001']);
    }

    /** Antrean dengan waktu daftar tertentu; jika $lama diisi, antrean itu selesai dilayani selama $lama menit. */
    private function antrean(Carbon $daftar, string $status = 'selesai', ?int $lama = null): Antrean
    {
        $a = new Antrean(['nomor_antrean' => 'A001', 'pasien_id' => $this->pasien->id, 'poli_id' => $this->poli->id,
            'tanggal_kunjungan' => $daftar->copy()->startOfDay(), 'status' => $status]);
        $a->created_at = $a->updated_at = $daftar;
        if ($lama !== null) {
            $a->waktu_mulai = $daftar->copy()->addMinutes(5);
            $a->waktu_selesai = $daftar->copy()->addMinutes(5 + $lama);
        }
        $a->save();

        return $a;
    }

    /** 3 Rabu sebelumnya, masing-masing 2 pasien pagi (selesai, layanan 10 menit) + 1 pasien sore. */
    private function riwayatTigaRabu(): void
    {
        foreach ([1, 2, 3] as $minggu) {
            $hari = today()->subWeeks($minggu);
            $this->antrean($hari->copy()->setTime(8, 0), 'selesai', 10);
            $this->antrean($hari->copy()->setTime(9, 0), 'selesai', 10);
            $this->antrean($hari->copy()->setTime(15, 0), 'selesai', 10);
        }
    }

    public function test_median(): void
    {
        $this->assertSame(6.0, PrediksiAntreanService::median(collect([4, 9, 6])));
        $this->assertSame(7.0, PrediksiAntreanService::median(collect([4, 6, 8, 20])));
        $this->assertSame(0.0, PrediksiAntreanService::median(collect()));
    }

    public function test_tanpa_riwayat_prediksi_tidak_dihitung(): void
    {
        $this->antrean(now()->subHour(), 'menunggu');
        $p = app(PrediksiAntreanService::class)->untukHariIni(collect([$this->poli]))[$this->poli->id];

        $this->assertNull($p['lama_layanan']);
        $this->assertNull($p['tunggu_baru']);
        $this->assertNull($p['biasa_sampai_jam']);
        $this->assertFalse($p['ramai']);
    }

    public function test_dua_hari_pembanding_belum_cukup(): void
    {
        foreach ([1, 2] as $minggu) {
            $this->antrean(today()->subWeeks($minggu)->setTime(8, 0), 'selesai', 10);
        }
        $p = app(PrediksiAntreanService::class)->untukHariIni(collect([$this->poli]))[$this->poli->id];

        $this->assertSame(2, $p['hari_pembanding']);
        $this->assertNull($p['biasa_sampai_jam']);
    }

    public function test_beban_dan_perkiraan_tunggu_dari_riwayat(): void
    {
        $this->riwayatTigaRabu();
        // Hari ini sampai jam 12.00: 1 sedang dilayani + 3 menunggu = 4 antrean (biasanya 2 sampai jam ini)
        $this->antrean(today()->setTime(8, 0), 'dilayani');
        $menunggu = collect([9, 10, 11])->map(fn ($j) => $this->antrean(today()->setTime($j, 0), 'menunggu'));

        $layanan = app(PrediksiAntreanService::class);
        $prediksi = $layanan->untukHariIni(collect([$this->poli]));
        $p = $prediksi[$this->poli->id];

        $this->assertSame(10, $p['lama_layanan']);           // median 9 pelayanan x 10 menit
        $this->assertSame(3, $p['hari_pembanding']);
        $this->assertSame(2.0, $p['biasa_sampai_jam']);      // pasien jam 15.00 tidak dihitung
        $this->assertSame(3.0, $p['biasa_sehari']);
        $this->assertSame(100, $p['selisih_persen']);        // (4 - 2) / 2
        $this->assertTrue($p['ramai']);
        $this->assertSame(40, $p['tunggu_baru']);            // 4 antrean di depan x 10 menit

        $perAntrean = $layanan->perkiraanPerAntrean(Antrean::whereDate('tanggal_kunjungan', today())->get(), $prediksi);
        $this->assertSame([10, 20, 30], $menunggu->map(fn ($a) => $perAntrean[$a->id])->all());
    }

    public function test_poli_lebih_ramai_dinaikkan_satu_tingkat(): void
    {
        $service = app(RekomendasiAntreanService::class);
        $dasar = ['prioritas' => 'Normal', 'judul' => 'Pelayanan berjalan', 'saran' => 'Lanjutkan pelayanan.'];
        $ramai = ['ramai' => true, 'selisih_persen' => 50, 'hari' => 'Rabu'];

        $naik = $service->perkuatDenganBeban($dasar + ['prediksi' => $ramai]);
        $this->assertSame('Sedang', $naik['prioritas']);
        $this->assertStringContainsString('50% di atas biasanya hari Rabu', $naik['saran']);

        $this->assertSame('Tinggi', $service->perkuatDenganBeban(['prioritas' => 'Sedang'] + $dasar + ['prediksi' => $ramai])['prioritas']);
        $this->assertSame('Normal', $service->perkuatDenganBeban($dasar + ['prediksi' => ['ramai' => false] + $ramai])['prioritas']);
    }

    public function test_halaman_menampilkan_prediksi(): void
    {
        $this->seed(DatabaseSeeder::class);
        $petugas = User::where('username', 'antrean')->first();

        $this->actingAs($petugas)->get('/rekomendasi-antrean')->assertOk()
            ->assertSee('Beban dibanding biasa')->assertSee('Perkiraan tunggu pasien baru')->assertSee('Cara menghitung prediksi');
        $this->actingAs($petugas)->get('/antrean')->assertOk()
            ->assertSee('Perkiraan tunggu')->assertSee('menit');
    }

    public function test_seeder_menandai_antrean_sebagai_data_simulasi(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertSame(0, Antrean::whereNull('sumber')->count());
        $this->assertTrue(Antrean::whereDate('tanggal_kunjungan', today()->subDays(28))->exists()
            || today()->subDays(28)->isSunday());
        $this->assertFalse(Antrean::whereDate('tanggal_kunjungan', '<', today())->get()
            ->contains(fn ($a) => $a->tanggal_kunjungan->isSunday()));
    }

    public function test_demo_segarkan_tidak_menyentuh_data_petugas(): void
    {
        $this->seed(DatabaseSeeder::class);
        $buatanPetugas = $this->antrean(now()->subMinutes(10), 'menunggu');   // sumber = null
        $jumlahPemakaian = TransaksiStok::where('sumber', 'demo')->count();

        // Seminggu kemudian data demo sudah "tua"
        $this->travelTo(now()->addWeek());
        $this->assertFalse(Antrean::where('sumber', 'demo')->whereDate('tanggal_kunjungan', today())->exists());

        $this->artisan('demo:segarkan', ['--force' => true])->assertSuccessful();

        $this->assertTrue(Antrean::where('sumber', 'demo')->whereDate('tanggal_kunjungan', today())->exists());
        $this->assertNotNull($buatanPetugas->fresh());
        $this->assertSame($jumlahPemakaian, TransaksiStok::where('sumber', 'demo')->count());
        $this->assertSame(today()->subDays(5)->toDateString(),
            BatchObat::where('nomor_batch', 'B-ANT-2409')->first()->tanggal_kedaluwarsa->toDateString());
    }

    public function test_demo_segarkan_menolak_jika_belum_ada_data_demo(): void
    {
        $this->artisan('demo:segarkan', ['--force' => true])->assertFailed();
    }
}

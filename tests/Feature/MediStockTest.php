<?php

namespace Tests\Feature;

use App\Models\BatchObat;
use App\Models\Obat;
use App\Models\Pasien;
use App\Models\Pembelian;
use App\Models\Penjualan;
use App\Models\Supplier;
use App\Models\User;
use App\Services\RekomendasiPengadaanService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Pengujian awal (alpha test) subsistem MediStock RS, dipetakan ke SRS.
 * Jalankan: php artisan test
 */
class MediStockTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $apoteker;
    private User $apotek;
    private User $gudang;
    private User $manajemen;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        foreach (['admin', 'apoteker', 'apotek', 'gudang', 'manajemen'] as $u) {
            $this->$u = User::where('username', $u)->first();
        }
    }

    private function obat(string $kode): Obat
    {
        return Obat::where('kode_obat', $kode)->firstOrFail();
    }

    public function test_halaman_wajib_login(): void
    {
        $this->get('/obat')->assertRedirect('/login');
    }

    public function test_login_dengan_username(): void
    {
        $this->post('/login', ['username' => 'apoteker', 'password' => 'apoteker123'])->assertRedirect('/');
        $this->assertAuthenticatedAs($this->apoteker);
    }

    public function test_semua_halaman_obat_bisa_dibuka_admin(): void
    {
        foreach (['/', '/obat', '/stok-batch', '/supplier', '/pembelian', '/penjualan', '/retur-obat',
            '/analisis-obat', '/rekomendasi-obat', '/laporan-obat', '/laporan-obat?jenis=penjualan',
            '/laporan-obat?jenis=pengadaan', '/laporan-obat?jenis=retur'] as $url) {
            $this->actingAs($this->admin)->get($url)->assertOk();
        }
    }

    // ---------- FR-14 / UC-11: rekomendasi (DDDM)

    public function test_rekomendasi_sesuai_rumus_srs(): void
    {
        $hasil = app(RekomendasiPengadaanService::class)->analisis(90)->keyBy(fn ($r) => $r['obat']->kode_obat);

        $paracetamol = $hasil['OBT-DEMO-01'];
        $this->assertSame('pesan', $paracetamol['status']);   // stok 80 <= ROP 100
        $this->assertSame(100, $paracetamol['titik_pesan']);
        $this->assertSame(158, $paracetamol['saran']);

        // Antasida: stok kedaluwarsa (10) tidak dihitung, S = 60
        $this->assertSame(60, $hasil['OBT-DEMO-03']['stok']);
        $this->assertSame('pantau', $hasil['OBT-DEMO-03']['status']);
        $this->assertSame('aman', $hasil['OBT-DEMO-04']['status']);
    }

    public function test_uc11_data_pemakaian_kurang_dari_sebulan(): void
    {
        $vitamin = app(RekomendasiPengadaanService::class)->analisis(90)->firstWhere('obat.kode_obat', 'OBT-DEMO-05');
        $this->assertSame('kurang', $vitamin['status']);
        $this->assertSame(0, $vitamin['saran']);
        $this->actingAs($this->apoteker)->get('/rekomendasi-obat')->assertSee('Saran belum dapat dihitung');
    }

    // ---------- FR-01 / NFR-04 / NFR-09

    public function test_fr01_edit_nonaktif_obat_dan_nama_tidak_boleh_kembar(): void
    {
        $data = ['kode_obat' => 'OBT-BARU', 'nama_obat' => 'paracetamol 500 MG', 'satuan' => 'strip', 'stok_minimum' => 5,
            'harga_jual' => 1000, 'lead_time_hari' => 7, 'stok_pengaman_hari' => 3, 'interval_pesan_hari' => 14];
        $this->actingAs($this->apoteker)->post('/obat', $data)->assertSessionHasErrors('nama_obat');

        $obat = $this->obat('OBT-DEMO-01');
        $this->actingAs($this->apoteker)->put("/obat/{$obat->id}", ['nama_obat' => 'Paracetamol 500 mg', 'kode_obat' => 'OBT-DEMO-01',
            'harga_jual' => 5500] + $data)->assertRedirect('/obat');
        $this->assertEquals(5500, $obat->fresh()->harga_jual);
        $this->assertDatabaseHas('riwayat_harga_obat', ['obat_id' => $obat->id, 'harga_lama' => 5000, 'harga_baru' => 5500,
            'user_id' => $this->apoteker->id]);

        $this->actingAs($this->apoteker)->patch("/obat/{$obat->id}/status")->assertRedirect('/obat');
        $this->assertFalse($obat->fresh()->aktif);
        $this->actingAs($this->apotek)->post('/penjualan', ['nama_pembeli' => 'X', 'jenis' => 'non_resep',
            'obat_id' => [$obat->id], 'jumlah' => [1]])->assertSessionHasErrors('item');
    }

    public function test_nfr04_petugas_apotek_tidak_bisa_ubah_harga(): void
    {
        $obat = $this->obat('OBT-DEMO-01');
        $this->actingAs($this->apotek)->put("/obat/{$obat->id}", ['harga_jual' => 1])->assertForbidden();
        $this->actingAs($this->apotek)->post('/obat', [])->assertForbidden();
        $this->actingAs($this->apotek)->get('/obat')->assertOk()->assertDontSee('Tambah Data Obat');
    }

    // ---------- FR-03, FR-04, FR-05 / UC-04, UC-06

    public function test_pesanan_dua_tahap_dan_diterima_sebagian(): void
    {
        $obat = $this->obat('OBT-DEMO-02');
        $stokAwal = $obat->stok;

        $this->actingAs($this->apoteker)->post('/pembelian', [
            'supplier_id' => Supplier::first()->id, 'obat_id' => $obat->id, 'jumlah' => 100,
            'harga_satuan' => 9000, 'tanggal_pembelian' => now()->format('Y-m-d'),
        ])->assertRedirect('/pembelian');
        $pesanan = Pembelian::latest('id')->first();
        $this->assertSame('dipesan', $pesanan->status);
        $this->assertSame($stokAwal, $obat->fresh()->stok); // belum bertambah

        // batch & ED wajib (US-02)
        $this->actingAs($this->gudang)->post("/pembelian/{$pesanan->id}/terima", ['jumlah' => 10,
            'tanggal_terima' => today()->toDateString()])->assertSessionHasErrors(['nomor_batch', 'tanggal_kedaluwarsa']);

        $terima = fn ($jumlah, $batch) => $this->actingAs($this->gudang)->post("/pembelian/{$pesanan->id}/terima", [
            'jumlah' => $jumlah, 'nomor_batch' => $batch, 'tanggal_kedaluwarsa' => today()->addYear()->toDateString(),
            'tanggal_terima' => today()->toDateString(),
        ]);

        $terima(60, 'b-uji-1')->assertRedirect('/pembelian');
        $this->assertSame('sebagian', $pesanan->fresh()->status);
        $this->assertSame($stokAwal + 60, $obat->fresh()->stok);
        $this->assertDatabaseHas('batch_obat', ['obat_id' => $obat->id, 'nomor_batch' => 'B-UJI-1', 'sisa' => 60]);

        $terima(50, 'B-UJI-2')->assertSessionHasErrors('jumlah'); // melebihi sisa 40
        $terima(40, 'B-UJI-2')->assertRedirect('/pembelian');
        $this->assertSame('diterima', $pesanan->fresh()->status);
        $this->assertSame($stokAwal + 100, $obat->fresh()->stok);
        $this->assertSame($obat->fresh()->stok, (int) BatchObat::where('obat_id', $obat->id)->sum('sisa'));
    }

    public function test_peran_pengadaan_dibatasi(): void
    {
        $pesanan = Pembelian::where('status', 'dipesan')->first();
        $this->actingAs($this->gudang)->post('/pembelian', [])->assertForbidden();          // gudang tidak memesan
        $this->actingAs($this->apotek)->post("/pembelian/{$pesanan->id}/terima", [])->assertForbidden();
        $this->actingAs($this->apotek)->get('/pembelian')->assertForbidden();
    }

    // ---------- FR-08, FR-09, FR-10 / UC-07

    public function test_penjualan_fefo_dan_struk(): void
    {
        $obat = $this->obat('OBT-DEMO-01'); // batch ED +45 hari (30) dan ED +400 hari (50)
        $pasien = Pasien::first();

        $respon = $this->actingAs($this->apotek)->post('/penjualan', [
            'pasien_id' => $pasien->id, 'jenis' => 'resep', 'nomor_resep' => 'RSP-001',
            'obat_id' => [$obat->id, $obat->id], 'jumlah' => [30, 5],
        ]);
        $respon->assertRedirect();

        $this->assertSame(45, $obat->fresh()->stok);
        $this->assertSame(0, BatchObat::where('nomor_batch', 'B-PCT-2501')->value('sisa'));  // ED terdekat habis dulu
        $this->assertSame(45, BatchObat::where('nomor_batch', 'B-PCT-2602')->value('sisa'));
        $jual = Penjualan::latest('id')->first();
        $this->assertEquals(175000, $jual->total);
        $this->assertSame(2, $jual->detail()->count()); // dua batch
        $this->actingAs($this->apotek)->get("/penjualan/{$jual->id}/struk")->assertSee('B-PCT-2501')->assertSee('Rp 175.000');
    }

    public function test_batch_kedaluwarsa_tidak_bisa_dijual(): void
    {
        $obat = $this->obat('OBT-DEMO-03'); // stok 70, 10 di antaranya kedaluwarsa
        $this->actingAs($this->apotek)->post('/penjualan', ['nama_pembeli' => 'Budi', 'jenis' => 'non_resep',
            'obat_id' => [$obat->id], 'jumlah' => [65]])->assertSessionHasErrors('item');
        $this->assertSame(70, $obat->fresh()->stok);
    }

    public function test_penjualan_ditolak_jika_stok_kurang(): void
    {
        $obat = $this->obat('OBT-DEMO-04');
        $jumlahJual = Penjualan::count();

        $this->actingAs($this->apotek)->post('/penjualan', [
            'nama_pembeli' => 'Budi', 'jenis' => 'non_resep', 'obat_id' => [$obat->id], 'jumlah' => [999],
        ])->assertSessionHasErrors('item');

        $this->assertSame(30, $obat->fresh()->stok);
        $this->assertSame($jumlahJual, Penjualan::count());
    }

    // ---------- FR-07, FR-11, FR-12

    public function test_peringatan_kedaluwarsa_urut_dari_terdekat(): void
    {
        $this->actingAs($this->gudang)->get('/stok-batch')->assertOk()
            ->assertSeeInOrder(['B-ANT-2409', 'B-PCT-2501', 'B-AMX-2601'])
            ->assertSee('Kedaluwarsa');
    }

    public function test_retur_mengurangi_stok_dan_mencatat_alasan(): void
    {
        $batch = BatchObat::where('nomor_batch', 'B-ANT-2409')->first();
        $this->actingAs($this->apoteker)->post('/retur-obat', ['batch_id' => $batch->id, 'jumlah' => 10,
            'alasan' => 'kedaluwarsa', 'tujuan' => 'pemusnahan'])->assertRedirect('/retur-obat');

        $this->assertSame(0, $batch->fresh()->sisa);
        $this->assertSame(60, $this->obat('OBT-DEMO-03')->stok);
        $this->assertDatabaseHas('retur_obat', ['batch_id' => $batch->id, 'alasan' => 'kedaluwarsa', 'user_id' => $this->apoteker->id]);
        // retur tidak dihitung sebagai pemakaian
        $this->assertDatabaseHas('transaksi_stok_obat', ['batch_id' => $batch->id, 'sumber' => 'retur']);
    }

    public function test_fr12_riwayat_pembelian_per_pasien(): void
    {
        $pasien = Pasien::orderBy('id')->first();
        $this->actingAs($this->apotek)->get('/penjualan?pasien_id=' . $pasien->id)
            ->assertSee('RSP-DEMO-1')->assertSee('Vitamin C 500 mg');
    }

    // ---------- FR-13, FR-15

    public function test_fr13_grafik_pemakaian(): void
    {
        $this->actingAs($this->manajemen)->get('/analisis-obat?bulan=6')->assertOk()
            ->assertSee('<svg', false)->assertSee('Paracetamol 500 mg');
    }

    public function test_fr15_laporan_bisa_diunduh_excel(): void
    {
        $respon = $this->actingAs($this->manajemen)->get('/laporan-obat/ekspor?jenis=stok');
        $respon->assertOk();
        $this->assertStringContainsString('spreadsheetml', $respon->headers->get('Content-Type'));
        $this->assertStringStartsWith('PK', $respon->getContent()); // file xlsx = zip
    }

    // ---------- US-11: menu sesuai peran

    public function test_us11_menu_sesuai_peran(): void
    {
        $this->actingAs($this->gudang)->get('/')->assertOk()
            ->assertSee('Pesanan &amp; Penerimaan', false)->assertDontSee('Penjualan Obat')->assertDontSee('Data Pasien');
        $this->actingAs($this->gudang)->get('/penjualan')->assertForbidden();
        $this->actingAs($this->apotek)->get('/rekomendasi-obat')->assertForbidden();
        $this->actingAs($this->apoteker)->get('/antrean')->assertForbidden();
    }
}

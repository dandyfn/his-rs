<?php

namespace Tests\Feature;

use App\Models\Obat;
use App\Models\Pasien;
use App\Models\Supplier;
use App\Models\User;
use App\Services\RekomendasiPengadaanService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Pengujian awal (alpha test) subsistem MediStock RS.
 * Jalankan: php artisan test
 */
class MediStockTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->admin = User::where('username', 'admin')->first();
    }

    public function test_halaman_wajib_login(): void
    {
        $this->get('/obat')->assertRedirect('/login');
    }

    public function test_login_dengan_username(): void
    {
        $this->post('/login', ['username' => 'admin', 'password' => 'admin123'])
            ->assertRedirect('/');
        $this->assertAuthenticatedAs($this->admin);
    }

    public function test_semua_halaman_obat_bisa_dibuka(): void
    {
        foreach (['/', '/obat', '/supplier', '/pembelian', '/penjualan', '/rekomendasi-obat'] as $url) {
            $this->actingAs($this->admin)->get($url)->assertOk();
        }
    }

    public function test_rekomendasi_sesuai_rumus_srs(): void
    {
        $hasil = app(RekomendasiPengadaanService::class)->analisis(90)->keyBy(fn ($r) => $r['obat']->kode_obat);

        $paracetamol = $hasil['OBT-DEMO-01'];
        $this->assertSame('pesan', $paracetamol['status']);   // stok 80 <= ROP 100
        $this->assertSame(100, $paracetamol['titik_pesan']);
        $this->assertSame(158, $paracetamol['saran']);

        $this->assertSame('pantau', $hasil['OBT-DEMO-03']['status']);
        $this->assertSame('aman', $hasil['OBT-DEMO-04']['status']);
    }

    public function test_penjualan_mengurangi_stok_dan_tercatat_sebagai_pemakaian(): void
    {
        $obat = Obat::where('kode_obat', 'OBT-DEMO-01')->first();
        $pasien = Pasien::first();

        $respon = $this->actingAs($this->admin)->post('/penjualan', [
            'pasien_id' => $pasien->id,
            'jenis' => 'resep',
            'nomor_resep' => 'RSP-001',
            'obat_id' => [$obat->id, $obat->id],
            'jumlah' => [2, 1],
        ]);

        $respon->assertRedirect();
        $this->assertSame(77, $obat->fresh()->stok);
        $this->assertDatabaseHas('penjualan_obat', ['nama_pembeli' => $pasien->nama, 'total' => 15000]);
        $this->assertDatabaseHas('transaksi_stok_obat', ['obat_id' => $obat->id, 'jenis' => 'keluar', 'jumlah' => 3, 'sumber' => 'penjualan']);
    }

    public function test_penjualan_ditolak_jika_stok_kurang(): void
    {
        $obat = Obat::where('kode_obat', 'OBT-DEMO-04')->first();

        $this->actingAs($this->admin)->post('/penjualan', [
            'nama_pembeli' => 'Budi',
            'jenis' => 'non_resep',
            'obat_id' => [$obat->id],
            'jumlah' => [999],
        ])->assertSessionHasErrors('item');

        $this->assertSame(15, $obat->fresh()->stok);
        $this->assertDatabaseCount('penjualan_obat', 0);
    }

    public function test_pembelian_menambah_stok(): void
    {
        $obat = Obat::where('kode_obat', 'OBT-DEMO-02')->first();

        $this->actingAs($this->admin)->post('/pembelian', [
            'supplier_id' => Supplier::first()->id,
            'obat_id' => $obat->id,
            'jumlah' => 100,
            'harga_satuan' => 9000,
            'tanggal_pembelian' => now()->format('Y-m-d'),
        ])->assertRedirect('/pembelian');

        $this->assertSame(145, $obat->fresh()->stok);
    }
}

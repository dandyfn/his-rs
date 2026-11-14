<?php

namespace Tests\Feature;

use App\Models\Antrean;
use App\Models\IdentitasPasien;
use App\Models\Pasien;
use App\Models\Poli;
use App\Models\User;
use App\Services\RekomendasiAntreanService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Pengujian awal (alpha test) subsistem Pendaftaran & Identitas, Antrean, dan Pengguna.
 */
class PendaftaranAntreanTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $petugas;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->admin = User::where('username', 'admin')->first();
        $this->petugas = User::where('username', 'petugas')->first();
    }

    public function test_semua_halaman_bisa_dibuka(): void
    {
        $halaman = ['/', '/pasien', '/identitas-pasien', '/antrean', '/poli', '/riwayat-antrean',
            '/rekomendasi-antrean', '/laporan-antrean', '/pengguna'];
        foreach ($halaman as $url) {
            $this->actingAs($this->admin)->get($url)->assertOk();
        }
    }

    public function test_tambah_dan_edit_pasien_dengan_kode_unik(): void
    {
        $this->actingAs($this->petugas)->post('/pasien', ['kode_pasien' => 'p-0100', 'nama' => 'Tes Pasien'])
            ->assertRedirect('/pasien');
        $pasien = Pasien::where('kode_pasien', 'P-0100')->firstOrFail();

        $this->actingAs($this->petugas)->post('/pasien', ['kode_pasien' => 'P-0100', 'nama' => 'Ganda'])
            ->assertSessionHasErrors('kode_pasien');

        $this->actingAs($this->petugas)->put("/pasien/{$pasien->id}", ['kode_pasien' => 'P-0100', 'nama' => 'Nama Baru'])
            ->assertRedirect('/pasien');
        $this->assertSame('Nama Baru', $pasien->fresh()->nama);
    }

    public function test_identitas_sementara_dan_verifikasi_tercatat_di_riwayat(): void
    {
        $this->actingAs($this->petugas)->post('/identitas-pasien', ['nama_sementara' => 'Pasien Tanpa KTP'])
            ->assertRedirect('/identitas-pasien');
        $identitas = IdentitasPasien::where('nama_sementara', 'Pasien Tanpa KTP')->firstOrFail();
        $this->assertSame('sementara', $identitas->status_verifikasi);
        $this->assertStringContainsString('Lengkapi informasi', $identitas->rekomendasi());

        $this->actingAs($this->petugas)->post("/identitas-pasien/{$identitas->id}/tindakan", [
            'aksi' => 'verifikasi', 'catatan_verifikasi' => 'Dicocokkan dengan KTP',
        ]);
        $identitas->refresh();
        $this->assertSame('terverifikasi', $identitas->status_verifikasi);
        $this->assertSame($this->petugas->id, $identitas->diverifikasi_oleh);
        $this->assertSame(2, $identitas->riwayat()->count());
    }

    public function test_daftar_antrean_memberi_nomor_berurutan_dan_menghormati_batas(): void
    {
        $poli = Poli::create(['nama_poli' => 'Poli Uji', 'batas_antrean' => 2]);
        $pasien = Pasien::first();

        $this->actingAs($this->petugas)->post('/antrean', ['pasien_id' => $pasien->id, 'poli_id' => $poli->id]);
        $this->actingAs($this->petugas)->post('/antrean', ['pasien_id' => $pasien->id, 'poli_id' => $poli->id]);
        $this->assertSame(['A001', 'A002'], Antrean::where('poli_id', $poli->id)->orderBy('id')->pluck('nomor_antrean')->all());

        $this->actingAs($this->petugas)->post('/antrean', ['pasien_id' => $pasien->id, 'poli_id' => $poli->id])
            ->assertSessionHasErrors('poli_id');
        $this->assertSame(2, Antrean::where('poli_id', $poli->id)->count());
    }

    public function test_alur_status_antrean_dan_riwayat(): void
    {
        $poli = Poli::create(['nama_poli' => 'Poli Alur', 'batas_antrean' => 10]);
        $this->actingAs($this->petugas)->post('/antrean', ['pasien_id' => Pasien::first()->id, 'poli_id' => $poli->id]);
        $antrean = Antrean::where('poli_id', $poli->id)->firstOrFail();

        // Tidak boleh langsung "selesai" dari "menunggu"
        $this->actingAs($this->petugas)->post("/antrean/{$antrean->id}/status", ['aksi' => 'selesai'])
            ->assertSessionHasErrors('aksi');

        foreach (['panggil', 'mulai', 'selesai'] as $aksi) {
            $this->actingAs($this->petugas)->post("/antrean/{$antrean->id}/status", ['aksi' => $aksi])
                ->assertSessionHasNoErrors();
        }

        $antrean->refresh();
        $this->assertSame('selesai', $antrean->status);
        $this->assertNotNull($antrean->waktu_mulai);
        $this->assertNotNull($antrean->waktu_selesai);
        $this->assertSame(3, $antrean->riwayat()->count());
    }

    public function test_rekomendasi_antrean_sesuai_aturan(): void
    {
        $service = app(RekomendasiAntreanService::class);

        $this->assertSame('Tinggi', $service->saran(['total' => 20, 'menunggu' => 1, 'dipanggil' => 0], 20)['prioritas']);
        $this->assertSame('Tinggi', $service->saran(['total' => 8, 'menunggu' => 5, 'dipanggil' => 0], 30)['prioritas']);
        $this->assertSame('Sedang', $service->saran(['total' => 5, 'menunggu' => 3, 'dipanggil' => 0], 30)['prioritas']);
        $this->assertSame('Normal', $service->saran(['total' => 0, 'menunggu' => 0, 'dipanggil' => 0], 30)['prioritas']);

        // Data demo: Poli Umum punya 5 antrean menunggu
        $umum = $service->analisisHariIni()->first(fn ($r) => $r['poli']->nama_poli === 'Poli Umum');
        $this->assertSame('Tinggi', $umum['prioritas']);
        $this->assertNotNull($umum['rata_tunggu']);
    }

    public function test_laporan_bisa_diekspor_ke_csv(): void
    {
        $respon = $this->actingAs($this->petugas)->get('/laporan-antrean/ekspor?tanggal_awal='
            . today()->subDays(7)->format('Y-m-d') . '&tanggal_akhir=' . today()->format('Y-m-d'));

        $respon->assertOk();
        $this->assertStringContainsString('No. Antrean', $respon->streamedContent());
    }

    public function test_hanya_admin_yang_bisa_mengelola_pengguna_dan_poli(): void
    {
        $this->actingAs($this->petugas)->get('/pengguna')->assertForbidden();
        $this->actingAs($this->petugas)->post('/poli', ['nama_poli' => 'Poli X', 'batas_antrean' => 5])->assertForbidden();

        $this->actingAs($this->admin)->post('/pengguna', [
            'name' => 'Kasir Baru', 'username' => 'kasir01', 'role' => 'petugas',
            'password' => 'rahasia123', 'password_confirmation' => 'rahasia123',
        ])->assertRedirect('/pengguna');
        $this->assertDatabaseHas('users', ['username' => 'kasir01', 'role' => 'petugas']);
    }

    public function test_admin_tidak_bisa_menonaktifkan_akunnya_sendiri_dan_akun_nonaktif_tidak_bisa_login(): void
    {
        $this->actingAs($this->admin)->patch("/pengguna/{$this->admin->id}/status")->assertSessionHasErrors('akun');

        $this->actingAs($this->admin)->patch("/pengguna/{$this->petugas->id}/status");
        $this->assertFalse($this->petugas->fresh()->aktif);

        auth()->logout();
        $this->post('/login', ['username' => 'petugas', 'password' => 'petugas123'])->assertSessionHasErrors('username');
        $this->assertGuest();
    }
}

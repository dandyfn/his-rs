<?php

namespace Database\Seeders;

use App\Models\Antrean;
use App\Models\IdentitasPasien;
use App\Models\Pasien;
use App\Models\Poli;
use App\Models\RiwayatIdentitas;
use Illuminate\Database\Seeder;

/**
 * Data contoh subsistem Pendaftaran & Identitas serta Antrean (SIRATA).
 * Aman dijalankan berulang.
 */
class PendaftaranAntreanDemoSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([['Poli Umum', 30], ['Poli Gigi', 20], ['Poli Anak', 20]] as [$nama, $batas]) {
            Poli::firstOrCreate(['nama_poli' => $nama], ['batas_antrean' => $batas, 'aktif' => true]);
        }

        // Data fiktif untuk demo (NIK bukan NIK asli).
        $namaPasien = [
            ['Siti Aminah', 'P', '1978-02-14', 'Jl. Melati No. 3, Malang'],
            ['Budi Santoso', 'L', '1965-07-21', 'Jl. Kenanga No. 10, Malang'],
            ['Rina Wulandari', 'P', '1992-11-05', 'Jl. Anggrek No. 7, Batu'],
            ['Agus Pratama', 'L', '1988-03-30', 'Jl. Mawar No. 1, Malang'],
            ['Dewi Lestari', 'P', '2001-09-17', 'Jl. Dahlia No. 22, Malang'],
            ['Joko Susilo', 'L', '1959-12-02', 'Jl. Cempaka No. 5, Kepanjen'],
            ['Maya Sari', 'P', '2015-06-08', 'Jl. Teratai No. 9, Malang'],
            ['Hendra Wijaya', 'L', '1980-01-25', 'Jl. Flamboyan No. 14, Batu'],
            ['Nur Aini', 'P', '1996-04-19', 'Jl. Kamboja No. 2, Malang'],
            ['Fajar Nugroho', 'L', '2018-10-11', 'Jl. Bougenvil No. 6, Malang'],
        ];
        foreach ($namaPasien as $i => [$nama, $jk, $lahir, $alamat]) {
            Pasien::firstOrCreate(['kode_pasien' => sprintf('P-%04d', $i + 1)], [
                'nama' => $nama, 'nik' => sprintf('357300%010d', $i + 1), 'jenis_kelamin' => $jk,
                'tanggal_lahir' => $lahir, 'alamat' => $alamat, 'no_hp' => sprintf('0812-3456-%04d', $i + 1),
            ]);
        }

        if (IdentitasPasien::doesntExist()) {
            $contoh = [
                ['Tn. X (tanpa identitas)', null, null, null, 'sementara'],
                ['Lestari', '1990-04-12', null, '0812-0000-1111', 'perlu_konfirmasi'],
                ['Ahmad Fauzi', '1985-09-30', '3573010000000001', '0813-0000-2222', 'terverifikasi'],
            ];
            foreach ($contoh as $n => [$nama, $lahir, $nik, $kontak, $status]) {
                $identitas = IdentitasPasien::create([
                    'kode_sementara' => 'TMP-DEMO-' . ($n + 1),
                    'nama_sementara' => $nama, 'tanggal_lahir' => $lahir,
                    'nomor_identitas' => $nik, 'kontak' => $kontak, 'status_verifikasi' => $status,
                ]);
                RiwayatIdentitas::create(['identitas_id' => $identitas->id, 'aksi' => 'pendaftaran_sementara',
                    'detail' => 'Identitas sementara dibuat dengan kode ' . $identitas->kode_sementara]);
            }
        }

        if (Antrean::doesntExist()) {
            $pasien = Pasien::orderBy('id')->pluck('id')->values();
            $poli = Poli::orderBy('id')->pluck('id', 'nama_poli');

            // 7 hari terakhir: antrean selesai dengan waktu tunggu bervariasi (untuk laporan)
            for ($hari = 7; $hari >= 1; $hari--) {
                $tanggal = today()->subDays($hari);
                foreach ($poli as $poliId) {
                    for ($k = 1; $k <= 4; $k++) {
                        $daftar = $tanggal->copy()->setTime(8, 0)->addMinutes($k * 12);
                        $Antrean = Antrean::create([
                            'nomor_antrean' => 'A' . str_pad((string) $k, 3, '0', STR_PAD_LEFT),
                            'pasien_id' => $pasien[($hari + $k + $poliId) % $pasien->count()],
                            'poli_id' => $poliId, 'tanggal_kunjungan' => $tanggal,
                            'status' => $k === 4 && $hari % 3 === 0 ? 'batal' : 'selesai',
                        ]);
                        $Antrean->created_at = $daftar;
                        if ($Antrean->status === 'selesai') {
                            $Antrean->waktu_mulai = $daftar->copy()->addMinutes(10 + ($k * 7 + $hari * 3) % 35);
                            $Antrean->waktu_selesai = $Antrean->waktu_mulai->copy()->addMinutes(12);
                        }
                        $Antrean->save();
                    }
                }
            }

            // Hari ini: Poli Umum ramai (memicu rekomendasi prioritas Tinggi)
            $statusHariIni = [
                'Poli Umum' => ['selesai', 'dilayani', 'dipanggil', 'menunggu', 'menunggu', 'menunggu', 'menunggu', 'menunggu'],
                'Poli Gigi' => ['selesai', 'dilayani', 'menunggu'],
                'Poli Anak' => ['dipanggil', 'menunggu', 'menunggu', 'menunggu'],
            ];
            $i = 0;
            foreach ($statusHariIni as $namaPoli => $daftarStatus) {
                foreach ($daftarStatus as $n => $status) {
                    $daftar = now()->subMinutes(90 - $n * 9);
                    $a = Antrean::create([
                        'nomor_antrean' => 'A' . str_pad((string) ($n + 1), 3, '0', STR_PAD_LEFT),
                        'pasien_id' => $pasien[$i++ % $pasien->count()],
                        'poli_id' => $poli[$namaPoli], 'tanggal_kunjungan' => today(), 'status' => $status,
                    ]);
                    $a->created_at = $daftar;
                    if (in_array($status, ['dilayani', 'selesai'], true)) {
                        $a->waktu_mulai = $daftar->copy()->addMinutes(18 + $n * 4);
                    }
                    if ($status === 'selesai') {
                        $a->waktu_selesai = $a->waktu_mulai->copy()->addMinutes(10);
                    }
                    $a->save();
                }
            }
        }
    }
}

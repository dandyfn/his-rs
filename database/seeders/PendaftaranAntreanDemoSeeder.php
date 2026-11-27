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
            self::buatAntreanDemo();
        }
    }

    /** Penanda data simulasi di kolom antrean.sumber (lihat perintah demo:segarkan). */
    public const SUMBER = 'demo';

    /**
     * Pola jumlah pasien per hari (Senin..Sabtu) untuk RIWAYAT SIMULASI 4 minggu.
     * Minggu = poli tutup. Senin sengaja paling ramai supaya fitur "beban dibanding biasa" terlihat.
     */
    public const POLA_HARIAN = [
        'Poli Umum' => [1 => 14, 12, 11, 11, 10, 7],
        'Poli Gigi' => [1 => 5, 4, 4, 4, 4, 3],
        'Poli Anak' => [1 => 8, 6, 5, 5, 5, 4],
    ];

    /** Lama pelayanan dasar per poli (menit) untuk data simulasi. */
    public const LAMA_LAYANAN = ['Poli Umum' => 10, 'Poli Gigi' => 20, 'Poli Anak' => 12];

    /**
     * Membuat antrean demo: riwayat 4 minggu (data simulasi berpola) + antrean hari ini.
     * Semua baris ditandai sumber = 'demo'. Tanggalnya relatif terhadap hari ini,
     * jadi perintah demo:segarkan cukup menghapus baris demo lalu memanggil fungsi ini lagi.
     */
    public static function buatAntreanDemo(): void
    {
        $pasien = Pasien::orderBy('id')->pluck('id')->values();
        $poli = Poli::whereIn('nama_poli', array_keys(self::POLA_HARIAN))->pluck('id', 'nama_poli');
        if ($pasien->isEmpty() || $poli->isEmpty()) {
            return;
        }

        // 1) Riwayat simulasi 28 hari ke belakang (Minggu dilewati).
        for ($hari = 28; $hari >= 1; $hari--) {
            $tanggal = today()->subDays($hari);
            if ($tanggal->isSunday()) {
                continue;
            }
            $urutPoli = 0;
            foreach (self::POLA_HARIAN as $namaPoli => $pola) {
                $urutPoli++;
                if (! isset($poli[$namaPoli])) {
                    continue;
                }
                // Variasi kecil -1..+1 supaya tiap minggu tidak persis sama.
                $jumlah = max(1, $pola[$tanggal->dayOfWeekIso] + (($hari * 7 + $urutPoli * 3) % 3) - 1);
                // Pasien datang antara 07.30 dan 10.00; dokter mulai 08.00 dan melayani satu per satu.
                $jarak = intdiv(150, $jumlah);
                $dokterSelesai = $tanggal->copy()->setTime(8, 0);
                for ($k = 1; $k <= $jumlah; $k++) {
                    $daftar = $tanggal->copy()->setTime(7, 30)->addMinutes(($k - 1) * $jarak);
                    $batal = $k % 9 === 0;
                    $a = new Antrean([
                        'nomor_antrean' => 'A' . str_pad((string) $k, 3, '0', STR_PAD_LEFT),
                        'pasien_id' => $pasien[($hari + $k + $urutPoli) % $pasien->count()],
                        'poli_id' => $poli[$namaPoli], 'tanggal_kunjungan' => $tanggal,
                        'status' => $batal ? 'batal' : 'selesai', 'sumber' => self::SUMBER,
                    ]);
                    $a->created_at = $a->updated_at = $daftar;
                    if (! $batal) {
                        $mulai = $daftar->greaterThan($dokterSelesai) ? $daftar->copy() : $dokterSelesai->copy();
                        $lama = self::LAMA_LAYANAN[$namaPoli] + ($k * 3 + $hari) % 5;
                        $a->waktu_mulai = $mulai;
                        $a->waktu_selesai = $dokterSelesai = $mulai->copy()->addMinutes($lama);
                    }
                    $a->save();
                }
            }
        }

        // 2) Hari ini:
        //    - Poli Umum: banyak antrean menunggu -> prioritas Tinggi dari aturan lama;
        //    - Poli Anak: sengaja ±50% di atas biasanya untuk hari ini, tetapi hanya 3 menunggu,
        //      sehingga prioritasnya naik dari Sedang ke Tinggi KARENA BEBAN (fitur prediksi);
        //    - Poli Gigi: normal.
        $anak = (int) ceil((self::POLA_HARIAN['Poli Anak'][today()->dayOfWeekIso] ?? 4) * 1.5);
        $statusHariIni = [
            'Poli Umum' => ['selesai', 'dilayani', 'dipanggil', 'menunggu', 'menunggu', 'menunggu', 'menunggu', 'menunggu'],
            'Poli Gigi' => ['selesai', 'dilayani', 'menunggu'],
            'Poli Anak' => array_merge(array_fill(0, max(0, $anak - 4), 'selesai'), ['dilayani', 'menunggu', 'menunggu', 'menunggu']),
        ];
        $i = 0;
        foreach ($statusHariIni as $namaPoli => $daftarStatus) {
            foreach ($daftarStatus as $n => $status) {
                $daftar = now()->subMinutes(5 + (count($daftarStatus) - $n) * 8);
                $a = new Antrean([
                    'nomor_antrean' => 'A' . str_pad((string) ($n + 1), 3, '0', STR_PAD_LEFT),
                    'pasien_id' => $pasien[$i++ % $pasien->count()],
                    'poli_id' => $poli[$namaPoli], 'tanggal_kunjungan' => today(), 'status' => $status,
                    'sumber' => self::SUMBER,
                ]);
                $a->created_at = $a->updated_at = $daftar;
                if (in_array($status, ['dilayani', 'selesai'], true)) {
                    $a->waktu_mulai = $daftar->copy()->addMinutes(10);
                }
                if ($status === 'selesai') {
                    $a->waktu_selesai = $a->waktu_mulai->copy()->addMinutes(self::LAMA_LAYANAN[$namaPoli]);
                }
                $a->save();
            }
        }
    }
}

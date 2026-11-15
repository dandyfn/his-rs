<?php

namespace App\Services;

use App\Models\Antrean;
use App\Models\Poli;
use Illuminate\Support\Collection;

/**
 * Fungsi Data-Driven Decision Making subsistem Antrean (SIRATA).
 * Aturan prioritas dan saran sama dengan versi PHP native, ditambah
 * rata-rata waktu tunggu hari ini (dari waktu daftar sampai mulai dilayani).
 */
class RekomendasiAntreanService
{
    /** @param  Collection|null  $antrean  antrean hari ini yang sudah dimuat (opsional, hemat query) */
    public function analisisHariIni(?Collection $antrean = null): Collection
    {
        $antreanHariIni = ($antrean ?? Antrean::whereDate('tanggal_kunjungan', today())->get())
            ->where('status', '<>', 'batal')
            ->groupBy('poli_id');

        return Poli::where('aktif', true)->orderBy('nama_poli')->get()
            ->map(function (Poli $poli) use ($antreanHariIni) {
                $data = $antreanHariIni->get($poli->id, collect());
                $hitung = fn ($status) => $data->where('status', $status)->count();

                $menitTunggu = $data->map->menitTunggu()->filter(fn ($m) => $m !== null);

                $r = [
                    'poli' => $poli,
                    'total' => $data->count(),
                    'menunggu' => $hitung('menunggu'),
                    'dipanggil' => $hitung('dipanggil'),
                    'dilayani' => $hitung('dilayani'),
                    'selesai' => $hitung('selesai'),
                    'rata_tunggu' => $menitTunggu->isEmpty() ? null : (int) round($menitTunggu->avg()),
                    'kapasitas' => $poli->batas_antrean > 0 ? (int) round($data->count() / $poli->batas_antrean * 100) : null,
                ];

                return $r + $this->saran($r, $poli->batas_antrean);
            })
            ->sortByDesc('menunggu')
            ->values();
    }

    /** Aturan prioritas (dipisah agar mudah diuji). */
    public function saran(array $r, int $batas): array
    {
        if ($r['total'] === 0) {
            return ['prioritas' => 'Normal', 'judul' => 'Belum ada antrean',
                'saran' => 'Belum ada antrean hari ini. Pantau pendaftaran baru.'];
        }
        if ($batas > 0 && $r['total'] >= $batas) {
            return ['prioritas' => 'Tinggi', 'judul' => 'Kapasitas antrean tercapai',
                'saran' => 'Jumlah antrean telah mencapai batas poli. Evaluasi kapasitas pelayanan.'];
        }
        if ($r['menunggu'] >= 5) {
            return ['prioritas' => 'Tinggi', 'judul' => 'Antrean menunggu perlu dipantau',
                'saran' => "Terdapat {$r['menunggu']} antrean menunggu. Prioritaskan pemanggilan sesuai urutan."];
        }
        if ($r['menunggu'] >= 3) {
            return ['prioritas' => 'Sedang', 'judul' => 'Antrean menunggu',
                'saran' => 'Pantau antrean menunggu dan percepat pemanggilan sesuai urutan.'];
        }
        if ($r['dipanggil'] > 0) {
            return ['prioritas' => 'Normal', 'judul' => 'Tindak lanjuti antrean yang dipanggil',
                'saran' => "Terdapat {$r['dipanggil']} antrean yang sudah dipanggil. Pantau agar pelayanan segera dimulai."];
        }

        return ['prioritas' => 'Normal', 'judul' => 'Pelayanan berjalan',
            'saran' => 'Lanjutkan pelayanan dan pantau perubahan antrean.'];
    }
}

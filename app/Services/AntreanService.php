<?php

namespace App\Services;

use App\Models\Antrean;
use App\Models\Poli;
use App\Models\RiwayatStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Subsistem Antrean Rawat Jalan (SIRATA): pendaftaran antrean dan alur status.
 * menunggu -> dipanggil -> dilayani -> selesai; batal dari status aktif mana pun.
 */
class AntreanService
{
    public const ALUR = [
        'panggil' => ['dari' => ['menunggu'], 'ke' => 'dipanggil'],
        'mulai' => ['dari' => ['dipanggil'], 'ke' => 'dilayani'],
        'selesai' => ['dari' => ['dilayani'], 'ke' => 'selesai'],
        'batal' => ['dari' => ['menunggu', 'dipanggil', 'dilayani'], 'ke' => 'batal'],
    ];

    public function daftar(int $pasienId, int $poliId): Antrean
    {
        return DB::transaction(function () use ($pasienId, $poliId) {
            $poli = Poli::where('aktif', true)->lockForUpdate()->find($poliId);
            if (! $poli) {
                throw ValidationException::withMessages(['poli_id' => 'Poli tidak ditemukan atau sedang tidak aktif.']);
            }

            $hariIni = Antrean::where('poli_id', $poli->id)->whereDate('tanggal_kunjungan', today());
            $aktif = (clone $hariIni)->where('status', '<>', 'batal')->count();

            if ($aktif >= $poli->batas_antrean) {
                throw ValidationException::withMessages(['poli_id' => 'Batas antrean poli hari ini sudah tercapai.']);
            }

            // Nomor dihitung dari semua antrean hari ini (termasuk batal) supaya tidak ada nomor ganda.
            $urutan = (clone $hariIni)->count() + 1;

            $antrean = Antrean::create([
                'nomor_antrean' => 'A' . str_pad((string) $urutan, 3, '0', STR_PAD_LEFT),
                'pasien_id' => $pasienId,
                'poli_id' => $poli->id,
                'tanggal_kunjungan' => today(),
                'status' => 'menunggu',
            ]);

            return $antrean;
        });
    }

    public function ubahStatus(int $antreanId, string $aksi, ?int $userId): Antrean
    {
        if (! isset(self::ALUR[$aksi])) {
            throw ValidationException::withMessages(['aksi' => 'Aksi tidak dikenal.']);
        }

        return DB::transaction(function () use ($antreanId, $aksi, $userId) {
            $antrean = Antrean::lockForUpdate()->findOrFail($antreanId);
            $statusLama = $antrean->status;
            $alur = self::ALUR[$aksi];

            if (! in_array($statusLama, $alur['dari'], true)) {
                throw ValidationException::withMessages([
                    'aksi' => "Perubahan status tidak diizinkan. Status saat ini: {$statusLama}.",
                ]);
            }

            $antrean->status = $alur['ke'];
            if ($aksi === 'mulai') {
                $antrean->waktu_mulai = now();
            } elseif ($aksi === 'selesai') {
                $antrean->waktu_selesai = now();
            }
            $antrean->save();

            RiwayatStatus::create([
                'antrean_id' => $antrean->id,
                'user_id' => $userId,
                'status_sebelumnya' => $statusLama,
                'status_baru' => $alur['ke'],
            ]);

            return $antrean;
        });
    }
}

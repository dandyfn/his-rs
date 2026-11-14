<?php

namespace App\Services;

use App\Models\IdentitasPasien;
use App\Models\RiwayatIdentitas;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Subsistem Pendaftaran & Identitas Pasien:
 * pendaftaran identitas sementara, verifikasi, dan riwayat perubahan.
 */
class IdentitasPasienService
{
    public function daftarSementara(array $data, ?int $userId): IdentitasPasien
    {
        return DB::transaction(function () use ($data, $userId) {
            $identitas = IdentitasPasien::create([
                'kode_sementara' => 'TMP-' . now()->format('ymd') . '-' . Str::upper(Str::random(6)),
                'nama_sementara' => $data['nama_sementara'],
                'tanggal_lahir' => $data['tanggal_lahir'] ?? null,
                'nomor_identitas' => $data['nomor_identitas'] ?? null,
                'kontak' => $data['kontak'] ?? null,
                'status_verifikasi' => 'sementara',
                'dibuat_oleh' => $userId,
            ]);

            $this->catatRiwayat($identitas->id, $userId, 'pendaftaran_sementara',
                'Identitas sementara dibuat dengan kode ' . $identitas->kode_sementara);

            return $identitas;
        });
    }

    /**
     * Identitas lain dengan nama mirip, sebagai petunjuk kemungkinan pendaftaran ganda.
     * Bukan bukti bahwa dua rekaman adalah orang yang sama.
     */
    public function cariKemiripan(IdentitasPasien $identitas): Collection
    {
        return IdentitasPasien::where('id', '<>', $identitas->id)
            ->whereRaw('LOWER(nama_sementara) LIKE ?', ['%' . Str::lower($identitas->nama_sementara) . '%'])
            ->latest('id')->limit(5)->get(['kode_sementara', 'nama_sementara']);
    }

    /** $aksi: verifikasi -> terverifikasi, konfirmasi -> perlu_konfirmasi */
    public function ubahStatus(IdentitasPasien $identitas, string $aksi, ?string $catatan, ?int $userId): void
    {
        DB::transaction(function () use ($identitas, $aksi, $catatan, $userId) {
            if ($aksi === 'verifikasi') {
                $identitas->update([
                    'status_verifikasi' => 'terverifikasi',
                    'catatan_verifikasi' => $catatan,
                    'diverifikasi_oleh' => $userId,
                ]);
                $keterangan = 'Status diubah menjadi terverifikasi.';
            } else {
                $identitas->update([
                    'status_verifikasi' => 'perlu_konfirmasi',
                    'catatan_verifikasi' => $catatan,
                ]);
                $keterangan = 'Identitas ditandai perlu konfirmasi.';
            }

            $this->catatRiwayat($identitas->id, $userId, $aksi,
                $keterangan . ' Catatan: ' . ($catatan !== null && $catatan !== '' ? $catatan : '-'));
        });
    }

    private function catatRiwayat(int $identitasId, ?int $userId, string $aksi, string $detail): void
    {
        RiwayatIdentitas::create([
            'identitas_id' => $identitasId,
            'user_id' => $userId,
            'aksi' => $aksi,
            'detail' => $detail,
        ]);
    }
}

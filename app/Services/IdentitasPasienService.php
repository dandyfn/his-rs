<?php

namespace App\Services;

use App\Models\IdentitasPasien;
use App\Models\Pasien;
use App\Models\RiwayatIdentitas;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Subsistem Pendaftaran & Identitas Pasien:
 * pendaftaran identitas sementara (FR-02), verifikasi (FR-05), pembaruan (FR-06),
 * penyelesaian menjadi pasien tetap (FR-07), dan riwayat perubahan (FR-08).
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
            if ($identitas->pasien_id) {
                throw ValidationException::withMessages(['aksi' => 'Identitas ini sudah menjadi pasien tetap.']);
            }
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

    private const LABEL = [
        'nama_sementara' => 'Nama', 'tanggal_lahir' => 'Tanggal lahir',
        'nomor_identitas' => 'Nomor identitas', 'kontak' => 'Kontak',
    ];

    /**
     * FR-06: melengkapi/memperbarui identitas. Setiap field yang berubah dicatat di riwayat.
     * Jika identitas sudah terverifikasi lalu datanya diubah, status kembali "perlu_konfirmasi"
     * agar data yang belum dicek ulang tidak dianggap pasti.
     */
    public function perbarui(IdentitasPasien $identitas, array $data, ?int $userId): bool
    {
        return DB::transaction(function () use ($identitas, $data, $userId) {
            $identitas = IdentitasPasien::lockForUpdate()->findOrFail($identitas->id);
            if ($identitas->pasien_id) {
                throw ValidationException::withMessages(['nama_sementara' =>
                    'Identitas ini sudah menjadi pasien tetap. Ubah datanya melalui menu Data Pasien.']);
            }

            $perubahan = [];
            foreach (self::LABEL as $field => $label) {
                $lama = $field === 'tanggal_lahir' ? $identitas->tanggal_lahir?->format('Y-m-d') : $identitas->$field;
                $baru = $data[$field] ?? null;
                if ((string) $lama !== (string) $baru) {
                    $perubahan[] = $label . ': ' . ($lama ?: '-') . ' → ' . ($baru ?: '-');
                }
            }
            if ($perubahan === []) {
                return false;
            }

            $update = array_intersect_key($data, self::LABEL);
            $catatanStatus = '';
            if ($identitas->status_verifikasi === 'terverifikasi') {
                $update['status_verifikasi'] = 'perlu_konfirmasi';
                $catatanStatus = ' Status kembali menjadi perlu konfirmasi karena data berubah setelah verifikasi.';
            }
            $identitas->update($update);

            $this->catatRiwayat($identitas->id, $userId, 'pembaruan',
                'Data diperbarui. ' . implode('; ', $perubahan) . '.' . $catatanStatus);

            return true;
        });
    }

    /**
     * FR-07: penelusuran dan penyelesaian identitas. Identitas yang sudah terverifikasi
     * dikaitkan ke pasien tetap: pasien yang sudah ada ($pasienId) atau pasien baru.
     * Data identitas sementara dan riwayatnya tetap disimpan.
     */
    public function jadikanPasienTetap(IdentitasPasien $identitas, ?int $pasienId, ?int $userId): Pasien
    {
        return DB::transaction(function () use ($identitas, $pasienId, $userId) {
            $identitas = IdentitasPasien::lockForUpdate()->findOrFail($identitas->id);
            if ($identitas->status_verifikasi !== 'terverifikasi') {
                throw ValidationException::withMessages(['pasien_id' =>
                    'Identitas harus diverifikasi terlebih dahulu sebelum dijadikan pasien tetap.']);
            }
            if ($identitas->pasien_id) {
                throw ValidationException::withMessages(['pasien_id' => 'Identitas ini sudah dikaitkan dengan pasien tetap.']);
            }

            if ($pasienId) {
                $pasien = Pasien::findOrFail($pasienId);
                if (IdentitasPasien::where('pasien_id', $pasien->id)->exists()) {
                    throw ValidationException::withMessages(['pasien_id' =>
                        'Pasien ' . $pasien->kode_pasien . ' sudah terkait dengan identitas sementara lain.']);
                }
                $detail = 'Dikaitkan dengan pasien yang sudah ada: ' . $pasien->kode_pasien . ' - ' . $pasien->nama . '.';
            } else {
                $nik = $identitas->nomor_identitas;
                $nikValid = $nik && preg_match('/^\d{16}$/', $nik) && ! Pasien::where('nik', $nik)->exists();
                $pasien = Pasien::create([
                    'kode_pasien' => Pasien::kodeBerikutnya(),
                    'nama' => $identitas->nama_sementara,
                    'nik' => $nikValid ? $nik : null,
                    'tanggal_lahir' => $identitas->tanggal_lahir,
                    'no_hp' => $identitas->kontak,
                ]);
                $detail = 'Dijadikan pasien tetap baru: ' . $pasien->kode_pasien . ' - ' . $pasien->nama . '.';
            }

            $identitas->update(['pasien_id' => $pasien->id]);
            $this->catatRiwayat($identitas->id, $userId, 'penyelesaian', $detail);

            return $pasien;
        });
    }

    /**
     * Petunjuk pasien tetap yang mungkin orang yang sama (NIK sama atau nama mirip).
     * Hanya saran; keputusan tetap di tangan petugas.
     */
    public function kandidatPasien(IdentitasPasien $identitas): Collection
    {
        $nik = $identitas->nomor_identitas;
        $nama = Str::lower(trim($identitas->nama_sementara));

        return Pasien::whereDoesntHave('identitasAsal')
            ->where(fn ($q) => $q
                ->when($nik, fn ($q) => $q->orWhere('nik', $nik))
                ->orWhereRaw('LOWER(nama) LIKE ?', ['%' . $nama . '%']))
            ->limit(5)->get()
            ->map(function (Pasien $p) use ($nik) {
                $p->alasan_cocok = ($nik && $p->nik === $nik) ? 'NIK sama' : 'nama mirip';

                return $p;
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

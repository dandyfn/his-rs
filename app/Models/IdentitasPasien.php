<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class IdentitasPasien extends Model
{
    protected $table = 'identitas_pasien_detail';

    protected $fillable = [
        'pasien_id', 'kode_sementara', 'nama_sementara', 'tanggal_lahir', 'nomor_identitas',
        'kontak', 'status_verifikasi', 'catatan_verifikasi', 'dibuat_oleh', 'diverifikasi_oleh',
    ];

    protected function casts(): array
    {
        return ['tanggal_lahir' => 'date'];
    }

    public function riwayat(): HasMany
    {
        return $this->hasMany(RiwayatIdentitas::class, 'identitas_id')->latest('id');
    }

    public function pasien(): BelongsTo
    {
        return $this->belongsTo(Pasien::class);
    }

    /** Rekomendasi tindak lanjut berbasis aturan (sama dengan versi native). */
    public function rekomendasi(): string
    {
        if ($this->pasien_id) {
            return 'Identitas telah diselesaikan menjadi pasien tetap. Perubahan data dilakukan melalui menu Data Pasien.';
        }
        if ($this->status_verifikasi === 'terverifikasi') {
            return 'Identitas telah diverifikasi. Jadikan pasien tetap agar dapat dipakai di antrean dan layanan lain.';
        }
        if (empty($this->nomor_identitas) || empty($this->tanggal_lahir)) {
            return 'Lengkapi informasi yang belum tersedia dan konfirmasikan kepada pasien atau pendamping.';
        }

        return 'Cocokkan informasi dengan dokumen pendukung sebelum verifikasi.';
    }

    public function labelStatus(): string
    {
        return $this->pasien_id ? 'pasien tetap' : str_replace('_', ' ', $this->status_verifikasi);
    }

    public function kelasStatus(): string
    {
        if ($this->pasien_id) {
            return 'badge-ungu';
        }

        return match ($this->status_verifikasi) {
            'terverifikasi' => 'badge-hijau',
            'perlu_konfirmasi' => 'badge-kuning',
            default => 'badge-merah',
        };
    }
}

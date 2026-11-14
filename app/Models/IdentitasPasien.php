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
        if ($this->status_verifikasi === 'terverifikasi') {
            return 'Identitas telah diverifikasi. Perubahan berikutnya perlu didukung bukti.';
        }
        if (empty($this->nomor_identitas) || empty($this->tanggal_lahir)) {
            return 'Lengkapi informasi yang belum tersedia dan konfirmasikan kepada pasien atau pendamping.';
        }

        return 'Cocokkan informasi dengan dokumen pendukung sebelum verifikasi.';
    }

    public function kelasStatus(): string
    {
        return match ($this->status_verifikasi) {
            'terverifikasi' => 'badge-hijau',
            'perlu_konfirmasi' => 'badge-kuning',
            default => 'badge-merah',
        };
    }
}

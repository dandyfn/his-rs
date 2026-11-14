<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Antrean extends Model
{
    protected $table = 'antrean';

    public const STATUS = ['menunggu', 'dipanggil', 'dilayani', 'selesai', 'batal'];

    protected $fillable = [
        'nomor_antrean', 'pasien_id', 'poli_id', 'tanggal_kunjungan', 'status', 'waktu_mulai', 'waktu_selesai',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_kunjungan' => 'date',
            'waktu_mulai' => 'datetime',
            'waktu_selesai' => 'datetime',
        ];
    }

    public function pasien(): BelongsTo
    {
        return $this->belongsTo(Pasien::class);
    }

    public function poli(): BelongsTo
    {
        return $this->belongsTo(Poli::class);
    }

    public function riwayat(): HasMany
    {
        return $this->hasMany(RiwayatStatus::class);
    }

    /** Lama menunggu (menit) dari daftar sampai mulai dilayani. */
    public function menitTunggu(): ?int
    {
        return $this->waktu_mulai ? (int) round(abs($this->created_at->diffInMinutes($this->waktu_mulai))) : null;
    }

    public function kelasStatus(): string
    {
        return match ($this->status) {
            'menunggu' => 'badge-kuning',
            'dipanggil' => 'badge-biru',
            'dilayani' => 'badge-ungu',
            'selesai' => 'badge-hijau',
            default => 'badge-abu',
        };
    }
}

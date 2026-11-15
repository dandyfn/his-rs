<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Stok per batch (FR-05). Total sisa semua batch = obat.stok. */
class BatchObat extends Model
{
    protected $table = 'batch_obat';

    protected $fillable = ['obat_id', 'nomor_batch', 'tanggal_kedaluwarsa', 'jumlah_awal', 'sisa', 'penerimaan_id'];

    protected function casts(): array
    {
        return ['tanggal_kedaluwarsa' => 'date', 'jumlah_awal' => 'integer', 'sisa' => 'integer'];
    }

    public function obat(): BelongsTo
    {
        return $this->belongsTo(Obat::class);
    }

    public function penerimaan(): BelongsTo
    {
        return $this->belongsTo(Penerimaan::class);
    }

    /** Masih ada sisa dan belum kedaluwarsa, urut FEFO (kedaluwarsa paling dekat dulu). */
    public function scopeLayakJual(Builder $q): Builder
    {
        return $q->where('sisa', '>', 0)
            ->whereDate('tanggal_kedaluwarsa', '>=', today())
            ->orderBy('tanggal_kedaluwarsa')->orderBy('id');
    }

    public function sisaHari(): int
    {
        return (int) today()->diffInDays($this->tanggal_kedaluwarsa, false);
    }

    public function sudahKedaluwarsa(): bool
    {
        return $this->tanggal_kedaluwarsa->lt(today());
    }

    /** [label, kelas badge] untuk status kedaluwarsa (FR-07). */
    public function statusKedaluwarsa(?int $batas = null): array
    {
        $batas ??= config('hak_akses.batas_kedaluwarsa_hari');
        $hari = $this->sisaHari();

        return match (true) {
            $hari < 0 => ['Kedaluwarsa', 'badge-merah'],
            $hari <= 30 => ["{$hari} hari lagi", 'badge-merah'],
            $hari <= $batas => ["{$hari} hari lagi", 'badge-kuning'],
            default => ['Aman', 'badge-hijau'],
        };
    }
}

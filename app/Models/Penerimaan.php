<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/** Penerimaan barang dari supplier (UC-06). */
class Penerimaan extends Model
{
    protected $table = 'penerimaan_obat';

    protected $fillable = [
        'nomor_penerimaan', 'pembelian_id', 'jumlah', 'nomor_batch', 'tanggal_kedaluwarsa',
        'tanggal_terima', 'keterangan', 'user_id',
    ];

    protected function casts(): array
    {
        return ['tanggal_kedaluwarsa' => 'date', 'tanggal_terima' => 'date', 'jumlah' => 'integer'];
    }

    public function pembelian(): BelongsTo
    {
        return $this->belongsTo(Pembelian::class);
    }

    public function batch(): HasOne
    {
        return $this->hasOne(BatchObat::class);
    }

    public function petugas(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}

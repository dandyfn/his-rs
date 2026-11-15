<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransaksiStok extends Model
{
    protected $table = 'transaksi_stok_obat';

    /** Sumber "keluar" yang dihitung sebagai pemakaian untuk DDDM (bukan retur / penyesuaian). */
    public const SUMBER_PEMAKAIAN = ['penjualan', 'demo'];

    protected $fillable = [
        'obat_id', 'batch_id', 'jenis', 'jumlah', 'keterangan', 'sumber', 'referensi_id', 'user_id', 'created_at',
    ];

    public function obat(): BelongsTo
    {
        return $this->belongsTo(Obat::class);
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(BatchObat::class);
    }

    public function petugas(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}

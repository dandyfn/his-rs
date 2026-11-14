<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransaksiStok extends Model
{
    protected $table = 'transaksi_stok_obat';

    protected $fillable = [
        'obat_id', 'jenis', 'jumlah', 'keterangan', 'sumber', 'referensi_id', 'user_id', 'created_at',
    ];

    public function obat(): BelongsTo
    {
        return $this->belongsTo(Obat::class);
    }
}

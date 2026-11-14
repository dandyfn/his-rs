<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Pembelian extends Model
{
    protected $table = 'pembelian_obat';

    protected $fillable = [
        'nomor_pembelian', 'supplier_id', 'obat_id', 'jumlah', 'harga_satuan',
        'total_harga', 'tanggal_pembelian', 'keterangan', 'user_id',
    ];

    protected function casts(): array
    {
        return ['tanggal_pembelian' => 'date', 'jumlah' => 'integer'];
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function obat(): BelongsTo
    {
        return $this->belongsTo(Obat::class);
    }
}

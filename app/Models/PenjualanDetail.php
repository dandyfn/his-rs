<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PenjualanDetail extends Model
{
    protected $table = 'penjualan_obat_detail';

    public $timestamps = false;

    protected $fillable = ['penjualan_id', 'obat_id', 'batch_id', 'jumlah', 'harga_satuan', 'subtotal'];

    public function obat(): BelongsTo
    {
        return $this->belongsTo(Obat::class);
    }

    public function penjualan(): BelongsTo
    {
        return $this->belongsTo(Penjualan::class);
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(BatchObat::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Obat extends Model
{
    protected $table = 'obat';

    protected $fillable = [
        'kode_obat', 'nama_obat', 'satuan', 'stok', 'stok_minimum', 'harga_jual',
        'lead_time_hari', 'stok_pengaman_hari', 'interval_pesan_hari',
    ];

    protected function casts(): array
    {
        return [
            'stok' => 'integer',
            'stok_minimum' => 'integer',
            'harga_jual' => 'decimal:2',
            'lead_time_hari' => 'integer',
            'stok_pengaman_hari' => 'integer',
            'interval_pesan_hari' => 'integer',
        ];
    }

    public function transaksiStok(): HasMany
    {
        return $this->hasMany(TransaksiStok::class);
    }

    public function perluDitinjau(): bool
    {
        return $this->stok <= $this->stok_minimum;
    }
}

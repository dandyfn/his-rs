<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Pesanan pengadaan ke supplier (UC-04). Stok bertambah saat barang diterima (UC-06). */
class Pembelian extends Model
{
    protected $table = 'pembelian_obat';

    public const STATUS = [
        'dipesan' => ['Menunggu barang', 'badge-kuning'],
        'sebagian' => ['Diterima sebagian', 'badge-biru'],
        'diterima' => ['Diterima lengkap', 'badge-hijau'],
        'batal' => ['Dibatalkan', 'badge-abu'],
    ];

    protected $fillable = [
        'nomor_pembelian', 'supplier_id', 'obat_id', 'jumlah', 'jumlah_diterima', 'harga_satuan',
        'total_harga', 'status', 'tanggal_pembelian', 'keterangan', 'user_id',
    ];

    protected function casts(): array
    {
        return ['tanggal_pembelian' => 'date', 'jumlah' => 'integer', 'jumlah_diterima' => 'integer'];
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function obat(): BelongsTo
    {
        return $this->belongsTo(Obat::class);
    }

    public function penerimaan(): HasMany
    {
        return $this->hasMany(Penerimaan::class)->latest('id');
    }

    public function sisaBelumDiterima(): int
    {
        return max(0, $this->jumlah - $this->jumlah_diterima);
    }

    public function bisaDiterima(): bool
    {
        return in_array($this->status, ['dipesan', 'sebagian'], true);
    }

    public function labelStatus(): array
    {
        return self::STATUS[$this->status] ?? [$this->status, 'badge-abu'];
    }
}

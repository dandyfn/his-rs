<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Penjualan extends Model
{
    protected $table = 'penjualan_obat';

    protected $fillable = [
        'nomor_penjualan', 'pasien_id', 'nama_pembeli', 'jenis', 'nomor_resep', 'total', 'user_id',
    ];

    public function detail(): HasMany
    {
        return $this->hasMany(PenjualanDetail::class);
    }

    public function pasien(): BelongsTo
    {
        return $this->belongsTo(Pasien::class);
    }

    public function petugas(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}

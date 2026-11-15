<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** NFR-09: setiap perubahan harga tercatat (siapa, kapan, apa). */
class RiwayatHarga extends Model
{
    protected $table = 'riwayat_harga_obat';

    protected $fillable = ['obat_id', 'harga_lama', 'harga_baru', 'user_id'];

    public function obat(): BelongsTo
    {
        return $this->belongsTo(Obat::class);
    }

    public function petugas(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pasien extends Model
{
    protected $table = 'pasien';

    protected $fillable = ['kode_pasien', 'nama'];

    public function penjualan(): HasMany
    {
        return $this->hasMany(Penjualan::class);
    }
}

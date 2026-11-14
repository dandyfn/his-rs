<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Supplier extends Model
{
    protected $table = 'supplier_obat';

    protected $fillable = ['kode_supplier', 'nama_supplier', 'kontak', 'alamat', 'aktif'];

    protected function casts(): array
    {
        return ['aktif' => 'boolean'];
    }

    public function pembelian(): HasMany
    {
        return $this->hasMany(Pembelian::class);
    }
}

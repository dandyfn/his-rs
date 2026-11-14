<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Poli extends Model
{
    protected $table = 'poli';

    protected $fillable = ['nama_poli', 'batas_antrean', 'aktif'];

    protected function casts(): array
    {
        return ['aktif' => 'boolean', 'batas_antrean' => 'integer'];
    }

    public function antrean(): HasMany
    {
        return $this->hasMany(Antrean::class);
    }
}

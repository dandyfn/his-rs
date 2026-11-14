<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RiwayatIdentitas extends Model
{
    protected $table = 'riwayat_identitas_pasien';

    protected $fillable = ['identitas_id', 'user_id', 'aksi', 'detail'];
}

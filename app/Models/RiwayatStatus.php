<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RiwayatStatus extends Model
{
    protected $table = 'riwayat_status';

    protected $fillable = ['antrean_id', 'user_id', 'status_sebelumnya', 'status_baru'];

    public function antrean(): BelongsTo
    {
        return $this->belongsTo(Antrean::class);
    }

    public function petugas(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}

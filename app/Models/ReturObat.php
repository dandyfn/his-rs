<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Retur obat rusak/kedaluwarsa ke supplier atau pemusnahan (FR-11, UC-09). */
class ReturObat extends Model
{
    protected $table = 'retur_obat';

    public const ALASAN = [
        'kedaluwarsa' => 'Kedaluwarsa',
        'rusak' => 'Rusak / kemasan cacat',
        'salah_kirim' => 'Salah kirim dari supplier',
        'ditarik' => 'Ditarik dari peredaran (recall)',
    ];

    public const TUJUAN = ['supplier' => 'Dikembalikan ke supplier', 'pemusnahan' => 'Dimusnahkan'];

    protected $fillable = ['nomor_retur', 'obat_id', 'batch_id', 'jumlah', 'alasan', 'tujuan', 'keterangan', 'user_id'];

    public function obat(): BelongsTo
    {
        return $this->belongsTo(Obat::class);
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(BatchObat::class);
    }

    public function petugas(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}

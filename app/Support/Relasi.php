<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;

/**
 * Menghemat query ke database awan: memasang relasi belongsTo dari data yang SUDAH dimuat
 * di halaman yang sama, dan hanya mengambil sisanya (jika ada) dalam satu query.
 * Contoh: daftar obat sudah dimuat, maka riwayat transaksi tidak perlu query obat lagi.
 */
class Relasi
{
    /**
     * @param  iterable  $items  model yang ingin dipasangi relasi
     * @param  string  $fk  nama kolom foreign key, mis. 'obat_id'
     * @param  string  $relasi  nama relasi, mis. 'obat'
     * @param  Collection  $tersedia  model yang sudah dimuat (boleh kosong)
     * @param  class-string  $model  kelas model relasi, untuk mengambil yang belum tersedia
     */
    public static function pasang(iterable $items, string $fk, string $relasi, Collection $tersedia, string $model): Collection
    {
        $peta = $tersedia->keyBy('id');
        $kurang = collect($items)->pluck($fk)->filter()->unique()->reject(fn ($id) => $peta->has($id));
        if ($kurang->isNotEmpty()) {
            $peta = $peta->union($model::whereIn('id', $kurang->values())->get()->keyBy('id'));
        }
        foreach ($items as $item) {
            $item->setRelation($relasi, $peta->get($item->$fk));
        }

        return $peta;
    }
}

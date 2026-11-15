<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Pasien extends Model
{
    protected $table = 'pasien';

    protected $fillable = ['kode_pasien', 'nama', 'nik', 'tanggal_lahir', 'jenis_kelamin', 'alamat', 'no_hp'];

    protected function casts(): array
    {
        return ['tanggal_lahir' => 'date'];
    }

    public function penjualan(): HasMany
    {
        return $this->hasMany(Penjualan::class);
    }

    public function antrean(): HasMany
    {
        return $this->hasMany(Antrean::class);
    }

    /** Identitas sementara yang sudah diselesaikan menjadi pasien ini (FR-07). */
    public function identitasAsal(): HasOne
    {
        return $this->hasOne(IdentitasPasien::class);
    }

    public function umur(): ?int
    {
        return $this->tanggal_lahir?->age;
    }

    public function labelJenisKelamin(): string
    {
        return match ($this->jenis_kelamin) {
            'L' => 'Laki-laki',
            'P' => 'Perempuan',
            default => '-',
        };
    }

    /** Kode pasien berikutnya dengan pola P-0001, P-0002, ... */
    public static function kodeBerikutnya(): string
    {
        $maks = static::where('kode_pasien', 'like', 'P-%')->pluck('kode_pasien')
            ->map(fn ($k) => ctype_digit(substr($k, 2)) ? (int) substr($k, 2) : 0)
            ->max() ?? 0;

        return sprintf('P-%04d', $maks + 1);
    }
}

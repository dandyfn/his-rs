<?php

namespace App\Services;

use App\Models\Antrean;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * Prediksi beban dan waktu tunggu poli (DDDM subsistem Antrean).
 *
 * Tidak memakai machine learning, hanya statistik sederhana dari riwayat antrean
 * 4 minggu terakhir, supaya setiap angka bisa dijelaskan ke petugas:
 *
 *  1. Lama layanan biasa  = median (mulai dilayani -> selesai) per poli.
 *  2. Perkiraan tunggu    = jumlah antrean di depan x lama layanan biasa.
 *  3. Beban dibanding biasa = jumlah antrean hari ini dibandingkan median jumlah antrean
 *     pada HARI YANG SAMA (Senin dengan Senin, dst.) sampai JAM YANG SAMA.
 *
 * Median dipakai (bukan rata-rata) supaya satu hari atau satu pasien yang luar biasa
 * tidak menarik hasilnya terlalu jauh.
 *
 * Aturan data minimal: perbandingan beban baru dihitung jika ada >= 3 hari pembanding,
 * dan lama layanan baru dipakai jika ada >= 5 pelayanan selesai. Jika belum cukup,
 * nilainya null dan halaman menulis "data riwayat belum cukup".
 */
class PrediksiAntreanService
{
    public const MINGGU = 4;
    public const HARI_MINIMAL = 3;
    public const LAYANAN_MINIMAL = 5;
    /** Poli dianggap "lebih ramai dari biasa" jika >= 30% di atas biasanya. */
    public const AMBANG_RAMAI = 30;
    /** ...dan minimal ada sekian antrean hari ini (supaya 2 vs 1 pasien tidak dianggap lonjakan 100%). */
    public const ANTREAN_MINIMAL_RAMAI = 3;

    /**
     * Prediksi untuk setiap poli hari ini.
     *
     * @param  Collection  $poli  daftar poli (model Poli)
     * @param  Collection|null  $antreanHariIni  semua antrean hari ini (termasuk batal), opsional supaya hemat query
     * @return Collection  [poli_id => array prediksi]
     */
    public function untukHariIni(Collection $poli, ?Collection $antreanHariIni = null, ?CarbonInterface $sekarang = null): Collection
    {
        $sekarang ??= now();
        $hariIni = $sekarang->copy()->startOfDay();
        $antreanHariIni ??= Antrean::whereDate('tanggal_kunjungan', $hariIni)->get();

        // Riwayat 4 minggu terakhir (tidak termasuk hari ini).
        $riwayat = Antrean::whereDate('tanggal_kunjungan', '>=', $hariIni->copy()->subDays(self::MINGGU * 7)->toDateString())
            ->whereDate('tanggal_kunjungan', '<', $hariIni->toDateString())
            ->get(['id', 'poli_id', 'tanggal_kunjungan', 'status', 'created_at', 'waktu_mulai', 'waktu_selesai']);

        // Hari pembanding = hari yang sama di 4 minggu terakhir yang benar-benar tercatat ada antrean
        // (hari libur/tanpa data tidak dihitung sebagai "0 pasien").
        $adaData = $riwayat->groupBy(fn ($a) => $a->tanggal_kunjungan->toDateString());
        $pembanding = collect(range(1, self::MINGGU))
            ->map(fn ($w) => $hariIni->copy()->subWeeks($w)->toDateString())
            ->filter(fn ($t) => $adaData->has($t))
            ->values();
        $jam = $sekarang->format('H:i:s');

        return $poli->mapWithKeys(function ($p) use ($riwayat, $pembanding, $jam, $antreanHariIni, $hariIni) {
            $riwayatPoli = $riwayat->where('poli_id', $p->id);
            $hariIniPoli = $antreanHariIni->where('poli_id', $p->id);

            // 1. Lama layanan biasa
            $lama = $riwayatPoli->map->menitLayanan()->filter(fn ($m) => $m !== null)->values();
            $lamaLayanan = $lama->count() >= self::LAYANAN_MINIMAL ? (int) round(self::median($lama)) : null;

            // 2. Perkiraan tunggu untuk pasien yang baru mendaftar sekarang
            $diDepan = $hariIniPoli->whereIn('status', ['menunggu', 'dipanggil', 'dilayani'])->count();
            $tungguBaru = $lamaLayanan === null ? null : $diDepan * $lamaLayanan;

            // 3. Beban dibanding hari yang sama sampai jam yang sama
            $jumlahHariIni = $hariIniPoli->count();
            $biasaSampaiJam = $biasaSehari = $selisih = null;
            if ($pembanding->count() >= self::HARI_MINIMAL) {
                $padaTanggal = fn ($t) => $riwayatPoli->filter(fn ($a) => $a->tanggal_kunjungan->toDateString() === $t);
                $biasaSampaiJam = self::median($pembanding->map(
                    fn ($t) => $padaTanggal($t)->filter(fn ($a) => $a->created_at->format('H:i:s') <= $jam)->count()));
                $biasaSehari = self::median($pembanding->map(fn ($t) => $padaTanggal($t)->count()));
                $selisih = $biasaSampaiJam > 0 ? (int) round(($jumlahHariIni - $biasaSampaiJam) / $biasaSampaiJam * 100) : null;
            }

            return [$p->id => [
                'lama_layanan' => $lamaLayanan,
                'sampel_layanan' => $lama->count(),
                'di_depan' => $diDepan,
                'tunggu_baru' => $tungguBaru,
                'hari' => $hariIni->translatedFormat('l'),
                'hari_pembanding' => $pembanding->count(),
                'jumlah_hari_ini' => $jumlahHariIni,
                'biasa_sampai_jam' => $biasaSampaiJam,
                'biasa_sehari' => $biasaSehari,
                'selisih_persen' => $selisih,
                'ramai' => $selisih !== null && $selisih >= self::AMBANG_RAMAI && $jumlahHariIni >= self::ANTREAN_MINIMAL_RAMAI,
            ]];
        });
    }

    /**
     * Perkiraan waktu tunggu (menit) untuk setiap antrean berstatus "menunggu".
     * Rumus: (antrean yang sedang dipanggil/dilayani + antrean menunggu di depannya) x lama layanan biasa.
     *
     * @return array  [antrean_id => menit|null]  null = data riwayat belum cukup
     */
    public function perkiraanPerAntrean(Collection $antreanHariIni, Collection $prediksi): array
    {
        $hasil = [];
        foreach ($antreanHariIni->groupBy('poli_id') as $poliId => $daftar) {
            $lama = $prediksi[$poliId]['lama_layanan'] ?? null;
            $berjalan = $daftar->whereIn('status', ['dipanggil', 'dilayani'])->count();
            // Urut berdasarkan waktu daftar (yang datang lebih dulu dilayani lebih dulu).
            foreach ($daftar->where('status', 'menunggu')->sortBy(fn ($x) => [$x->created_at, $x->id])->values() as $urutan => $a) {
                $hasil[$a->id] = $lama === null ? null : ($berjalan + $urutan) * $lama;
            }
        }

        return $hasil;
    }

    /** Nilai tengah. Contoh: median [4, 9, 6] = 6; median [4, 6, 8, 20] = 7. */
    public static function median(Collection $nilai): float
    {
        $urut = $nilai->sort()->values();
        $n = $urut->count();
        if ($n === 0) {
            return 0;
        }
        $tengah = intdiv($n, 2);

        return $n % 2 ? (float) $urut[$tengah] : ($urut[$tengah - 1] + $urut[$tengah]) / 2;
    }
}

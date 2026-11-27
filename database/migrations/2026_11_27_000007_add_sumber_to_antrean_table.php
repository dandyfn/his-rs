<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Menandai asal data antrean. 'demo' = data simulasi (boleh disegarkan oleh perintah
 * demo:segarkan); kosong (null) = antrean yang didaftarkan petugas lewat aplikasi.
 *
 * Antrean yang SUDAH ADA saat migrasi ini dijalankan ditandai 'demo', karena sampai versi ini
 * aplikasi baru dipakai untuk demo (isinya berasal dari seeder dan uji coba). Antrean yang
 * didaftarkan setelah migrasi ini tidak bertanda dan tidak pernah disentuh demo:segarkan.
 * Tidak ada data yang dihapus oleh migrasi ini.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('antrean', function (Blueprint $table) {
            $table->string('sumber', 20)->nullable()->after('waktu_selesai');
            $table->index('sumber');
        });

        DB::table('antrean')->update(['sumber' => 'demo']);
    }

    public function down(): void
    {
        Schema::table('antrean', function (Blueprint $table) {
            $table->dropIndex(['sumber']);
            $table->dropColumn('sumber');
        });
    }
};

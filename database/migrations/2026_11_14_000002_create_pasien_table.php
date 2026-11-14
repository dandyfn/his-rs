<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Data induk pasien (milik subsistem Pendaftaran & Identitas Pasien).
 * Versi minimal: dipakai subsistem obat untuk mencatat pembeli/penerima resep.
 * Kolom lain (identitas, verifikasi) ditambahkan oleh subsistem pendaftaran.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pasien', function (Blueprint $table) {
            $table->id();
            $table->string('kode_pasien', 20)->unique();
            $table->string('nama', 100);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pasien');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Subsistem Pendaftaran & Identitas Pasien dan Subsistem Antrean Rawat Jalan (SIRATA).
 * Dipindahkan dari sirata.sql versi PHP native.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ---------- Pendaftaran & Identitas Pasien ----------
        Schema::create('identitas_pasien_detail', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pasien_id')->nullable()->constrained('pasien')->nullOnDelete();
            $table->string('kode_sementara', 30)->unique();
            $table->string('nama_sementara', 100)->index();
            $table->date('tanggal_lahir')->nullable();
            $table->string('nomor_identitas', 50)->nullable()->index();
            $table->string('kontak', 30)->nullable();
            $table->string('status_verifikasi', 20)->default('sementara'); // sementara | perlu_konfirmasi | terverifikasi
            $table->text('catatan_verifikasi')->nullable();
            $table->foreignId('dibuat_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('diverifikasi_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('riwayat_identitas_pasien', function (Blueprint $table) {
            $table->id();
            $table->foreignId('identitas_id')->constrained('identitas_pasien_detail')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('aksi', 40);
            $table->text('detail');
            $table->timestamps();
        });

        // ---------- Antrean Rawat Jalan (SIRATA) ----------
        Schema::create('poli', function (Blueprint $table) {
            $table->id();
            $table->string('nama_poli', 100)->unique();
            $table->unsignedInteger('batas_antrean')->default(20);
            $table->boolean('aktif')->default(true);
            $table->timestamps();
        });

        Schema::create('antrean', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_antrean', 20);
            $table->foreignId('pasien_id')->constrained('pasien')->restrictOnDelete();
            $table->foreignId('poli_id')->constrained('poli')->restrictOnDelete();
            $table->date('tanggal_kunjungan');
            $table->string('status', 20)->default('menunggu'); // menunggu | dipanggil | dilayani | selesai | batal
            $table->dateTime('waktu_mulai')->nullable();   // saat mulai dilayani
            $table->dateTime('waktu_selesai')->nullable();
            $table->timestamps();                          // created_at = waktu daftar
            $table->index(['tanggal_kunjungan', 'poli_id']);
            $table->index('status');
        });

        Schema::create('riwayat_status', function (Blueprint $table) {
            $table->id();
            $table->foreignId('antrean_id')->constrained('antrean')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status_sebelumnya', 20)->nullable();
            $table->string('status_baru', 20);
            $table->timestamps(); // created_at = waktu perubahan
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('riwayat_status');
        Schema::dropIfExists('antrean');
        Schema::dropIfExists('poli');
        Schema::dropIfExists('riwayat_identitas_pasien');
        Schema::dropIfExists('identitas_pasien_detail');
    }
};

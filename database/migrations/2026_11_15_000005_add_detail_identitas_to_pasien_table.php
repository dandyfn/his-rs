<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * FR-01 (SRS Pendaftaran): data identitas pasien yang lebih lengkap.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pasien', function (Blueprint $table) {
            $table->string('nik', 16)->nullable()->unique()->after('nama');
            $table->date('tanggal_lahir')->nullable()->after('nik');
            $table->string('jenis_kelamin', 1)->nullable()->after('tanggal_lahir'); // L / P
            $table->text('alamat')->nullable()->after('jenis_kelamin');
            $table->string('no_hp', 20)->nullable()->after('alamat');
        });
    }

    public function down(): void
    {
        Schema::table('pasien', function (Blueprint $table) {
            $table->dropUnique(['nik']);
            $table->dropColumn(['nik', 'tanggal_lahir', 'jenis_kelamin', 'alamat', 'no_hp']);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * MediStock RS tahap 2 (SRS FR-01, FR-03, FR-04, FR-05, FR-07, FR-09, FR-11, NFR-09):
 * - obat dapat dinonaktifkan
 * - stok per batch + tanggal kedaluwarsa (FEFO)
 * - pesanan pengadaan dua tahap: dipesan -> diterima (sebagian/penuh)
 * - retur obat dan riwayat perubahan harga
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('obat', function (Blueprint $table) {
            $table->boolean('aktif')->default(true)->after('interval_pesan_hari');
        });

        Schema::table('pembelian_obat', function (Blueprint $table) {
            $table->string('status', 15)->default('dipesan')->after('total_harga'); // dipesan | sebagian | diterima | batal
            $table->unsignedInteger('jumlah_diterima')->default(0)->after('jumlah');
        });

        Schema::create('penerimaan_obat', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_penerimaan', 40)->unique();
            $table->foreignId('pembelian_id')->constrained('pembelian_obat');
            $table->unsignedInteger('jumlah');
            $table->string('nomor_batch', 50);
            $table->date('tanggal_kedaluwarsa');
            $table->date('tanggal_terima');
            $table->string('keterangan', 255)->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('batch_obat', function (Blueprint $table) {
            $table->id();
            $table->foreignId('obat_id')->constrained('obat');
            $table->string('nomor_batch', 50);
            $table->date('tanggal_kedaluwarsa')->index();
            $table->unsignedInteger('jumlah_awal');
            $table->unsignedInteger('sisa');
            $table->foreignId('penerimaan_id')->nullable()->constrained('penerimaan_obat')->nullOnDelete();
            $table->timestamps();
            $table->unique(['obat_id', 'nomor_batch']);
        });

        Schema::table('transaksi_stok_obat', function (Blueprint $table) {
            $table->foreignId('batch_id')->nullable()->after('obat_id')->constrained('batch_obat')->nullOnDelete();
        });

        Schema::table('penjualan_obat_detail', function (Blueprint $table) {
            $table->foreignId('batch_id')->nullable()->after('obat_id')->constrained('batch_obat')->nullOnDelete();
        });

        Schema::create('retur_obat', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_retur', 40)->unique();
            $table->foreignId('obat_id')->constrained('obat');
            $table->foreignId('batch_id')->constrained('batch_obat');
            $table->unsignedInteger('jumlah');
            $table->string('alasan', 20);  // kedaluwarsa | rusak | salah_kirim | ditarik
            $table->string('tujuan', 20);  // supplier | pemusnahan
            $table->string('keterangan', 255)->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('riwayat_harga_obat', function (Blueprint $table) {
            $table->id();
            $table->foreignId('obat_id')->constrained('obat')->cascadeOnDelete();
            $table->decimal('harga_lama', 15, 2);
            $table->decimal('harga_baru', 15, 2);
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // Data lama: pembelian yang sudah ada dianggap sudah diterima,
        // dan stok obat yang sudah ada dijadikan satu batch awal.
        DB::table('pembelian_obat')->update(['status' => 'diterima', 'jumlah_diterima' => DB::raw('jumlah')]);
        foreach (DB::table('obat')->where('stok', '>', 0)->get() as $obat) {
            DB::table('batch_obat')->insert([
                'obat_id' => $obat->id, 'nomor_batch' => 'AWAL-' . $obat->id,
                'tanggal_kedaluwarsa' => now()->addYear()->toDateString(),
                'jumlah_awal' => $obat->stok, 'sisa' => $obat->stok,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('riwayat_harga_obat');
        Schema::dropIfExists('retur_obat');
        Schema::table('penjualan_obat_detail', fn (Blueprint $t) => $t->dropConstrainedForeignId('batch_id'));
        Schema::table('transaksi_stok_obat', fn (Blueprint $t) => $t->dropConstrainedForeignId('batch_id'));
        Schema::dropIfExists('batch_obat');
        Schema::dropIfExists('penerimaan_obat');
        Schema::table('pembelian_obat', fn (Blueprint $t) => $t->dropColumn(['status', 'jumlah_diterima']));
        Schema::table('obat', fn (Blueprint $t) => $t->dropColumn('aktif'));
    }
};

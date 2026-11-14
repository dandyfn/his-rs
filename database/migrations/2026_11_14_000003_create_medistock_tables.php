<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Subsistem Manajemen Stok Obat (MediStock RS).
 * F1: obat, supplier, pembelian, transaksi stok
 * F2: penjualan obat ke pasien
 * F3: parameter rekomendasi pengadaan (lead time, stok pengaman, interval pesan)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('obat', function (Blueprint $table) {
            $table->id();
            $table->string('kode_obat', 30)->unique();
            $table->string('nama_obat', 150);
            $table->string('satuan', 30)->default('tablet');
            $table->unsignedInteger('stok')->default(0);
            $table->unsignedInteger('stok_minimum')->default(10);
            $table->decimal('harga_jual', 15, 2)->default(0);
            $table->unsignedSmallInteger('lead_time_hari')->default(7);      // L
            $table->unsignedSmallInteger('stok_pengaman_hari')->default(3);  // SS = d x hari
            $table->unsignedSmallInteger('interval_pesan_hari')->default(14); // T
            $table->timestamps();
        });

        Schema::create('supplier_obat', function (Blueprint $table) {
            $table->id();
            $table->string('kode_supplier', 30)->unique();
            $table->string('nama_supplier', 150);
            $table->string('kontak', 50)->nullable();
            $table->text('alamat')->nullable();
            $table->boolean('aktif')->default(true);
            $table->timestamps();
        });

        Schema::create('pembelian_obat', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_pembelian', 40)->unique();
            $table->foreignId('supplier_id')->constrained('supplier_obat');
            $table->foreignId('obat_id')->constrained('obat');
            $table->unsignedInteger('jumlah');
            $table->decimal('harga_satuan', 15, 2);
            $table->decimal('total_harga', 17, 2);
            $table->date('tanggal_pembelian')->index();
            $table->string('keterangan', 255)->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('penjualan_obat', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_penjualan', 40)->unique();
            $table->foreignId('pasien_id')->nullable()->constrained('pasien')->nullOnDelete();
            $table->string('nama_pembeli', 100);
            $table->string('jenis', 10)->default('non_resep'); // resep | non_resep
            $table->string('nomor_resep', 50)->nullable();
            $table->decimal('total', 17, 2)->default(0);
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('penjualan_obat_detail', function (Blueprint $table) {
            $table->id();
            $table->foreignId('penjualan_id')->constrained('penjualan_obat')->cascadeOnDelete();
            $table->foreignId('obat_id')->constrained('obat');
            $table->unsignedInteger('jumlah');
            $table->decimal('harga_satuan', 15, 2);
            $table->decimal('subtotal', 17, 2);
        });

        /* Kartu stok: setiap perubahan stok tercatat di sini.
           Transaksi "keluar" menjadi data pemakaian untuk rekomendasi (DDDM). */
        Schema::create('transaksi_stok_obat', function (Blueprint $table) {
            $table->id();
            $table->foreignId('obat_id')->constrained('obat');
            $table->string('jenis', 10); // masuk | keluar
            $table->unsignedInteger('jumlah');
            $table->string('keterangan', 255)->nullable();
            $table->string('sumber', 20)->default('manual'); // manual | pembelian | penjualan
            $table->unsignedBigInteger('referensi_id')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['obat_id', 'jenis', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transaksi_stok_obat');
        Schema::dropIfExists('penjualan_obat_detail');
        Schema::dropIfExists('penjualan_obat');
        Schema::dropIfExists('pembelian_obat');
        Schema::dropIfExists('supplier_obat');
        Schema::dropIfExists('obat');
    }
};

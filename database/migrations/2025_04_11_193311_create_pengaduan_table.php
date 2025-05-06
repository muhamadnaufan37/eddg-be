<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('pengaduan', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->string('nama_lengkap', 100);
            $table->string('kontak', 100);
            $table->enum('jenis_pengaduan', ['keluhan_data', 'kritik_saran']);
            $table->string('subjek');
            $table->text('isi_pengaduan');
            $table->text('lampiran')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->enum('status_pengaduan', ['terkirim', 'diproses', 'selesai', 'dibatalkan']);
            $table->string('nama_kelompok', 45);
            $table->text('balasan_admin')->nullable();
            $table->timestamp('tanggal_dibalas')->nullable();
            $table->unsignedBigInteger('dibalas_oleh')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pengaduan');
    }
};

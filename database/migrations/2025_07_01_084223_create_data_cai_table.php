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
        Schema::create('data_cai', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('kode_cari_data');
            $table->string('nama_lengkap');
            $table->enum('jenis_kelamin', ['LAKI-LAKI', 'PEREMPUAN']);
            $table->enum('status_utusan', ['mt', 'utusan_desa', 'utusan_kelompok', 'utusan_desa_pondok']);
            $table->unsignedBigInteger('tmpt_daerah');
            $table->unsignedBigInteger('tmpt_desa')->nullable();
            $table->unsignedBigInteger('tmpt_kelompok')->nullable();
            $table->string('tahun');
            $table->string('img')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('data_cai');
    }
};

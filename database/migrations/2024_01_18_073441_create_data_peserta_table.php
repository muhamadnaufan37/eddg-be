<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('data_peserta', function (Blueprint $table) {
            $table->id();
            $table->string('kode_cari_data', 255);
            $table->string('nama_lengkap', 255);
            $table->string('nama_panggilan', 255);
            $table->string('tempat_lahir', 255);
            $table->date('tanggal_lahir', 255);
            $table->text('alamat', 255);
            $table->enum('jenis_kelamin', ['LAKI-LAKI', 'PEREMPUAN']);
            $table->string('no_telepon', 15);
            $table->string('nama_ayah', 255);
            $table->string('nama_ibu', 255);
            $table->string('hoby', 255);
            $table->string('pekerjaan', 255);
            $table->string('usia_menikah', 255)->nullable();
            $table->string('kriteria_pasangan', 255)->nullable();
            $table->boolean('status_pernikahan');
            $table->id('status_sambung');
            $table->foreignId('tmpt_daerah')->nullable();
            $table->foreignId('tmpt_desa')->nullable();
            $table->foreignId('tmpt_kelompok')->nullable();
            $table->foreignId('user_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('data_peserta');
    }
};

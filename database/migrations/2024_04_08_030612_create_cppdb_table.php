<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('cppdb', function (Blueprint $table) {
            $table->id();
            $table->string('kode_cari_ppdb', 255);
            $table->foreignId('id_thn_akademik');
            $table->foreignId('id_kelas');
            $table->foreignId('id_pengajar');
            $table->foreignId('id_peserta');
            $table->foreignId('id_petugas');
            $table->integer('nilai1')->nullable();
            $table->integer('nilai2')->nullable();
            $table->integer('nilai3')->nullable();
            $table->integer('nilai4')->nullable();
            $table->integer('nilai5')->nullable();
            $table->integer('nilai6')->nullable();
            $table->integer('nilai7')->nullable();
            $table->integer('nilai8')->nullable();
            $table->integer('nilai9')->nullable();
            $table->integer('nilai10')->nullable();
            $table->integer('nilai11')->nullable();
            $table->string('nilai12')->nullable();
            $table->string('nilai13')->nullable();
            $table->string('nilai14')->nullable();
            $table->string('nilai15')->nullable();
            $table->string('nilai16')->nullable();
            $table->integer('nilai_presensi_1')->nullable();
            $table->integer('nilai_presensi_2')->nullable();
            $table->integer('nilai_presensi_3')->nullable();
            $table->text('catatan_ortu')->nullable();
            $table->boolean('status_naik_kelas')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cppdb');
    }
};

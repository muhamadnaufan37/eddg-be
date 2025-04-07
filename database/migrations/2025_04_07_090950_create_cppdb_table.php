<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cppdb', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('kode_cari_ppdb');
            $table->unsignedBigInteger('id_thn_akademik');
            $table->unsignedBigInteger('id_kelas');
            $table->unsignedBigInteger('id_pengajar');
            $table->unsignedBigInteger('id_peserta');
            $table->unsignedBigInteger('id_petugas');
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
            $table->integer('nilai11_1')->nullable();
            $table->string('nilai12', 11)->nullable();
            $table->string('nilai13', 11)->nullable();
            $table->string('nilai14', 11)->nullable();
            $table->string('nilai15', 11)->nullable();
            $table->string('nilai16', 11)->nullable();
            $table->integer('nilai_presensi_1')->nullable();
            $table->integer('nilai_presensi_2')->nullable();
            $table->integer('nilai_presensi_3')->nullable();
            $table->text('catatan_ortu')->nullable();
            $table->date('tgl_penetapan')->nullable();
            $table->string('tmpt_penetapan')->nullable();
            $table->boolean('status_naik_kelas')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('cppdb');
    }
};

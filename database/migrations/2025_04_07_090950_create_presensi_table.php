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
        Schema::create('presensi', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('id_kegiatan');
            $table->unsignedBigInteger('id_peserta');
            $table->unsignedBigInteger('id_petugas');
            $table->enum('status_presensi', ['HADIR', 'TELAT HADIR', 'IZIN', 'SAKIT']);
            $table->text('keterangan');
            $table->dateTime('waktu_presensi');
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
        Schema::dropIfExists('presensi');
    }
};

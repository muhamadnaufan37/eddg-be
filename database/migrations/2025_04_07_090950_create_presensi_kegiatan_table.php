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
        Schema::create('presensi_kegiatan', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('kode_kegiatan');
            $table->string('nama_kegiatan');
            $table->string('tmpt_kegiatan');
            $table->enum('type_kegiatan', ['DAERAH', 'DESA', 'KELOMPOK']);
            $table->date('tgl_kegiatan');
            $table->time('jam_kegiatan');
            $table->dateTime('expired_date_time');
            $table->integer('usia_batas')->nullable();
            $table->enum('usia_operator', ['>=', '<=', '>', '<', '='])->nullable();
            $table->unsignedBigInteger('tmpt_daerah');
            $table->unsignedBigInteger('tmpt_desa')->nullable();
            $table->unsignedBigInteger('tmpt_kelompok')->nullable();
            $table->bigInteger('add_by_petugas');
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
        Schema::dropIfExists('presensi_kegiatan');
    }
};

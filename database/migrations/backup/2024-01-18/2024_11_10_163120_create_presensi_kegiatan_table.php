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
        Schema::create('presensi_kegiatan', function (Blueprint $table) {
            $table->id();
            $table->string('kode_kegiatan', 255);
            $table->string('nama_kegiatan', 255);
            $table->string('tmpt_kegiatan', 255);
            $table->enum('type_kegiatan', ['DAERAH', 'DESA', 'KELOMPOK']);
            $table->date('tgl_kegiatan');
            $table->time('jam_kegiatan');
            $table->dateTime('expired_date_time');
            $table->integer('usia_batas')->nullable();
            $table->enum('usia_operator', ['>=', '<=', '>', '<', '='])->nullable();
            $table->foreignId('add_by_petugas');
            $table->foreignId('tmpt_daerah');
            $table->foreignId('tmpt_desa')->nullable();
            $table->foreignId('tmpt_kelompok')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('presensi_kegiatan');
    }
};

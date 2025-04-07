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
        Schema::create('presensi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_kegiatan');
            $table->foreignId('id_peserta');
            $table->foreignId('id_petugas');
            $table->enum('status_presensi', ['HADIR', 'TIDAK HADIR', 'IZIN', 'SAKIT']);
            $table->text('keterangan');
            $table->dateTime('waktu_presensi');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('presensi');
    }
};

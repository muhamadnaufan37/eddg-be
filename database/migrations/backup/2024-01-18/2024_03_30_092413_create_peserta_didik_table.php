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
        Schema::create('peserta_didik', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_induk_santri', 255);
            $table->string('nama_lengkap', 255);
            $table->string('tempat_lahir', 255);
            $table->date('tanggal_lahir', 255);
            $table->enum('jenis_kelamin', ['LAKI-LAKI', 'PEREMPUAN']);
            $table->string('hoby', 255);
            $table->string('nama_ortu', 255);
            $table->string('no_telepon_ortu', 15);
            $table->text('alamat', 255);
            $table->boolean('status_peserta_didik');
            $table->foreignId('tmpt_daerah');
            $table->foreignId('tmpt_desa')->nullable();
            $table->foreignId('tmpt_kelompok')->nullable();
            $table->boolean('status_atlet_asad')->default(0);
            $table->foreignId('add_by_user_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('peserta_didik');
    }
};

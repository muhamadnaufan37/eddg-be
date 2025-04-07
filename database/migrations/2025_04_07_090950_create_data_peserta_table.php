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
        Schema::create('data_peserta', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('kode_cari_data');
            $table->string('nama_lengkap');
            $table->string('nama_panggilan');
            $table->string('tempat_lahir');
            $table->date('tanggal_lahir');
            $table->text('alamat');
            $table->enum('jenis_kelamin', ['LAKI-LAKI', 'PEREMPUAN']);
            $table->string('no_telepon', 15);
            $table->string('nama_ayah');
            $table->string('nama_ibu');
            $table->string('hoby');
            $table->unsignedBigInteger('pekerjaan');
            $table->string('usia_menikah')->nullable();
            $table->string('kriteria_pasangan')->nullable();
            $table->boolean('status_pernikahan');
            $table->integer('status_sambung');
            $table->boolean('status_atlet_asad')->default(false);
            $table->unsignedBigInteger('tmpt_daerah');
            $table->unsignedBigInteger('tmpt_desa')->nullable();
            $table->unsignedBigInteger('tmpt_kelompok')->nullable();
            $table->enum('jenis_data', ['SENSUS', 'KBM'])->nullable();
            $table->unsignedBigInteger('user_id');
            $table->text('img')->nullable();
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
        Schema::dropIfExists('data_peserta');
    }
};

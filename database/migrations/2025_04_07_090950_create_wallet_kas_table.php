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
        Schema::create('wallet_kas', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('id_user');
            $table->enum('jenis_transaksi', ['PEMASUKAN', 'PENGELUARAN']);
            $table->date('tgl_transaksi');
            $table->text('keterangan');
            $table->integer('jumlah');
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
        Schema::dropIfExists('wallet_kas');
    }
};

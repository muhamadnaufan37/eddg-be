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
        Schema::create('wallet_kas', function (Blueprint $table) {
            $table->id();
            $table->string('id_user', 255);
            $table->enum('jenis_transaksi', ['PEMASUKAN', 'PENGELUARAN']);
            $table->date('tgl_transaksi');
            $table->text('keterangan');
            $table->integer('jumlah');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wallet_kas');
    }
};

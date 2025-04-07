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
        Schema::create('wallet_kas_digital', function (Blueprint $table) {
            $table->char('id', 36)->primary();
            $table->text('transaction_id');
            $table->string('order_id');
            $table->unsignedBigInteger('wallet_user_id');
            $table->unsignedBigInteger('wallet_sensus_id');
            $table->string('bulan');
            $table->unsignedBigInteger('jenis_tampungan');
            $table->unsignedBigInteger('payment_type');
            $table->text('keterangan');
            $table->unsignedBigInteger('transaction_status');
            $table->unsignedBigInteger('amount');
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
        Schema::dropIfExists('wallet_kas_digital');
    }
};

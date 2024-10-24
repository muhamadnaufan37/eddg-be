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
        Schema::create('wallet_kas_digital', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->text('transaction_id');
            $table->string('order_id');
            $table->foreignId('wallet_user_id');
            $table->foreignId('wallet_sensus_id');
            $table->string('bulan');
            $table->foreignId('jenis_tampungan');
            $table->foreignId('payment_type');
            $table->text('keterangan');
            $table->foreignId('transaction_status');
            $table->unsignedBigInteger('amount');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('test_wallet_digital');
    }
};

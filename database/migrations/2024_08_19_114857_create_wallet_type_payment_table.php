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
        Schema::create('wallet_type_payment', function (Blueprint $table) {
            $table->id();
            $table->string('channel_name_payment', 255);
            $table->string('string_name_payment', 255);
            $table->boolean('info_status_payment');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wallet_type_payment');
    }
};

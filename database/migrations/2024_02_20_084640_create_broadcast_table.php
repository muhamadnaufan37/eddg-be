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
        Schema::create('broadcast', function (Blueprint $table) {
            $table->id();
            $table->string('id_user', 255);
            $table->string('judul_broadcast', 255);
            $table->integer('jenis_broadcast');
            $table->text('text_broadcast');
            $table->string('ip', 255);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('broadcast');
    }
};

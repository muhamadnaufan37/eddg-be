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
        Schema::create('rerata_nilai', function (Blueprint $table) {
            $table->id();
            $table->integer('r_nilai1');
            $table->integer('r_nilai2');
            $table->integer('r_nilai3');
            $table->integer('r_nilai4');
            $table->integer('r_nilai5');
            $table->integer('r_nilai6');
            $table->integer('r_nilai7');
            $table->integer('r_nilai8');
            $table->integer('r_nilai9');
            $table->integer('r_nilai10');
            $table->integer('r_nilai11');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rerata_nilai');
    }
};

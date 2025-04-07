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
        Schema::create('rerata_nilai', function (Blueprint $table) {
            $table->bigIncrements('id');
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
            $table->integer('r_nilai11_1')->nullable();
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
        Schema::dropIfExists('rerata_nilai');
    }
};

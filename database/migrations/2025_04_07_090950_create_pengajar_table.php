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
        Schema::create('pengajar', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('nama_pengajar');
            $table->boolean('status_pengajar');
            $table->unsignedBigInteger('tmpt_daerah');
            $table->unsignedBigInteger('tmpt_desa')->nullable();
            $table->unsignedBigInteger('tmpt_kelompok')->nullable();
            $table->unsignedBigInteger('add_by_user_id');
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
        Schema::dropIfExists('pengajar');
    }
};

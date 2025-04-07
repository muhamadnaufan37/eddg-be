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
        Schema::create('kalender_pendidikan', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('tahun_pelajaran');
            $table->string('semester_pelajaran');
            $table->boolean('status_pelajaran');
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
        Schema::dropIfExists('kalender_pendidikan');
    }
};

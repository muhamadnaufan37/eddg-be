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
        Schema::create('pengajar', function (Blueprint $table) {
            $table->id();
            $table->string('nama_pengajar', 255);
            $table->boolean('status_pengajar');
            $table->foreignId('tmpt_daerah');
            $table->foreignId('tmpt_desa')->nullable();
            $table->foreignId('tmpt_kelompok')->nullable();
            $table->foreignId('add_by_user_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pengajar');
    }
};

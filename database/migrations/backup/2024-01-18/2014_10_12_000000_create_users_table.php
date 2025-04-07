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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('nama_lengkap', 255);
            $table->string('email')->unique();
            $table->string('username', 255)->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->foreignId('role_id');
            $table->enum('status', [-1, 1, 0]);
            $table->foreignId('role_daerah')->nullable();
            $table->foreignId('role_desa')->nullable();
            $table->foreignId('role_kelompok')->nullable();
            $table->string('reason_ban', 255);
            $table->timestamp('login_terakhir')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};

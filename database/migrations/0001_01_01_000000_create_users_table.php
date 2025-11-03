<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            // Kolom bawaan Laravel
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();

            // Kolom tambahan untuk aplikasi hibah bansos
            $table->string('username', 10)->nullable();
            $table->string('nohp', 12)->nullable();
            $table->enum('peran', ['admin', 'opd', 'pengusul'])->default('pengusul');
            $table->char('kode_opd', 10)->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');

            $table->foreign('kode_opd')->references('kode_opd')->on('opd');
        });
    }

    public function down()
    {
        Schema::dropIfExists('users');
    }
};

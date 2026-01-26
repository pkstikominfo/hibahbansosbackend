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
            $table->string('username', 50)->nullable();
            $table->string('nohp', 12)->nullable();
            $table->enum('peran', ['admin', 'opd', 'pengusul'])->default('pengusul');
            $table->char('kode_opd', 10)->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');

            $table->foreign('kode_opd')->references('kode_opd')->on('opd');
        });

         Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down()
    {
        Schema::dropIfExists('users');
    }
};

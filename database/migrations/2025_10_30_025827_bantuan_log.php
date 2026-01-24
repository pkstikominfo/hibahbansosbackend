<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('bantuan_log', function (Blueprint $table) {
            $table->integer('idlog', true);
            $table->integer('id_fk')->nullable();
            $table->unsignedBigInteger('iduser')->nullable();
            $table->dateTime('tanggal')->nullable();
            $table->enum('tipe', ['usulan', 'spj'])->default('usulan');

            $table->foreign('iduser')->references('id')->on('users');
        });
    }

    public function down()
    {
        Schema::dropIfExists('bantuan_log');
    }
};

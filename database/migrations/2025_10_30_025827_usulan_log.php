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
        Schema::create('usulan_log', function (Blueprint $table) {
            $table->integer('idlog', true);
            $table->integer('idusulan')->nullable();
            $table->integer('iduser')->nullable();
            $table->dateTime('tanggal')->nullable();

            $table->foreign('idusulan')->references('idusulan')->on('usulan');
            $table->foreign('iduser')->references('iduser')->on('users');
        });
    }

    public function down()
    {
        Schema::dropIfExists('usulan_log');
    }
};

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
        Schema::create('kategori', function (Blueprint $table) {
            $table->tinyInteger('idkategori', true);
            $table->tinyInteger('idjenisbantuan');
            $table->text('namakategori')->nullable();
            $table->text('keterangan')->nullable();

            $table->foreign('idjenisbantuan')->references('idjenisbantuan')->on('jenis_bantuan');
        });
    }

    public function down()
    {
        Schema::dropIfExists('kategori');
    }
};

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
        Schema::create('jenis_bantuan', function (Blueprint $table) {
            $table->tinyInteger('idjenisbantuan', true);
            $table->string('namajenisbantuan', 30)->nullable();
            $table->text('keterangan')->nullable();
        });
    }

    public function down()
    {
        Schema::dropIfExists('jenis_bantuan');
    }
};

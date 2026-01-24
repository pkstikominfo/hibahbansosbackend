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
        Schema::create('sub_jenis_bantuan', function (Blueprint $table) {
            $table->tinyInteger('idsubjenisbantuan', true);
            $table->tinyInteger('idjenisbantuan')->nullable();
            $table->string('namasubjenis', 30)->nullable();
            $table->text('keterangan')->nullable();

            $table->foreign('idjenisbantuan')->references('idjenisbantuan')->on('jenis_bantuan');
        });
    }

    public function down()
    {
        Schema::dropIfExists('sub_jenis_bantuan');
    }
};

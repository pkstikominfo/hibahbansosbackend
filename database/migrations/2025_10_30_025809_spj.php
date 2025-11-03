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
        Schema::create('spj', function (Blueprint $table) {
            $table->integer('idspj', true);
            $table->integer('idusulan')->nullable();
            $table->string('file_pertanggungjawaban', 100)->nullable();
            $table->integer('realisasi');
            $table->enum('status', ['diusulkan', 'disetujui'])->nullable();

            $table->foreign('idusulan')->references('idusulan')->on('usulan');
        });
    }

    public function down()
    {
        Schema::dropIfExists('spj');
    }
};

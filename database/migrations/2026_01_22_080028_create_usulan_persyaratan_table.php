<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('usulan_persyaratan', function (Blueprint $table) {
            $table->integer('id_up', true)->primary();
            $table->integer('idusulan');
            $table->integer('id_fp');
            $table->string('file_persyaratan', 100);

            $table->foreign('idusulan')->references('idusulan')->on('usulan');
            $table->foreign('id_fp')->references('id_fp')->on('file_persyaratan');
        });
    }
     /* Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('usulan_persyaratan');
    }
};

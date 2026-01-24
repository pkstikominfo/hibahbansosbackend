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
        Schema::create('file_persyaratan', function (Blueprint $table) {
            $table->integer('id_fp', true);
            $table->char('id_opd', 10);
            $table->char('nama_persayaratan', length: 100);
            $table->tinyInteger('idsubjenisbantuan');

            $table->foreign('id_opd')->references('kode_opd')->on('opd');
            $table->foreign('idsubjenisbantuan')->references('idsubjenisbantuan')->on('sub_jenis_bantuan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('file_persyaratan');
    }
};

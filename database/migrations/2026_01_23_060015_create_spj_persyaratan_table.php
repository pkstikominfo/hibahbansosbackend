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
        Schema::create('spj_persyaratan', function (Blueprint $table) {

            $table->integer('id_sp', true)->primary();
            $table->integer('idspj');
            $table->string('file_persyaratan', 100);

            $table->foreign('idspj')->references('idspj')->on('spj');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('spj_persyaratan');
    }
};

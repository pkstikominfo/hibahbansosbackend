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
        Schema::create('desa', function (Blueprint $table) {
            $table->id('iddesa')->primary();
            $table->integer('idkecamatan')->nullable();
            $table->text('namadesa')->nullable();

            // Foreign key constraint
            $table->foreign('idkecamatan')
                ->references('idkecamatan')
                ->on('kecamatan')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('desa');
    }
};

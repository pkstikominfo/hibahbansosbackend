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
        Schema::create('usulan', function (Blueprint $table) {
            $table->integer('idusulan', true);
            $table->text('judul')->nullable();
            $table->integer('anggaran_usulan')->nullable();
            $table->string('email', 30);
            $table->string('nohp', 12);
            $table->tinyInteger('idsubjenisbantuan');
            $table->tinyInteger('idkategori');
            $table->integer('anggaran_disetujui')->nullable();
            $table->char('kode_opd', 10);
            $table->enum('status', ['diusulkan', 'disetujui', 'diterima', 'ditolak'])->nullable();
            $table->softDeletes(); // Add this line for soft delete
            $table->string('nama', 75);
            $table->text('catatan_ditolak')->nullable();
            $table->year('tahun')->default(date('Y'));

            $table->unsignedBigInteger('iddesa')->references('iddesa')->on('desa');
            $table->foreign('idsubjenisbantuan')->references('idsubjenisbantuan')->on('sub_jenis_bantuan');
            $table->foreign('idkategori')->references('idkategori')->on('kategori');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('usulan');
    }
};

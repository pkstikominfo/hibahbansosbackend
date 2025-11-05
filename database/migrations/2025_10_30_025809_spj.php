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
            $table->string('file_pertanggungjawaban', 100);
            $table->string('foto', 100);
            $table->integer('realisasi');
            $table->enum('status', ['diusulkan', 'disetujui'])->nullable();


             // 🔗 Relasi
            $table->foreign('idusulan')->references('idusulan')->on('usulan');

            // 🧑‍💼 User yang membuat data
            $table->unsignedBigInteger('created_by')->nullable();
            $table->foreign('created_by')->references('iduser')->on('users');
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->foreign('updated_by')->references('iduser')->on('users');

            // ⏰ Timestamp otomatis
            $table->timestamps(); // ini otomatis menambah created_at & updated_at
        });
    }

    public function down()
    {
        Schema::dropIfExists('spj');
    }
};

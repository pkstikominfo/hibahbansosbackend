<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;


class HibahBansosSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        // Data jenis_bantuan
        DB::table('jenis_bantuan')->insert([
            ['idjenisbantuan' => 1, 'namajenisbantuan' => 'Hibah'],
            ['idjenisbantuan' => 2, 'namajenisbantuan' => 'Bantuan Sosial'],
        ]);

        // Data sub_jenis_bantuan
        DB::table('sub_jenis_bantuan')->insert([
            ['idsubjenisbantuan' => 1, 'idjenisbantuan' => 1, 'namasubjenis' => 'Hibah Uang'],
            ['idsubjenisbantuan' => 2, 'idjenisbantuan' => 1, 'namasubjenis' => 'Hibah Barang'],
            ['idsubjenisbantuan' => 3, 'idjenisbantuan' => 2, 'namasubjenis' => 'Bantuan Sosial Uang'],
            ['idsubjenisbantuan' => 4, 'idjenisbantuan' => 2, 'namasubjenis' => 'Bantuan Sosial Barang'],
        ]);

        // Data kategori
        DB::table('kategori')->insert([
            ['idkategori' => 1, 'idjenisbantuan' => 2, 'namakategori' => 'Rehabilitasi sosial'],
            ['idkategori' => 2, 'idjenisbantuan' => 2, 'namakategori' => 'Perlindungan sosial'],
            ['idkategori' => 3, 'idjenisbantuan' => 2, 'namakategori' => 'Pemberdayaan sosial'],
            ['idkategori' => 4, 'idjenisbantuan' => 2, 'namakategori' => 'Jaminan sosial'],
            ['idkategori' => 5, 'idjenisbantuan' => 2, 'namakategori' => 'Penanggulangan kemiskinan'],
            ['idkategori' => 6, 'idjenisbantuan' => 2, 'namakategori' => 'Penanggulangan bencana'],
            ['idkategori' => 7, 'idjenisbantuan' => 1, 'namakategori' => 'Hibah Kepada Pemerintah Pusat'],
            ['idkategori' => 8, 'idjenisbantuan' => 1, 'namakategori' => 'Hibah Kepada Pemerintah Daerah Lainnya'],
            ['idkategori' => 9, 'idjenisbantuan' => 1, 'namakategori' => 'Hibah Kepada BUMN'],
            ['idkategori' => 10, 'idjenisbantuan' => 1, 'namakategori' => 'Hibah Kepada BUMD'],
            ['idkategori' => 11, 'idjenisbantuan' => 1, 'namakategori' => 'Hibah Kepada Badan dan Lembaga, serta Organisasi Kemasyarakatan yang Berbadan Hukum Indonesia'],
            ['idkategori' => 12, 'idjenisbantuan' => 1, 'namakategori' => 'Hibah kepada organisasi kemasyarakatan yang berbadan hukum'],
        ]);
    }
}

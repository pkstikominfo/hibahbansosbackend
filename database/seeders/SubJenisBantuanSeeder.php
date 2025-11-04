<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SubJenisBantuanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $subJenisBantuan = [
            ['idsubjenisbantuan' => 1, 'idjenisbantuan' => 1, 'namasubjenis' => 'Hibah Uang'],
            ['idsubjenisbantuan' => 2, 'idjenisbantuan' => 1, 'namasubjenis' => 'Hibah Barang'],
            ['idsubjenisbantuan' => 3, 'idjenisbantuan' => 2, 'namasubjenis' => 'Bantuan Sosial Uang'],
            ['idsubjenisbantuan' => 4, 'idjenisbantuan' => 2, 'namasubjenis' => 'Bantuan Sosial Barang'],
        ];

        foreach ($subJenisBantuan as $subJenis) {
            DB::table('sub_jenis_bantuan')->updateOrInsert(
                ['idsubjenisbantuan' => $subJenis['idsubjenisbantuan']],
                $subJenis
            );
        }

        $this->command->info('Seeder Sub Jenis Bantuan berhasil ditambahkan!');
    }
}

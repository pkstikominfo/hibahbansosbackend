<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class JenisBantuanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $jenisBantuan = [
            ['idjenisbantuan' => 1, 'namajenisbantuan' => 'Hibah'],
            ['idjenisbantuan' => 2, 'namajenisbantuan' => 'Bantuan Sosial'],
        ];

        foreach ($jenisBantuan as $jenis) {
            DB::table('jenis_bantuan')->updateOrInsert(
                ['idjenisbantuan' => $jenis['idjenisbantuan']],
                $jenis
            );
        }

        $this->command->info('Seeder Jenis Bantuan berhasil ditambahkan!');
    }
}

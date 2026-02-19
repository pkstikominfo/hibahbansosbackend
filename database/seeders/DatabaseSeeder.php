<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            HibahBansosSeeder::class,
            KecamatanDesaSeeder::class,
            OpdSeeder::class,          // ⚠️ HARUS SEBELUM USER
            AdditionalUserSeeder::class,
            JenisBantuanSeeder::class,
            SubJenisBantuanSeeder::class,
            KategoriSeeder::class,
            RealDummyDataSeeder::class
        ]);

        // Token fonte
        DB::table('tb_token')->insert([
            [
                'source' => 'Fonte',
                'token' => '3qq6bnduK78W8DRgGi13',
                'nama' => 'Smartfren',
                'status' => 'active'
            ]
        ]);
    }
}

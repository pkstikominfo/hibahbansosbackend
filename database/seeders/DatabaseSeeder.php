<?php

namespace Database\Seeders;

use App\Http\Controllers\Api\OpdController;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            HibahBansosSeeder::class,
            KecamatanDesaSeeder::class,
            OpdSeeder::class,
            AdditionalUserSeeder::class,
            JenisBantuanSeeder::class,
            SubJenisBantuanSeeder::class,
            KategoriSeeder::class,

        ]);

        $token = [
            [
                'id'   => 1,
                'source'  => 'Fonte',
                'token'  => '3qq6bnduK78W8DRgGi13',
                'nama'  => 'Smartfren',
                'status' => 'active'
            ],
        ];
        DB::table('tb_token')->insert($token);
    }
}

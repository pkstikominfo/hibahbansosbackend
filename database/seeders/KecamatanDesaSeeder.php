<?php

namespace Database\Seeders;

use App\Models\Desa;
use App\Models\Kecamatan;
use Illuminate\Database\Seeder;

class KecamatanDesaSeeder extends Seeder
{
    public function run(): void
    {
        Desa::truncate();
        Kecamatan::truncate();

        $data = [
            // idkecamatan = 1
            [
                'kecamatan' => 'Bintauna',
                'desa' => [
                    'Bintauna',
                    'Batulintik',
                    'Bintauna Pantai',
                    'Bunia',
                    'Bunong',
                    'Huntuk',
                    'Kopi',
                    'Kuhanga',
                    'Minanga',
                    'Mome',
                    'Padang',
                    'Padang Barat',
                    'Pimpi',
                    'Talaga',
                    "Voa'a",
                    'Vahuta'
                ]
            ],

            // idkecamatan = 2
            [
                'kecamatan' => 'Bolangitang Barat',
                'desa' => [
                    'Paku Selatan',
                    'Ollot II',
                    'Paku',
                    'Talaga',
                    'Tote',
                    'Wakat',
                    'Bolangitang',
                    'Bolangitang I',
                    'Bolangitang II',
                    'Iyok',
                    'Jambusarang',
                    'Keimanga',
                    'Langi',
                    'Ollot I',
                    'Ollot',
                    'Sonuo',
                    'Talaga Tomoagu',
                    'Tanjung Buaya'
                ]
            ],

            // idkecamatan = 3
            [
                'kecamatan' => 'Bolangitang Timur',
                'desa' => [
                    'Binuanga',
                    'Binuni',
                    'Binjeita',
                    'Bohabak I',
                    'Bohabak III',
                    'Bohabak IV',
                    'Mokoditek',
                    'Mokoditek I',
                    'Nunuka',
                    'Saleo',
                    'Saleo Satu',
                    'Tanjung Labou',
                    'Binjeita I',
                    'Binjeita II',
                    'Biontong',
                    'Biontong I',
                    'Biontong II',
                    'Bohabak II',
                    'Lipu Bogu',
                    'Nagara'
                ]
            ],

            // idkecamatan = 4
            [
                'kecamatan' => 'Kaidipang',
                'desa' => [
                    'Bigo Selatan',
                    'Boroko Utara',
                    'Boroko',
                    'Gihang',
                    'Inomunga',
                    'Inomunga Utara',
                    'Komus Dua Timur',
                    'Bigo',
                    'Boroko Timur',
                    'Komus II',
                    'Kuala',
                    'Kuala Utara',
                    'Solo',
                    'Pontak',
                    'Soligir'
                ]
            ],

            // idkecamatan = 5
            [
                'kecamatan' => 'Pinogaluman',
                'desa' => [
                    'Batu Tajam',
                    'Busato',
                    'Dalapuli Barat',
                    'Dengi',
                    'Tanjung Sidupa',
                    'Tuntung',
                    'Tuntung Timur',
                    'Batu Bantayo',
                    'Buko Selatan',
                    'Buko Utara',
                    'Buko',
                    'Dalapuli',
                    'Dalapuli Timur',
                    'Duini',
                    'Kayu Ogu',
                    'Komus I',
                    'Padango',
                    'Tambulang Pantai',
                    'Tambulang Timur',
                    'Tombulang',
                    'Tuntulow',
                    'Tuntulow Utara'
                ]
            ],

            // idkecamatan = 6
            [
                'kecamatan' => 'Sangkub',
                'desa' => [
                    'Ampeng Sembeka',
                    'Busisingo',
                    'Busisingo Utara',
                    'Sampiro',
                    'Sang Tombolang',
                    'Sangkub III',
                    'Mokusato',
                    'Monompia',
                    'Sidodadi',
                    'Pangkusa',
                    'Sangkub Empat',
                    'Sangkub I',
                    'Sangkub II',
                    'Sangkub Timur',
                    'Suka Makmur',
                    'Tombolango'
                ]
            ]
        ];

        foreach ($data as $item) {
            $kecamatan = Kecamatan::create([
                'namakecamatan' => $item['kecamatan']
            ]);

            foreach ($item['desa'] as $desa) {
                Desa::create([
                    'idkecamatan' => $kecamatan->idkecamatan,
                    'namadesa' => $desa
                ]);
            }
        }

        $this->command->info('Seeder Kecamatan & Desa berhasil dijalankan.');
    }
}
